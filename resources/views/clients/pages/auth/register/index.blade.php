<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - {{ $settings->site_name ?? $settings->subname ?? 'AutoSensor Việt Nam' }}</title>
    <link rel="apple-touch-icon" sizes="180x180"
        href="{{ asset('/clients/assets/img/business/' . ($settings->site_favicon ?? 'favicon.png')) }}?v={{ time() }}">
    <link rel="icon" type="image/png" sizes="32x32"
        href="{{ asset('/clients/assets/img/business/' . ($settings->site_favicon ?? 'favicon.png')) }}?v={{ time() }}">
    <link rel="icon" type="image/png" sizes="16x16"
        href="{{ asset('/clients/assets/img/business/' . ($settings->site_favicon ?? 'favicon.png')) }}?v={{ time() }}">
    <link rel="mask-icon"
        href="{{ asset('clients/assets/img/business/' . ($settings->site_favicon ?? 'favicon.png')) }}?v={{ time() }}"
        color="#5bbad5">
    <link rel="icon"
        href="{{ asset('clients/assets/img/business/' . ($settings->site_favicon ?? 'favicon.png')) }}?v={{ time() }}"
        type="image/x-icon">
    <meta name="theme-color" content="#3bb77e">
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="robots" content="follow, noindex"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #0f5132 0%, #198754 50%, #20c997 100%);
        }
        
        .input-effect {
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        .input-effect:focus {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(15, 81, 50, 0.2), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            border-color: #198754;
        }
        
        .btn-hover {
            transition: all 0.3s ease;
            background-size: 200% auto;
            background-image: linear-gradient(to right, #198754 0%, #0f5132 51%, #198754 100%);
        }
        
        .btn-hover:hover {
            background-position: right center;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(15, 81, 50, 0.3);
        }
        
        .social-btn {
            transition: all 0.3s ease;
        }
        
        .social-btn:hover {
            transform: translateY(-3px) scale(1.05);
        }
        
        .floating {
            animation: floating 3s ease-in-out infinite;
        }
        
        @keyframes floating {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="max-w-6xl w-full mx-auto bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col md:flex-row">
        <!-- Left Side - Illustration -->
        <div class="w-full md:w-1/2 gradient-bg text-white p-10 flex flex-col justify-center items-center">
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold mb-2">Tham Gia Ngay Hôm Nay!</h1>
                <p class="opacity-90">Tạo tài khoản để bắt đầu mua sắm tại {{ $settings->site_name ?? $settings->subname ?? 'AutoSensor Việt Nam' }}</p>
            </div>
            
            <div class="relative w-full max-w-xs floating">
                <div class="absolute -top-10 -left-10 w-24 h-24 bg-green-300 rounded-full opacity-20"></div>
                <div class="absolute -bottom-10 -right-10 w-24 h-24 bg-emerald-300 rounded-full opacity-20"></div>
                <img src="{{ asset('clients/assets/img/business/'. ($settings->site_logo ?? 'no-image.webp')) }}" alt="Sign Up Illustration" class="relative z-10 w-full">
            </div>
            
            <div class="mt-8 text-center">
                <p class="text-sm opacity-80">Đã có tài khoản? <a href="{{ route('client.auth.login') }}" class="font-semibold underline hover:opacity-90">Đăng nhập</a></p>
            </div>
        </div>
        
        <!-- Right Side - Register Form -->
        <div class="w-full md:w-1/2 p-10 md:p-12 flex flex-col justify-center">
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-gray-800">Bắt Đầu</h2>
                <p class="text-gray-600 mt-2">Tạo tài khoản trong vài giây</p>
            </div>

            @if ($errors->any())
                <div class="mb-6 p-4 rounded-lg border border-red-200 bg-red-50 text-sm text-red-700">
                    <ul class="list-disc ml-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form class="space-y-6" action="{{ route('client.auth.register.handle') }}" method="POST">
                @csrf

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Họ và Tên</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-user text-gray-400"></i>
                        </div>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" class="input-effect w-full pl-10 pr-4 py-3 rounded-lg border border-gray-300 focus:border-green-500 focus:ring-1 focus:ring-green-500 outline-none" placeholder="Nguyễn Văn A">
                    </div>
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Địa chỉ Email</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-gray-400"></i>
                        </div>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" class="input-effect w-full pl-10 pr-4 py-3 rounded-lg border border-gray-300 focus:border-green-500 focus:ring-1 focus:ring-green-500 outline-none" placeholder="your@email.com">
                    </div>
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Mật khẩu</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input type="password" id="password" name="password" class="input-effect w-full pl-10 pr-4 py-3 rounded-lg border border-gray-300 focus:border-green-500 focus:ring-1 focus:ring-green-500 outline-none" placeholder="••••••••">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer">
                            <i class="fas fa-eye-slash text-gray-400 hover:text-gray-600" id="togglePassword"></i>
                        </div>
                    </div>
                    <div class="text-xs text-gray-500 mt-1">Sử dụng 8 ký tự trở lên bao gồm chữ, số và ký tự đặc biệt</div>
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label for="confirm-password" class="block text-sm font-medium text-gray-700 mb-1">Xác nhận Mật khẩu</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input type="password" id="confirm-password" name="password_confirmation" class="input-effect w-full pl-10 pr-4 py-3 rounded-lg border border-gray-300 focus:border-green-500 focus:ring-1 focus:ring-green-500 outline-none" placeholder="••••••••">
                    </div>
                </div>
                
                <button type="submit" class="btn-hover w-full py-3 px-4 rounded-lg text-white font-semibold transition duration-300">
                    Tạo Tài Khoản
                </button>
                
                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white text-gray-500">Hoặc tiếp tục với</span>
                    </div>
                </div>
                
                <div class="grid grid-cols-3 gap-4">
                    <a href="#" class="social-btn flex items-center justify-center py-2 px-4 border border-gray-300 rounded-lg hover:bg-gray-50">
                        <i class="fab fa-google text-red-500 text-xl"></i>
                    </a>
                    <a href="#" class="social-btn flex items-center justify-center py-2 px-4 border border-gray-300 rounded-lg hover:bg-gray-50">
                        <i class="fab fa-facebook-f text-blue-600 text-xl"></i>
                    </a>
                    <a href="#" class="social-btn flex items-center justify-center py-2 px-4 border border-gray-300 rounded-lg hover:bg-gray-50">
                        <i class="fab fa-apple text-gray-800 text-xl"></i>
                    </a>
                </div>

                <a href="{{ route('client.home.index') }}" style="display: flex; gap: 10px; color: green;"><svg xmlns="http://www.w3.org/2000/svg" width="20px" height="20px" viewBox="0 0 48 48"><script xmlns="" id="eppiocemhmnlbhjplcgkofciiegomcon"/><script xmlns=""/><script xmlns=""/>
                    <title>arrow-back-solid</title>
                    <g id="Layer_2" data-name="Layer 2">
                        <g id="invisible_box" data-name="invisible box">
                        <rect width="48" height="48" fill="none"/>
                        </g>
                        <g id="Q3_icons" data-name="Q3 icons">
                        <path d="M19.7,6a.9.9,0,0,0-.8.4L2.4,23.1a1.5,1.5,0,0,0,0,2L18.9,41.6a.9.9,0,0,0,.8.4,1.2,1.2,0,0,0,1.1-1.3V31c15.7.7,21.1,3.8,23.5,9.2.4.8.8,1.1,1.1,1.1s.6-.4.6-1c-.2-10.5-10-20.9-25.2-22.4V7.3A1.2,1.2,0,0,0,19.7,6Z"/>
                        </g>
                    </g>
                    </svg> <span>Quay lại trang chủ</span></a>
            </form>
        </div>
    </div>

    <script>
        // Toggle password visibility
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');
        
        if (togglePassword && password) {
            togglePassword.addEventListener('click', function() {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        }
        
        // Add animation to form inputs on focus
        const inputs = document.querySelectorAll('.input-effect');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.querySelector('i').classList.add('text-green-500');
            });
            input.addEventListener('blur', function() {
                this.parentElement.querySelector('i').classList.remove('text-green-500');
            });
        });
    </script>
</body>
</html>
