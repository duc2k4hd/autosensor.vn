@extends('clients.layouts.master')

@section('title', $pageTitle ?? 'Hướng dẫn chọn sản phẩm')

@section('head')
    <link rel="stylesheet" href="{{ asset('clients/assets/css/wizard.css') }}">
    <meta name="robots" content="index, follow" />
    <meta name="description" content="Hướng dẫn chọn {{ strtolower($category->name) }} phù hợp với nhu cầu của bạn. Trả lời các câu hỏi để nhận gợi ý sản phẩm tốt nhất." />
@endsection

@section('content')
    <div class="autosensor_wizard_container">
        <div class="autosensor_wizard_header">
            <h1 class="autosensor_wizard_title">{{ $pageTitle }}</h1>
            <p class="autosensor_wizard_subtitle">Trả lời các câu hỏi sau để chúng tôi gợi ý sản phẩm phù hợp nhất cho bạn</p>
        </div>

        <div class="autosensor_wizard_progress">
            <div class="autosensor_wizard_progress_bar" id="wizard-progress-bar"></div>
        </div>

        <form id="wizard-form" class="autosensor_wizard_form" data-process-url="{{ route('client.wizard.process') }}">
            <input type="hidden" name="category_id" value="{{ $category->id }}">

            @include('clients.pages.wizard.questions.category', ['category' => $category])

            <div class="autosensor_wizard_actions">
                <button type="button" class="autosensor_wizard_btn autosensor_wizard_btn_prev" id="wizard-prev-btn" style="display: none;">
                    ← Quay lại
                </button>
                <button type="button" class="autosensor_wizard_btn autosensor_wizard_btn_next" id="wizard-next-btn">
                    Tiếp theo →
                </button>
                <button type="submit" class="autosensor_wizard_btn autosensor_wizard_btn_submit" id="wizard-submit-btn" style="display: none;">
                    Xem kết quả
                </button>
            </div>
        </form>

        <div id="wizard-loading" class="autosensor_wizard_loading" style="display: none;">
            <div class="autosensor_wizard_loading_spinner"></div>
            <p>Đang tìm kiếm sản phẩm phù hợp...</p>
        </div>
    </div>
@endsection

@push('js_page')
    <script src="{{ asset('clients/assets/js/main.js') }}" defer></script>
    <script src="{{ asset('clients/assets/js/wizard.js') }}" defer></script>
@endpush
