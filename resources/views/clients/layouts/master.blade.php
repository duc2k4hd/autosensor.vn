<!DOCTYPE html>
<html lang="{{ $settings->site_language ?? 'vi' }}">
    <head>
        <meta charset="UTF-8">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        @include('clients.templates.css')
        @yield('schema')

        {{-- <script>
        const sessionToken = {!! session()->getId() !!};
        </script> --}}
        <script>
        window.imageSearchRoute = '{{ route("client.image-search.search") }}';
        window.shopIndexRoute = '{{ route("client.shop.index") }}';
        </script>
        @yield('head')
        @include('clients.templates.head')
        @stack('styles')
        <title>@yield('title')</title>
    </head>

    <body>
        {!! $settings->google_tag_body ?? '' !!}
        <div class="autosensor">
            @include('clients.pages.loading.index')
            @include('clients.templates.header')

            @yield('content')

            @include('clients.templates.footer')
        </div>
        @include('clients.templates.notice')
        @include('clients.templates.chat')
        @include('clients.templates.js')
        @yield('foot')
    </body>
</html>



