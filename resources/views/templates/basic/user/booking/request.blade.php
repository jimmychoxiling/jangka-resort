@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="table-responsive--md">
        <table class="custom--table head--base table">
            <thead>
                <tr>
                    <th>@lang('S.N.')</th>
                    <th>@lang('Room Type') | @lang('Qty')</th>
                    <th>@lang('Booked For')</th>
                    <th>@lang('Fare')</th>
                    <th>@lang('Status')</th>
                    <th>@lang('Action')</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($bookingRequests as $bookingRequest)
                    <tr>
                        <td data-label="@lang('S.N.')">
                            {{ $bookingRequests->firstItem() + $loop->index }}
                        </td>
                        <td data-label="@lang('Room Type') | @lang('Qty')">
                            {{ __($bookingRequest->roomType->name) }}
                            <br>
                            <span class="fw-bold">
                                {{ $bookingRequest->number_of_rooms }}
                            </span>
                        </td>
                        <td data-label="@lang('Booked For')">
                            {{ showDateTime($bookingRequest->check_in, 'd M, Y') }}
                            <br>
                            <span class="text--base">@lang('to')</span> {{ showDateTime($bookingRequest->check_out, 'd M, Y') }}
                        </td>
                        <td data-label="@lang('Fare')">
                            <small>
                                {{ $general->cur_sym }}{{ showAmount($bookingRequest->unit_fare) }} /@lang('Night')
                            </small>
                            <br>
                            <span class="fw-bold">
                                @lang('Total') &nbsp; {{ $general->cur_sym }}{{ showAmount($bookingRequest->total_amount) }}
                            </span>
                        </td>
                        <td data-label="@lang('Status')">
                            @php
                                echo $bookingRequest->statusBadge;
                            @endphp
                        </td>
                        <td data-label="@lang('Action')">
                            <button class="btn btn-sm btn-outline--danger confirmationBtn" data-action="{{ route('user.booking.request.delete', $bookingRequest->id) }}" data-question="@lang('Are you sure to delete this request permanently?')" @disabled($bookingRequest->status)>
                                <i class="las la-times-circle"></i> @lang('Delete')
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="text-center" colspan="100%">{{ __($emptyMessage) }}</td>
                    </tr>
                @endforelse

            </tbody>
        </table>
        @if ($bookingRequests->hasPages())
            <nav aria-label="Page navigation example">
                {{ paginateLinks($bookingRequests) }}
            </nav>
        @endif
    </div>
    <x-confirmation-modal></x-confirmation-modal>
@endsection
