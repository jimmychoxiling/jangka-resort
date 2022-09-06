@extends('receptionist.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('receptionist.room.search') }}" method="get" class="formRoomSearch">
                        <div class="d-flex justify-content-between align-items-end flex-wrap gap-2">
                            <div class="form-group flex-fill">
                                <label>@lang('Room Type')</label>
                                <select name="room_type" class="form-control" required>
                                    <option value="">@lang('Select One')</option>
                                    @foreach ($roomTypes as $type)
                                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group flex-fill">
                                <label>@lang('Check In - Check Out Date')</label>
                                <input name="date" type="text" data-range="true" data-multiple-dates-separator=" - " data-language="en" class="datepicker-here form-control bg--white" data-position='bottom left' placeholder="@lang('Select Date')" autocomplete="off" required>
                            </div>

                            <div class="form-group flex-fill">
                                <label>@lang('Room')</label>
                                <input name="rooms" class="form-control" type="text" placeholder="@lang('How many room?')" required>
                            </div>

                            <div class="form-group flex-fill">
                                <button type="submit" class="btn btn--primary w-100 h-45"><i class="la la-search"></i>@lang('Search')</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="row booking-wrapper d-none">
        <div class="col-lg-8 mt-3">
            <div class="card">
                <div class="card-header">
                    <div class="card-title d-flex justify-content-between booking-info-title mb-0">
                        <h5>@lang('Booking Information')</h5>
                    </div>
                </div>
                <div class="card-body">
                    <div class="pb-3">
                        <span class="fas fa-circle text--danger" disabled></span>
                        <span class="mr-5">@lang('Booked')</span>
                        <span class="fas fa-circle text--success"></span>
                        <span class="mr-5">@lang('Selected')</span>
                        <span class="fas fa-circle text--primary"></span>
                        <span>@lang('Available')</span>
                    </div>
                    <div class="alert alert-info room-assign-alert p-3" role="alert">
                    </div>
                    <div class="bookingInfo">

                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mt-3">
            <div class="card">
                <div class="card-header">
                    <div class="card-title mb-0">
                        <h5>@lang('Book Room')</h5>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('receptionist.room.book') }}" method="POST" class="booking-form"
                          id="booking-form">
                        @csrf
                        <div class="row">
                            <div class="form-group flex-fill">
                                <label>@lang('Guest Type')</label>
                                <select name="guest_type" class="form-control">
                                    <option value="0" selected>@lang('Walk-In Guest')</option>
                                    <option value="1">@lang('Existing Guest')</option>

                                </select>
                            </div>

                            <div class="form-group guestInputDiv">
                                <label>@lang('Name')</label>
                                <input type="text" class="form-control forGuest" name="guest_name" required>
                            </div>

                            <div class="form-group">
                                <label>@lang('Email')</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>

                            <div class="form-group guestInputDiv">
                                <label>@lang('Phone Number')</label>
                                <input type="number" class="form-control forGuest" name="mobile" required>
                            </div>

                            <div class="orderList d-none">
                                <ul class="list-group list-group-flush orderItem">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <h6>@lang('Room')</h6>
                                        <h6>@lang('Days')</h6>
                                        <span>
                                            <h6>@lang('Fare')</h6>
                                        </span>
                                        <span>
                                            <h6>@lang('Sub Total')</h6>
                                        </span>
                                    </li>
                                </ul>
                                <div class="d-flex justify-content-between align-items-center border-top p-3">
                                    <span>@lang('Total Fare')</span>
                                    <span class="totalFare" data-amount="0"></span>
                                    <input type="text" name="total_amount" hidden>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>@lang('Amount')</label>
                                <input type="number" step="0.01" min="0" class="form-control" name="paid_amount"
                                       placeholder="@lang('Paying Amount')">
                            </div>

                            <div class="form-group">
                                <button type="button" class="btn btn--primary w-100 h-45 btn-book confirmBookingBtn">@lang('Book Now')</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div id="confirmBookingModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Confirmation Alert!')</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <p>@lang('Are you sure to book this rooms?')</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn--dark" data-bs-dismiss="modal">@lang('No')</button>
                    <button type="button" class="btn btn--primary btn-confirm"
                            data-bs-dismiss="modal">@lang('Yes')</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script-lib')
    <script src="{{ asset('assets/global/js/vendor/datepicker.min.js') }}"></script>
    <script src="{{ asset('assets/global/js/vendor/datepicker.en.js') }}"></script>
@endpush

@push('style')
    <style>
        .booking-table td {
            white-space: unset;
        }
    </style>
@endpush

@push('script')
    <script>
        "use strict";

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });


        if (!$('.datepicker-here').val()) {
            $('.datepicker-here').datepicker({
                minDate: new Date()
            });
        }

        $('[name=guest_type]').on('change', function() {
            if ($(this).val() == 1) {
                $('.guestInputDiv').addClass('d-none');
                $('.forGuest').attr("required", false);
            } else {
                $('.guestInputDiv').removeClass('d-none');
                $('.forGuest').attr("required", true);
            }
        });

        $('.formRoomSearch').on('submit', function(e) {
            e.preventDefault();
            let formData = $(this).serialize();
            let url = $(this).attr('action');

            $.ajax({
                type: "get",
                url: url,
                data: formData,
                success: function(response) {
                    $('.bookingInfo').html('');
                    $('.booking-wrapper').addClass('d-none');
                    if (response.error) {
                        $.each(response.error, function(key, value) {
                            notify('error', value);
                        });
                    } else if (response.html.error) {
                        $.each(response.html.error, function(key, value) {
                            notify('error', value);
                        });
                    } else {
                        $('.bookingInfo').html(response.html);
                        $('.booking-wrapper').removeClass('d-none');
                    }
                },
                processData: false,
                contentType: false,
            });
        });

        $(document).on('click', '.confirmBookingBtn', function() {
            var modal = $('#confirmBookingModal');
            modal.modal('show');
        });

        $('.btn-confirm').on('click', function() {
            $('.booking-form').submit();
        });

        $('.booking-form').on('submit', function(e) {
            e.preventDefault();
            let formData = $(this).serialize();
            let url = $(this).attr('action');
            $.ajax({
                type: "POST",
                url: url,
                data: formData,
                success: function(response) {
                    if (response.success) {
                        notify('success', response.success);
                        $('.bookingInfo').html('');
                        $('.booking-wrapper').addClass('d-none');
                        $(document).find('.orderListItem').remove();
                        $('.orderList').addClass('d-none');
                        $('.formRoomSearch').trigger('reset');
                    } else {
                        notify('error', response.error);
                    }
                },
            });
        })
    </script>
@endpush
