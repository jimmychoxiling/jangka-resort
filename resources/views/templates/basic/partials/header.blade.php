@php
$contactContent = getContent('contact_us.content', true);
$socialElements = getContent('social_icon.element', false, null, true);
@endphp
<header class="header">
    <div class="header__top">
        <div class="container">
            <div class="row gy-2 align-items-center">
                <div class="col-lg-5 d-sm-block d-none">
                    <ul class="header-info-list justify-content-lg-start justify-content-center">
                        <li>
                            <a href="mailto:{{ $contactContent->data_values->email_address }}"><i class="fas fa-envelope"></i> {{ $contactContent->data_values->email_address }}</a>
                        </li>

                        <li>
                            <a href="tel:{{ $contactContent->data_values->contact_number }}"><i class="fas fa-phone-alt"></i> +{{ $contactContent->data_values->contact_number }}</a>
                        </li>
                    </ul>
                </div>
                <div class="col-lg-7">
                    <div class="header-top-right justify-content-lg-end justify-content-center">
                        <div class="header-top-action-wrapper">
                            @if ($language->count())
                                <div class="language-select">
                                    <i class="fas fa-globe"></i>
                                    <select class="langSel">
                                        @foreach ($language as $item)
                                            <option value="{{ $item->code }}" @if (session('lang') == $item->code) selected @endif>{{ __($item->name) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            @guest
                                <a href="{{ route('user.login') }}" class="header-user-btn me-3"><i class="las la-sign-in-alt"></i> @lang('Sign in')</a>
                                <a href="{{ route('user.register') }}" class="header-user-btn"><i class="las la-user"></i> @lang('Register')</a>
                            @endguest

                            @auth
                                <a href="{{ route('user.logout') }}" class="header-user-btn me-3"><i class="las la-sign-out-alt"></i> @lang('Sign Out')</a>
                            @endauth
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="header__bottom">
        <div class="container">
            <nav class="navbar navbar-expand-xl align-items-center">
                <a class="site-logo site-title" href="{{ route('home') }}">
                    <img src="{{ getImage(getFilePath('logoIcon') . '/logo.png') }}" alt="logo">
                </a>
                <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="menu-toggle"></span>
                </button>
                <div class="collapse navbar-collapse mt-lg-0 mt-3" id="navbarSupportedContent">
                    <ul class="navbar-nav main-menu ms-auto">
                        <li><a href="{{ route('home') }}" class="{{ menuActive('home') }}">@lang('HOME')</a>
                        </li>
                        @php
                            $pages = App\Models\Page::where('tempname', $activeTemplate)
                                ->where('is_default', 0)
                                ->get();
                        @endphp
                        @foreach ($pages as $data)
                            <li>
                                <a href="{{ route('pages', [$data->slug]) }}" class="@if (request()->url() == route('pages', [$data->slug])) active @endif">{{ __(strtoupper($data->name)) }}</a>
                            </li>
                        @endforeach

                        <li>
                            <a href="{{ route('blog') }}" class="{{ menuActive('blog') }}">@lang('UPDATES')</a>
                        </li>

                        <li>
                            <a href="{{ route('contact') }}" class="{{ menuActive('contact') }}">@lang('CONTACT')</a>
                        </li>
                    </ul>
                    <div class="nav-right justify-content-xl-end ps-0 ps-xl-5">
                        <a href="{{ route('room.types') }}" class="btn btn-sm btn--base me-3"><i class="las la-user me-2"></i>@lang('BOOK ONLINE')</a>
                        @auth
                            <a href="{{ route('user.home') }}" class="btn btn-sm btn-outline--base"><i class="las la-home"></i> @lang('Dashboard')</a>
                        @endauth
                    </div>

                </div>
            </nav>
        </div>
    </div>
</header>
