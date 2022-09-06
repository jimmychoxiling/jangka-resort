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
                                    <th>@lang('Details')</th>
                                    <th>@lang('Action By')</th>
                                    <th>@lang('Date')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($bookingLog as $log)
                                    <tr>
                                        <td data-label="@lang('Booking No.')">
                                            <span class="fw-bold">{{ @$log->booking->booking_number }}</span>
                                        </td>
                                        <td data-label="@lang('Details')">
                                            @if ($log->details)
                                                {{ __(keyToTitle($log->details)) }}
                                            @else
                                                {{ __(keyToTitle($log->remark)) }}
                                            @endif
                                        </td>

                                        <td data-label="@lang('Action By')">
                                            @if ($log->receptionist_id)
                                                <a href="{{ route('admin.receptionist.all') }}?search={{ $log->receptionist->name }}">{{ __($log->receptionist->name) }}</a>
                                            @else
                                                {{ @$log->admin->name }}
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
                @if ($bookingLog->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($bookingLog) }}
                    </div>
                @endif
            </div><!-- card end -->
        </div>


    </div>
@endsection

@push('breadcrumb-plugins')
    <form action="" method="GET" class="float-sm-end form-search">
        <div class="d-flex justify-content-between gap-2">
            <select name="remark" class="form-control">
                <option value="">@lang('Select One')</option>
                @foreach ($remarks as $remark)
                    <option value="{{ $remark->remark }}">{{ __(keyToTitle($remark->remark)) }}</option>
                @endforeach
            </select>
            <div class="input-group">
                <input type="text" name="search" class="form-control bg--white" placeholder="@lang('Booking No.')" value="{{ request()->search }}">
                <button class="btn btn--primary input-group-text" type="submit"><i class="fa fa-search"></i></button>
            </div>
        </div>
    </form>
@endpush

@push('script')
    <script>
        "use strict";

        $('[name=remark]').on('change', function() {
            $('.form-search').submit();
        })

        @if (request()->remark)
            let remark = @json(request()->remark);
            $(`[name=remark] option[value="${remark}"]`).prop('selected', true);
        @endif
    </script>
@endpush
