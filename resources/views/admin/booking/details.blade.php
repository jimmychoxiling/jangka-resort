@extends('admin.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card b-radius--10">

                @php
                    $bookedBy = actionTakenBy($booking->bookedBy);
                    $approvedBy = actionTakenBy($booking->approvedBy);
                    $checkedOutBy = actionTakenBy($booking->checkedOutBy);
                @endphp
                <div class="card-header">
                    <div class="d-flex flex-wrap gap-4">
                        @if ($bookedBy)
                            <span>
                                <span class="fw-bold">@lang('Booked By')</span>:
                                <span class="text--info">{{ actionTakenBy($booking->bookedBy) }}</span>
                            </span>
                        @endif

                        @if ($approvedBy)
                            <span>
                                <span class="fw-bold">@lang('Approved By')</span>:
                                <span class="text--info">{{ actionTakenBy($booking->approvedBy) }}</span>
                            </span>
                        @endif

                        @if ($checkedOutBy)
                            <span>
                                <span class="fw-bold">@lang('Checked Out By')</span>:
                                <span class="text--info">{{ actionTakenBy($booking->checkedOutBy) }}</span>
                            </span>
                        @endif
                    </div>
                </div>
                <div class="card-body p-0">

                    <div class="d-flex justify-content-end me-3 flex-wrap gap-3 p-2">
                        <div class="d-flex align-items-center gap-1">
                            <span class="custom--label bg--danger"></span>
                            <span>@lang('Cancelled')</span>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <span class="custom--label bg--dark"></span>
                            <span>@lang('Checked Out')</span>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <span class="custom--label bg--18"></span>
                            <span>@lang('Booked')</span>
                        </div>

                    </div>


                    <div class="table-responsive--md table-responsive">
                        <table class="table--light style--two table">
                            <thead>
                                <tr>
                                    <th>@lang('Action')</th>
                                    <th>@lang('Booked For')</th>
                                    <th>@lang('Room Numbers')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($bookedRooms as $key => $bookedRoom)
                                    <tr>
                                        @php
                                            $activeBooking = $bookedRoom->where('status', 1)->count();
                                        @endphp

                                        <td>
                                            <button type="button" class="btn btn--danger confirmationBtn" @if (!$activeBooking || $key <= now()->format('Y-m-d')) disabled @endif data-question="@lang('Are you sure, you want to cancel the booking for this date?')" data-action="{{ route('admin.booked.day.cancel', [$booking->id, $key]) }}">@lang('Cancel Booking')</button>
                                        </td>


                                        <td data-label="@lang('Booked For')">
                                            {{ __(showDateTime($key, 'd M, Y')) }}
                                        </td>

                                        <td data-label="@lang('Room Numbers')" class="d-flex justify-content-end flex-wrap gap-2">
                                            @foreach ($bookedRoom as $item)
                                                @if ($item->status == 3)
                                                    <div class="bg--danger room-container rounded p-2">
                                                        <span class="f-size--24 text--white">
                                                            {{ __($item->room->room_number) }}
                                                        </span>
                                                        <span class="d-block text--white text--shadow">
                                                            {{ __($item->room->roomType->name) }}
                                                        </span>

                                                    </div>
                                                @elseif($item->status == 9)
                                                    <div class="bg--dark room-container rounded p-2">
                                                        <span class="f-size--24 text--white">
                                                            {{ __($item->room->room_number) }}
                                                        </span>
                                                        <span class="d-block text--white text--shadow">
                                                            {{ __($item->room->roomType->name) }}
                                                        </span>

                                                    </div>

                                                    @elseif($item->status == 1)
                                                    <div class="bg--18 room-container rounded p-2">
                                                        <span class="f-size--24 text--white">
                                                            {{ __($item->room->room_number) }}
                                                        </span>
                                                        <span class="d-block text--white text--shadow">
                                                            {{ __($item->room->roomType->name) }}
                                                        </span>

                                                        @if (now()->toDateString() < $item->booked_for)
                                                            <button type="button" class="cancel-btn confirmationBtn" data-question="@lang('Are you sure, you want to cancel this booked room?')" data-action="{{ route('receptionist.booked.room.cancel', $item->id) }}"><i class="las la-times"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                @endif
                                            @endforeach
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="100%" class="text-center">{{ __($emptyMessage) }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <x-confirmation-modal></x-confirmation-modal>


@endsection

@push('breadcrumb-plugins')
    <div class="d-flex align-items-center justify-content-end flex-wrap gap-1">
        <a href="{{ url()->previous() }}" class="btn btn-sm btn-outline--primary"> <i class="las la-undo"></i>@lang('Back')</a>
    </div>
@endpush

@push('script')
    <script>
        (function($) {
            "use strict";
            var previous = @json(url()->previous());
            $('.sidebar__menu li a[href="' + previous + '"]').closest('li').addClass('active');
        })(jQuery);
    </script>
@endpush
