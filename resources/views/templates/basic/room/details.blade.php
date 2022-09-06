@extends($activeTemplate . 'layouts.frontend')
@section('content')
    <!-- room details section start -->
    <section class="section">
        <div class="container">
            <div class="row g-4 justify-content-center">
                <div class="col-lg-8">
                    <div class="room-details-head">
                        <div>
                            <h2 class="title">{{ __($roomType->name) }}</h2>
                            <div class="d-flex justify-content-center flex-wrap gap-3">
                                <span>
                                    @lang('Adult') &nbsp; {{ $roomType->total_adult }}
                                </span>

                                <span>
                                    @lang('Child') &nbsp; {{ $roomType->total_child }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <h2 class="text--base fare">{{ __($general->cur_sym) }}{{ showAmount($roomType->fare) }}</h2>
                            <span class="text--base text-sm">/@lang('night')</span>
                        </div>
                    </div>

                    <div class="room-details-thumb-slider">
                        @foreach ($roomType->images as $roomTypeImage)
                            <div class="single-slide">
                                <div class="room-details-thumb">
                                    <img src="{{ getImage(getFilePath('roomTypeImage') . '/' . $roomTypeImage->image, getFileSize('roomTypeImage')) }}" alt="image">
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if ($roomType->images->count() > 1)
                        <div class="room-details-nav-slider mt-4">
                            @foreach ($roomType->images as $roomTypeImage)
                                <div class="single-slide">
                                    <div class="room-details-nav-thumb">
                                        <img src="{{ getImage(getFilePath('roomTypeImage') . '/' . $roomTypeImage->image, getFileSize('roomTypeImage')) }}"
                                             alt="image">
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="room-details-card mt-4">
                        <h5 class="title">@lang('Description')</h5>

                        <div class="body">
                            @php
                                echo $roomType->description;
                            @endphp
                        </div>
                    </div>

                    @if ($roomType->amenities)
                        <div class="room-details-card mt-4">
                            <h5 class="title">@lang('Amenities')</h5>

                            <div class="body">
                                <div class="d-inline-flex flex-md-row flex-column gap-md-5 flex-wrap gap-3">
                                    @foreach ($roomType->amenities as $amenity)
                                        <span class="me-2">
                                            @php echo $amenity->icon @endphp
                                            {{ __($amenity->title) }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif


                    @if ($roomType->complements)
                        <div class="room-details-card mt-4">
                            <h5 class="title">@lang('Complements')</h5>
                            <div class="body">
                                <div class="d-inline-flex flex-md-row flex-column gap-md-5 flex-wrap gap-3">
                                    @foreach ($roomType->complements as $complement)
                                        <span class="me-2">
                                            <i class="las la-check-double"></i>
                                            {{ __($complement->name) }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($roomType->beds)
                        <div class="room-details-card mt-4">
                            <h5 class="title">@lang('Beds')</h5>
                            <div class="body">
                                <div class="d-inline-flex flex-md-row flex-column gap-md-5 flex-wrap gap-3">
                                    @foreach ($roomType->beds as $bed)
                                        <span class="me-2"><i class="las la-check-double"></i>{{ __($bed) }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="col-lg-4">
                    <input type="text" name="room_type_id" form="confirmation-form" value="{{ $roomType->id }}" hidden>
                    <div class="room-booking-sidebar">
                        <div class="room-booking-widget">
                            <div class="room-booking-widget__body mt-0">

                                <div class="mb-3">
                                    <label class="fw-bold">@lang('Check-In')</label>
                                    <div class="custom-icon-field">
                                        <input type="text" name="check_in" form="confirmation-form" data-range="false" data-multiple-dates-separator=" - " data-language="en" class="datepicker-here form--control" data-date-format="mm/dd/yyyy" data-position='top left' placeholder="@lang('Month/Date/Year')" autocomplete="off">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="fw-bold">@lang('Check-Out')</label>
                                    <div class="custom-icon-field">
                                        <input type="text" name="check_out" form="confirmation-form" data-range="false" data-multiple-dates-separator=" - " data-language="en" class="datepicker-here form--control" data-date-format="mm/dd/yyyy" data-position='top left' placeholder="@lang('Month/Date/Year')" autocomplete="off">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                    <div class="bookingLimitationMsg text--warning"></div>
                                </div>


                                <div class="mb-3">
                                    <label class="fw-bold">@lang('Rooms')</label>
                                    <input type="number" name="number_of_rooms" form="confirmation-form" class="form--control" placeholder="@lang('Number of Rooms')" required>
                                </div>

                                <div class="room-booking-widget__body">
                                    <ul class="room-booking-widget-list"></ul>
                                    <button type="button" class="btn btn--base w-100 confirmationBtn" data-action="{{ route('booking.request') }}" data-question="@lang('Are your sure, you want to book this room?')">@lang('SEND BOOKING REQUEST')</button>
                                </div>

                            </div><!-- room-booking-widget end -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- room details section end -->
    <x-confirmation-modal></x-confirmation-modal>
@endsection

@push('style')
    <style>
        #confirmationModal button {
            padding: 0.375rem 0.625rem;
            font-size: 0.875rem;
        }
    </style>
@endpush

@push('script-lib')
    <script src="{{ asset('assets/global/js/vendor/datepicker.min.js') }}"></script>
    <script src="{{ asset('assets/global/js/vendor/datepicker.en.js') }}"></script>
@endpush

@push('script')
    <script>
        "use strict";
        let maxRoomBookingLimit = 0;
        let btnRequest = $('.confirmationBtn');
        btnRequest.attr('disabled', true);

        $('.datepicker-here').on('focusout', function(e) {
            e.preventDefault();

            let data = {};

            data.check_in = $('input[name=check_in]').val();
            data.check_out = $('input[name=check_out]').val();
            data.room_type_id = $('input[name=room_type_id]').val();

            $('[name=number_of_rooms]').val('');

            if (!data.check_in || !data.check_out) {
                return;
            }

            $.ajax({
                type: "get",
                url: "{{ route('room.available.search') }}",
                data: data,
                success: function(response) {
                    let messageBox = $('.bookingLimitationMsg');
                    if (response.success) {
                        maxRoomBookingLimit = response.success;
                        messageBox.text(`@lang('You can book maximum ${response.success} rooms')`);
                        btnRequest.removeAttr('disabled');
                    } else {
                        notify('error', response.error);
                        messageBox.empty();
                        btnRequest.attr('disabled', true);
                        $('input[name=check_in]').val('');
                        $('input[name=check_out]').val('');
                    }
                }
            });
        });

        $('[name=number_of_rooms]').on('input', function() {
            $('.confirmationBtn').attr('disabled', false);
            if ($(this).val() > maxRoomBookingLimit) {
                btnRequest.attr('disabled', true);
                notify('error', "Number of rooms can't be greater than maximum allowed room");
            }
        });
    </script>
@endpush


@push('style')
    <style>
        .main-wrapper {
            background-color: #fafafa
        }
    </style>
@endpush
