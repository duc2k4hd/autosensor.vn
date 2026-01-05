@extends('clients.layouts.master')







@section('head')

    <meta name="robots" content="noindex, nofollow">



    <meta name="description" content="403 - Quyền truy cập bị từ chối">



    <meta name="keywords" content="403, Quyền truy cập bị từ chối">



    <meta name="author" content="{{ renderMeta($settings->site_name ?? 'AutoSensor Việt Nam') }}">



    <meta name="viewport" content="width=device-width, initial-scale=1.0">



    <meta name="theme-color" content="#ffffff">



    <meta name="apple-mobile-web-app-capable" content="yes">



    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">



    <title>403 - Quyền truy cập bị từ chối | {{ $settings->site_name }}</title>



@endsection


@push('js_page')
    <script defer src="{{ asset('clients/assets/js/main.js') }}"></script>
@endpush




@section('content')



    <style>



        .autosensor_error_box {



            background: #ffffff;



            padding: 50px 40px;



            border-radius: 18px;



            box-shadow: 0px 8px 22px rgba(0, 0, 0, 0.08);



            max-width: 600px;



            width: 90%;



            animation: fadeIn 0.4s ease-out;



            margin: 80px auto;



            text-align: center;



        }







        .autosensor_error_code {



            font-size: 110px;



            font-weight: 800;



            margin: 0;



            line-height: 1;



            background: linear-gradient(90deg, #ff4d4f, #ff7875);



            -webkit-background-clip: text;



            -webkit-text-fill-color: transparent;



        }







        .autosensor_icon_lock {



            font-size: 60px;



            margin-bottom: 10px;



            color: #ff4d4f;



        }







        .autosensor_message {



            margin-top: 10px;



            font-size: 20px;



            font-weight: 600;



        }







        .autosensor_sub_message {



            margin-top: 8px;



            font-size: 15px;



            color: #596575;



        }







        .autosensor_btn_home {



            margin-top: 25px;



            display: inline-block;



            padding: 12px 22px;



            background: #1677ff;



            color: white;



            text-decoration: none;



            border-radius: 10px;



            font-size: 15px;



            font-weight: 600;



            transition: 0.25s ease;



        }







        .autosensor_btn_home:hover {



            background: #125fcc;



        }







        @keyframes autosensor_fadeIn {



            from { opacity: 0; transform: translateY(20px); }



            to   { opacity: 1; transform: translateY(0); }



        }



    </style>



    <div class="autosensor_error_box">



        <div class="autosensor_icon_lock">đŸ"'</div>



        <h1 class="autosensor_error_code">403</h1>







        <div class="autosensor_message">Bạn khĂ´ng cĂ³ quyền truy cập trang nĂ y</div>







        <div class="autosensor_sub_message">



            CĂ³ thể bạn Ä'Ă£ đăng xuất, khĂ´ng thuá»™c phĂ¢n quyền phĂ¹ hợp<br>



            hoặc tĂ i nguyĂªn nĂ y bị giá»›i hạn truy cập.



        </div>







        <a href="{{ route('client.home.index') }}" class="autosensor_btn_home">⬅ Quay về trang chủ</a>



    </div>



@endsection

