<script defer src="{{ asset('clients/assets/js/vendor/slim_select.js') }}"></script>
<script defer src="{{ asset('clients/assets/js/vendor/embla_carousel.js') }}"></script>

@stack('js_page')

@php
    $alerts = [
        'success' => session('success'),
        'error'   => session('error'),
        'warning' => session('warning'),
        'info'    => session('info'),
    ];
@endphp

<script>
    document.addEventListener("DOMContentLoaded", function() {
        let alerts = [];
        @foreach ($alerts as $type => $message)
            @if ($message)
                alerts.push({type: '{{ $type }}', message: @json($message)});
            @endif
        @endforeach

        @if ($errors->any())
            @foreach ($errors->all() as $error)
                alerts.push({type: 'error', message: @json($error)});
            @endforeach
        @endif

        alerts.forEach(a => showCustomToast(a.message, a.type));
    });
</script>



