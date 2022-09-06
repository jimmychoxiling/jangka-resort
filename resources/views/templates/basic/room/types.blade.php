@extends($activeTemplate . 'layouts.frontend')
@section('content')
    <section class="section">
        <div class="container">
            <div class="row gy-4 justify-content-center">
                @forelse ($roomTypes as $type)
                    <div class="col-xl-4 col-md-6 col-xs-10">
                        <div class="room-card">
                            <div class="room-card__thumb">
                                <img src="{{ getImage(getFilePath('roomTypeImage') . '/' . @$type->images->first()->image, getFileSize('roomTypeImage')) }}"
                                     alt="image">
                                <ul class="room-card__utilities">
                                    @foreach ($type->amenities->take(4) as $amenity)
                                        <li data-bs-toggle="tooltip" data-bs-placement="right"
                                            title="{{ $amenity->title }}">
                                            @php echo $amenity->icon  @endphp
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                            <div class="room-card__content">
                                <h3 class="title mb-2"><a
                                       href="{{ route('room.type.details', [$type->id, slug($type->name)]) }}">{{ __($type->name) }}</a>
                                </h3>
                                <div class="room-card__bottom justify-content-between align-items-center mt-2 gap-3">
                                    <div>
                                        <h6 class="price text--base mb-3">
                                            {{ showAmount($type->fare) }}
                                            {{ $general->cur_text }} / @lang('Night')
                                        </h6>

                                        <div class="room-capacity text--base d-flex align-items-center flex-wrap gap-3">
                                            <span class="custom--badge">
                                                @lang('Adult') &nbsp; {{ $type->total_adult }}
                                            </span>
                                            <span class="custom--badge"">
                                                @lang('Child') &nbsp; {{ $type->total_child }}
                                            </span>
                                            <a href="{{ route('room.type.details', [$type->id, slug($type->name)]) }}"
                                               class="btn btn-sm btn--base"><i
                                                   class="la la-desktop me-2"></i>@lang('DETAILS')</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-md-9">
                        <div class="card custom--card border-0">
                            <div class="card-body empty-message">
                                <i class="la la-lg la-10x la-frown text--warning"></i>
                                <span class="text--muted mt-3">{{ __($emptyMessage) }}</span>
                            </div>
                        </div>
                    </div>
                @endforelse
            </div>

        </div>
    </section>
@endsection

@push('style')
    <style>
        .empty-message {
            text-align: center;
        }

        .empty-message span {
            font-size: 25px;
            display: block;
        }
    </style>
@endpush
