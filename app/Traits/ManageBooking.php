<?php

namespace App\Traits;

use Illuminate\Support\Facades\Validator;
use App\Models\Booking;
use App\Models\BookedRoom;
use App\Models\RoomType;
use App\Models\Room;
use App\Models\User;
use App\Models\BookingActionHistory;
use App\Models\Deposit;
use App\Models\GatewayCurrency;
use App\Models\PaymentLog;
use App\Models\UsedExtraService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use PDF;

trait ManageBooking
{
    protected $userType;

    public function room()
    {
        $pageTitle = 'Book Room';
        $roomTypes = RoomType::active()->get(['id', 'name']);
        return view($this->userType . '.booking.book', compact('pageTitle', 'roomTypes'));
    }

    public function todaysBooked()
    {
        $pageTitle = 'Todays Booked Rooms';
        if(request()->type == 'not_booked'){
            $pageTitle = 'Available Rooms to Book Today';
        }


        $rooms = BookedRoom::active()
        ->with(['room:id,room_number,room_type_id',
            'room.roomType:id,name',
            'booking:id,user_id,booking_number',
            'booking.user:id,firstname,lastname',
            'extraServices.extraService:id,name'
        ])
        ->where('booked_for', now()->toDateString())
        ->get();

        $bookedRooms = $rooms->pluck('id')->toArray();

        $emptyRooms = Room::whereNotIn('id', $bookedRooms)->with('roomType:id,name')->select('id', 'room_type_id','room_number')->get();

        return view($this->userType . '.booking.todays_booked', compact('pageTitle', 'rooms', 'emptyRooms'));
    }

