@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card b-radius--10">
                <div class="card-body p-0">
                    <div class="table-responsive--sm table-responsive">
                        <table class="table--light style--two table">
                            <thead>
                                <tr>
                                    <th>@lang('Booking No.')</th>
                                    <th>@lang('User')</th>
                                    <th>@lang('Amount')</th>
                                    <th>@lang('Issued By')</th>
                                    <th>@lang('Date')</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse($paymentLog as $log)
                                    <tr>
                                        <td data-label="@lang('Booking No.')">
                                            <span class="fw-bold">{{ @$log->booking->booking_number }}</span>
                                        </td>

                                        <td data-label="@lang('User')">
                                            @if (@$log->booking->user_id)
                                                {{ __($log->booking->user->fullname) }}
                                                <br>
                                                <span class="small">
                                                    <a href="{{ route('admin.users.detail', $log->booking->user_id) }}"><span>@</span>{{ $log->booking->user->username }}</a>
                                                </span>
                                            @else
                                                {{ __($log->booking->guest_details->name) }}
                                                <br>
                                                <span class="small fw-bold">{{ $log->booking->guest_details->email }}</span>
                                            @endif
                                        </td>

                                        <td data-label="@lang('Amount')">
                                            <span class="fw-bold">{{ showAmount($log->amount) }} {{ __($general->cur_text) }}</span>
                                        </td>

                                        <td data-label="@lang('Issued By')">
                                            @if ($log->receptionist_id)
                                                <a href="{{ route('admin.receptionist.all') }}?search={{ $log->receptionist->name }}">{{ __($log->receptionist->name) }}</a>
                                            @elseif($log->admin_id)
                                                {{ __($log->admin->name) }}
                                            @else
                                                <span class="text--cyan">@lang('Direct Payment')</span>
                                            @endif
                                        </td>

                                        <td data-label="@lang('Date')">
                                            {{ showDateTime($log->created_at) }} <br>
                                            {{ diffForHumans($log->created_at) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td>
                                    </tr>
                                @endforelse

                            </tbody>
                        </table><!-- table end -->
                    </div>
                </div>
                @if ($paymentLog->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($paymentLog) }}
                    </div>
                @endif
            </div><!-- card end -->
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <div class="d-flex justify-content-end gap-2">
        <form action="" method="GET" class="form-search d-flex justify-content-between gap-2">
            <div class="input-group">
                <input type="text" name="search" class="form-control bg--white" placeholder="@lang('User / Booking No.')" value="{{ request()->search }}">
                <button class="btn btn--primary input-group-text" type="submit"><i class="fa fa-search"></i></button>
            </div>
        </form>
    </div>
@endpush

@push('script')
    <script>
        "use strict";

        $('[name=type]').on('change', function() {
            $('.form-search').submit();
        })

        @if (request()->type)
            let type = @json(request()->type);
            $(`[name=type] option[value="${type}"]`).prop('selected', true);
        @endif
    </script>
@endpush