    function searchRoom(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'room_type'  => 'required|exists:room_types,id',
            'date'       => 'required',
            'rooms'      => 'required|integer|gt:0'
        ]);

        if (!$validator->passes()) {
            return response()->json(['error' => $validator->errors()->all()]);
        }

        $date = explode('-', $request->date);

        $request->merge([
            'checkin_date'  => trim(@$date[0]),
            'checkout_date' => trim(@$date[1]),
        ]);

        $validator = Validator::make($request->all(), [
            'checkin_date'  => 'required|date_format:m/d/Y|after:yesterday',
            'checkout_date' => 'nullable|date_format:m/d/Y|after:checkin_date',
        ]);

        if (!$validator->passes()) {
            return response()->json(['error' => $validator->errors()->all()]);
        }

        $view =  $this->getRooms($request);
        return response()->json(['html' => $view]);
    }

    // This method also inherited in ManageBookingRequest Trait.
    private function getRooms(Request $request)
    {
        $checkIn  = Carbon::parse($request->checkin_date);
        $checkOut = $request->checkout_date ? Carbon::parse($request->checkout_date) : $checkIn;
        $rooms    = Room::active()
            ->where('room_type_id', $request->room_type)
            ->with([
                'booked' => function ($q) {
                    $q->active();
                },
                'roomType' => function ($q) {
                    $q->select('id', 'name', 'fare');
                }
            ])
            ->get();

        if (count($rooms) < $request->rooms) {
            return ['error' => ['The requested number of rooms is not available for the selected date']];
        }

        $numberOfRooms = $request->rooms;
        $requestUnitFare = $request->unit_fare;

        return view('partials.rooms', compact('checkIn', 'checkOut', 'rooms', 'numberOfRooms', 'requestUnitFare'))->render();
    }

    public function book(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'guest_type'       => 'required|in:1,0',
            'guest_name'       => 'nullable|required_if:guest_type,0',
            'email'            => 'required|email',
            'mobile'           => 'nullable|required_if:guest_type,0|regex:/^([0-9]*)$/',
            'room'             => 'required|array',
            'paid_amount'      => 'nullable|integer|gte:0'
        ]);

        if (!$validator->passes()) {
            return response()->json(['error' => $validator->errors()->all()]);
        }

        if ($request->paid_amount > $request->total_amount) {
            return response()->json(['error' => ['Paying amount can\'t be greater than total amount']]);
        }

        $guest = [];

        if ($request->guest_type == 1) {
            $user = User::where('email', $request->email)->first();
            if (!$user) {
                return response()->json(['error' => ['User not found']]);
            }
        } else {
            $guest['name']   = $request->guest_name;
            $guest['email']  = $request->email;
            $guest['mobile'] = $request->mobile;
        }

        $bookedRoomData = [];

        foreach ($request->room as $room) {
            $data = [];
            $roomId     = explode('-', $room)[0];
            $bookedFor  = explode('-', $room)[1];

            // Check if already booked for this date;
            $isBooked = BookedRoom::where('room_id', $roomId)->where('booked_for', $bookedFor)->exists();

            if ($isBooked) {
                return response()->json(['error' => ['Something went wrong!']]);
            }

            $room               = Room::with('roomType')->findOrFail($roomId);
            $data['booking_id'] = 0;
            $data['room_id']    = $room->id;
            $data['booked_for'] = Carbon::parse($bookedFor)->format('Y-m-d');
            $data['fare']       = $room->roomType->fare;
            $data['status']     = 1;
            $bookedRoomData[]   = $data;
        }

        $booking                = new Booking();
        $booking->booking_number  = getTrx();
        $booking->user_id       = $request->guest_type ? $user->id : 0;
        $booking->guest_details = $guest;
        $booking->total_amount  = $request->total_amount;
        $booking->paid_amount   = $request->paid_amount ?? 0;
        $booking->status        = 1;
        $booking->save();

        if ($request->paid_amount) {
            $this->paymentLog($booking->id, $booking->paid_amount, 'RECEIVED');
        }

        $this->bookingActionHistory('book_room', $booking->id);

        $bookedRoomData = collect($bookedRoomData)->map(function ($data) use ($booking) {
            $data['booking_id'] = $booking->id;
            return $data;
        });

        BookedRoom::insert($bookedRoomData->toArray());
        return response()->json(['success' => ['Room booked successfully']]);
    }

    public function activeBookings()
    {
        $pageTitle = 'Upcoming and Running';
        $bookings = $this->bookingData('active');
        return view($this->userType . '.booking.list', compact('pageTitle', 'bookings'));
    }

    public function checkedOutBookingList()
    {
        $pageTitle = 'Checked Out Bookings';
        $bookings = $this->bookingData('checkedOut');
        return view($this->userType . '.booking.list', compact('pageTitle', 'bookings'));
    }

    public function cancelledBookingList()
    {
        $pageTitle = 'Cancelled Bookings';
        $bookings = $this->bookingData('cancelled');

        return view($this->userType . '.booking.list', compact('pageTitle', 'bookings'));
    }

    public function allBookingList()
    {
        $pageTitle = 'All Bookings';
        $bookings  = $this->bookingData('ALL');
        return view($this->userType . '.booking.list', compact('pageTitle', 'bookings'));
    }

    public function mergeBooking(Request $request, $id)
    {
        $parentBooking = Booking::active()->findOrFail($id);

        $request->merge(['merge_with' => $parentBooking->booking_number]);

        $request->validate([
            'booking_numbers'   => 'required|array',
            'booking_numbers.*' => 'exists:bookings,booking_number|different:merge_with'
        ], [
            'booking_numbers.*.different' => 'All booking numbers must be different from the booking number of merging with'
        ]);

        // Check if available to merge
        $check =  Booking::whereIn('booking_number', $request->booking_numbers)->where('status', '!=', 1)->first();

        if ($check) {
            $notify[] = ['error', $check->booking_number . ' can\'t be merged. Only running bookings are able to merge.'];
            return back()->withNotify($notify);
        }

        foreach ($request->booking_numbers as $orderNumber) {
            $booking = Booking::where('booking_number', $orderNumber)->first();
            $booking->usedExtraService()->update(['booking_id' => $parentBooking->id]);
            $booking->bookedRoom()->update(['booking_id' => $parentBooking->id]);

            BookingActionHistory::where('booking_id', $booking->id)->delete();
            PaymentLog::where('booking_id', $booking->id)->update(['booking_id' => $parentBooking->id]);

            $parentBooking->total_amount += $booking->total_amount;
            $parentBooking->paid_amount += $booking->paid_amount;
            $parentBooking->save();
            $booking->delete();
        }

        $action = new BookingActionHistory();
        $action->booking_id = $parentBooking->id;
        $action->remark = 'merged_booking';
        $action->details = implode(', ', $request->booking_numbers) . ' merged with ' . $parentBooking->booking_number;

        $column = $this->column;
        $action->$column = $this->user->id;

        $action->save();

        $notify[] = ['success', 'Bookings merged successfully'];
        return redirect()->route($this->userType . '.booking.details', $parentBooking->id)->withNotify($notify);
    }


    public function payment(Request $request, $id)
    {
        $request->validate([
            'type'   => 'string|in:return,receive',
            'amount' => 'required|integer|gt:0'
        ]);

        $booking = Booking::withSum('usedExtraService', 'total_amount')
            ->withSum(['bookedRoom' => function ($booked) {
                $booked->where('status', 1);
            }], 'fare')->findOrFail($id);

        $booking = $this->adjustTotalAmount($booking);

        if ($booking->status != 1) {
            $notify[] = ['error', 'Amount should be paid while booking is active'];
            return back()->withNotify($notify);
        }

        if ($request->type == 'receive') {
            return $this->receivePayment($booking, $request->amount);
        }
        return $this->returnPayment($booking, $request->amount);
    }


    protected function receivePayment($booking, $receivingAmount)
    {

        $due = $booking->total_amount - $booking->paid_amount;

        if ($receivingAmount > $due) {
            $notify[] = ['error', 'Amount shouldn\'t be greater than payable amount'];
            return back()->withNotify($notify);
        }

        $this->deposit($booking, $receivingAmount);
        $this->paymentLog($booking->id, $receivingAmount, 'RECEIVED');
        $this->bookingActionHistory('payment_received', $booking->id);

        $booking->paid_amount += $receivingAmount;
        $booking->save();

        $notify[] = ['success', 'Payment received successfully'];
        return back()->withNotify($notify);
    }

    protected function returnPayment($booking, $receivingAmount)
    {

        $due = $booking->total_amount - $booking->paid_amount;


        if ($due > 0) {
            $notify[] = ['error', 'Invalid action'];
            return back()->withNotify($notify);
        }

        $due = abs($due);

        if ($receivingAmount > $due) {
            $notify[] = ['error', 'Amount shouldn\'t be greater than payable amount'];
            return back()->withNotify($notify);
        }

        $this->paymentLog($booking->id, $receivingAmount, 'RETURNED');
        $this->bookingActionHistory('payment_returned', $booking->id);

        $booking->paid_amount -= $receivingAmount;
        $booking->save();

        $notify[] = ['success', 'Payment received successfully'];
        return back()->withNotify($notify);
    }

    protected function deposit($booking, $payableAmount)
    {
        $gate = GatewayCurrency::where('id', 0)->first();

        $data = new Deposit();
        $data->user_id = $booking->user_id;
        $data->booking_id = $booking->id;
        $data->method_code = $gate->method_code;
        $data->method_currency = strtoupper($gate->currency);
        $data->amount = $payableAmount;
        $data->charge = 0;
        $data->rate = $gate->rate;
        $data->final_amo = $payableAmount;
        $data->btc_amo = 0;
        $data->btc_wallet = "";
        $data->trx = getTrx();
        $data->try = 0;
        $data->status = 1;
        $data->save();
    }

    public function checkOutPreview($id)
    {
        $booking = Booking::withSum('usedExtraService', 'total_amount')
            ->withSum(['bookedRoom' => function ($booked) {
                $booked->where('status', 1);
            }], 'fare')->findOrFail($id);

        if ($booking->status != 1) {
            $notify[] = ['error', 'This is not an active booking.'];
            return back()->withNotify($notify);
        }

        $booking = $this->adjustTotalAmount($booking);


        $pageTitle = "Check Out - " . $booking->booking_number;
        return view($this->userType . '.booking.check_out', compact('pageTitle', 'booking'));
    }

    public function checkOut($id)
    {
        $booking = Booking::active()->withMin('bookedRoom', 'booked_for')->withSum('usedExtraService', 'total_amount')->findOrFail($id);

        $checkedInDate = $booking->booked_room_min_booked_for;

        if ($checkedInDate > now()->toDateString()) {
            $notify[] = ['error', 'Check-in date for this booking is greater than now'];
            return back()->withNotify($notify);
        }

        $totalAmount = $booking->total_amount - $booking->used_extra_service_sum_total_amount ?? 0;
        $dueAmount = $totalAmount - $booking->paid_amount;

        if ($dueAmount > 0) {
            $notify[] = ['error', 'The due amount must be paid before check-out.'];
            return back()->withNotify($notify);
        }

        if ($dueAmount < 0) {
            $notify[] = ['error', 'Pay the extra amount to the guest, before checkout.'];
            return back()->withNotify($notify);
        }

        $this->bookingActionHistory('checked_out', $booking->id);

        $booking->bookedRoom()->where('status', '!=', 3)->update(['status' => 9]);
        $booking->status = 9;
        $booking->checked_out_at = now();

        $booking->save();

        $notify[] = ['success', 'Booking checked out successfully'];
        return redirect()->route($this->userType . '.booking.checkout', $id)->withNotify($notify);
    }


    public function generateInvoice($bookingId)
    {
        $booking = Booking::with([
            'bookedRoom' => function ($query) {
                $query->select('id','booking_id','room_id','fare','status', 'booked_for')
                ->where('status', 1);
            },
            'bookedRoom.room:id,room_type_id,room_number',
            'bookedRoom.room.roomType:id,name',
            'usedExtraService.room',
            'usedExtraService.extraService',
            'user:id,firstname,lastname,username,email,mobile',
            'payments'
        ])
        ->withSum(['bookedRoom' => function ($booked) {
            $booked->where('status', 1);
        }], 'fare')
        ->withSum('usedExtraService', 'total_amount')
        ->findOrFail($bookingId);

        $booking = $this->adjustTotalAmount($booking);

        $data = [
            'booking' => $booking,
        ];

        $pdf = PDF::loadView('partials.invoice', $data);

        return $pdf->stream($booking->booking_number . '.pdf');
    }

    protected function adjustTotalAmount($booking)
    {
        if ($booking->booked_room_sum_fare && ($booking->total_amount != $booking->booked_room_sum_fare)) {
            $booking->total_amount = $booking->booked_room_sum_fare;
            $booking->save();
        }

        return $booking;
    }

    function bookingDetails($id)
    {
        $booking = Booking::findOrFail($id);
        $pageTitle  = 'Booking Details of ' . $booking->booking_number;
        $bookedRooms = BookedRoom::where('booking_id', $id)->with('booking.user', 'room.roomType')->orderBy('booked_for')->get()->groupBy('booked_for');
        return view($this->userType . '.booking.details', compact('pageTitle', 'bookedRooms', 'booking'));
    }


    public function extraServiceDetail($id)
    {
        $booking = Booking::where('id', $id)->firstOrFail();
        $services = UsedExtraService::where('booking_id', $id)->with('extraService', 'room')->paginate(getPaginate());
        $pageTitle = 'Service Details - ' . $booking->booking_number;
        return view($this->userType . '.booking.service_details', compact('pageTitle', 'services'));
    }




    protected function bookingData($scope)
    {
        $request = request();
        $query = Booking::query();

        if ($scope != "ALL") {
            $query = $query->$scope();
        }

        if ($request->search) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('username', 'like', "%$search%")
                    ->orWhere('email', 'like',  "%$search%")
                    ->orWhere('mobile', 'like',  "%$search%");
            })->orWhere(function ($query) use ($search) {
                $query->where('guest_details->name', 'like', "%$search%")
                    ->orWhere('guest_details->email', 'like', "%$search%")
                    ->orWhere('guest_details->mobile', 'like', "%$search%")
                    ->orWhere('booking_number', 'like', "%" . $search . "%");
            });
        }

        if ($request->date) {
            $date  = explode('-', $request->date);

            $request->merge([
                'checkin_date'  => trim(@$date[0]),
                'checkout_date' => trim(@$date[1]) ? trim(@$date[1]) : trim(@$date[0])
            ]);

            $request->validate([
                'checkin_date'  => 'required|date_format:m/d/Y',
                'checkout_date' => 'nullable|date_format:m/d/Y|after_or_equal:checkin_date'
            ]);

            $checkIn  = Carbon::parse($request->checkin_date)->format('Y-m-d');
            $checkOut = Carbon::parse($request->checkout_date)->format('Y-m-d');

            $query->whereHas('bookedRoom', function ($q) use ($checkIn, $checkOut) {
                $q->where('booked_for', '>=', $checkIn)->where('booked_for', '<=', $checkOut);
            });
        }

        return $query->with('bookedRoom.room', 'user')
            ->withMin('bookedRoom', 'booked_for')
            ->withMax('bookedRoom', 'booked_for')
            ->withSum('usedExtraService', 'total_amount')
            ->orderBy('booked_room_min_booked_for', 'asc')
            ->latest()
            ->paginate(getPaginate());
    }

    protected function bookingActionHistory($remark, $bookingId, $details = null)
    {
        $bookingActionHistory = new BookingActionHistory();

        $bookingActionHistory->booking_id = $bookingId;
        $bookingActionHistory->remark = $remark;
        $bookingActionHistory->details = $details;

        $column = $this->column;
        $bookingActionHistory->$column = $this->user->id;
        $bookingActionHistory->save();
    }

    protected function paymentLog($bookingId, $amount, $type)
    {
        $column = $this->column;

        $paymentLog             = new PaymentLog();
        $paymentLog->booking_id = $bookingId;
        $paymentLog->amount     = $amount;
        $paymentLog->type       = $type;
        $paymentLog->$column    = $this->user->id;
        $paymentLog->save();
    }

    public function cancelBooking($id)
    {
        $booking = Booking::active()->withMin('bookedRoom', 'booked_for')->findOrFail($id);

        $checkIn = $booking->booked_room_min_booked_for;

        if ( $checkIn <= now()->toDateString()) {
            $notify[] = ['error', 'Only future days bookings can be cancelled'];
            return back()->withNotify($notify);
        }

        $this->bookingActionHistory('cancel_booking', $booking->id);
        $booking->bookedRoom()->update(['status' => 3]);
        $booking->status = 3;
        $booking->save();

        $rooms = Room::whereIn('id', $booking->bookedRoom()->pluck('room_id')->toArray())->get()->pluck('room_number')->toArray();


        // Return the paid amount to user
        if ($booking->paid_amount > 0) {
            $this->paymentLog($booking->id, $booking->paid_amount, 'RETURNED');
            $booking->paid_amount = 0;
        }

        if ($booking->user) {
            notify($booking->user, 'BOOKING_CANCELLED', [
                'booking_number' => $booking->booking_number,
                'rooms' => implode(', ', $rooms),
                'check_in' => Carbon::parse($booking->bookedRoom->first()->booked_for)->format('d M, Y'),
                'check_out' => Carbon::parse($booking->bookedRoom->last()->booked_for)->format('d M, Y')
            ]);
        }

        $notify[] = ['success', 'Booking cancelled successfully'];
        return back()->with($notify);
    }


    public function cancelBookingByDate($id, $date)
    {
        $booking = Booking::findOrFail($id);

        if ($date <= now()->format('Y-m-d')) {
            $notify[] = ['error', 'Only upcoming bookings can be cancelled'];
            return back()->withNotify($notify);
        }

        if ($booking->status == 9 || $booking->status == 3) {
            $notify[] = ['error', 'This booking can\'t be cancelled'];
            return back()->withNotify($notify);
        }

        $roomsFare = $booking->bookedRoom()->where('booked_for', $date)->sum('fare');
        $booking->total_amount -= $roomsFare;
        $booking->save();

        $booking->bookedRoom()->where('booked_for', $date)->update(['status' => 3]);
        $this->bookingActionHistory('cancel_booking', $booking->id, 'Canceled Booking of ' . showDateTime($date, 'd M, Y'));

        $bookedRooms = $booking->bookedRoom()->where('booked_for', $date)->pluck('room_id')->toArray();
        $rooms = Room::whereIn('id', $bookedRooms)->get()->pluck('room_number')->toArray();

        if ($booking->user) {
            notify($booking->user, 'BOOKING_CANCELLED_BY_DATE', [
                'booking_number' => $booking->booking_number,
                'date' => showDateTime($date, 'd M, Y'),
                'rooms' => implode(', ', $rooms)
            ]);
        }

        $notify[] = ['success', 'Booking cancelled successfully'];
        return back()->with($notify);
    }


    public function cancelBookedRoom($id)
    {
        $bookedRoom = BookedRoom::findOrFail($id);

        if (now()->toDateString() <= $bookedRoom->booked_for) {
            $notify[] = ['error', 'Only future date\'s bookings can be cancelled'];
            return back()->withNotify($notify);
        }

        if ($bookedRoom->status == 9 || $bookedRoom->status == 3) {
            $notify[] = ['error', 'This room can\'t be cancelled'];
            return back()->withNotify($notify);
        }

        $booking    = Booking::find($bookedRoom->booking_id);

        $this->bookingActionHistory('cancel_room', $booking->id);

        $booking->total_amount -= $bookedRoom->fare;
        $booking->save();
        $bookedRoom->status = 3;

        $bookedRoom->save();
        $notify[] = ['success', 'Room cancelled successfully'];
        return back()->with($notify);
    }
}
