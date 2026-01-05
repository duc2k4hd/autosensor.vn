<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Models\Newsletter;
use App\Services\NewsletterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

class NewsletterController extends Controller
{
    public function __construct(
        protected NewsletterService $newsletterService
    ) {}

    /**
     * Xử lý đăng ký newsletter từ form
     */
    public function subscription(Request $request): RedirectResponse
    {
        $email = $request->input('xanhworld_main_newsletter_email');

        $validator = Validator::make(
            ['email' => $email],
            [
                'email' => ['required', 'email', 'max:80'],
            ],
            [
                'email.required' => 'Vui lòng nhập địa chỉ email.',
                'email.email' => 'Địa chỉ email không hợp lệ.',
                'email.max' => 'Địa chỉ email không được vượt quá 80 ký tự.',
            ]
        );

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();
        $email = $validated['email'];

        // Rate limit nhẹ theo email để tránh spam
        $rateKey = 'newsletter_email_'.sha1($email);
        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            $seconds = RateLimiter::availableIn($rateKey);

            return redirect()->back()
                ->with('error', "Bạn thao tác quá nhanh, vui lòng thử lại sau {$seconds} giây.")
                ->withInput();
        }
        RateLimiter::hit($rateKey, 3600);

        try {
            $newsletter = Newsletter::where('email', $email)->first();

            // Xác định source dựa trên route hiện tại
            $source = $this->determineSource($request);

            if (! $newsletter) {
                $newsletter = Newsletter::create([
                    'email' => $email,
                    'ip' => $request->ip(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'status' => Newsletter::STATUS_PENDING,
                    'source' => $source,
                    'verify_token' => bin2hex(random_bytes(32)),
                    'is_verified' => false,
                ]);
            } else {
                // Nếu đã hủy hoặc pending, cho phép đăng ký lại
                if ($newsletter->status === Newsletter::STATUS_SUBSCRIBED) {
                    return redirect()->back()
                        ->with('success', 'Email này đã đăng ký nhận bản tin rồi. Cảm ơn bạn!')
                        ->withInput();
                }

                $newsletter->fill([
                    'status' => Newsletter::STATUS_PENDING,
                    'verify_token' => bin2hex(random_bytes(32)),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'source' => $source,
                ])->save();
            }

            // Gửi email xác nhận
            $this->newsletterService->sendVerifyEmail($newsletter);

            return redirect()->back()
                ->with('success', 'Cảm ơn bạn đã đăng ký. Vui lòng kiểm tra email để xác nhận đăng ký nhận bản tin!');
        } catch (\Throwable $e) {
            return redirect()->back()
                ->with('error', 'Đã có lỗi xảy ra. Vui lòng thử lại sau.')
                ->withInput();
        }
    }

    /**
     * Xác định source dựa trên route hiện tại
     */
    protected function determineSource(Request $request): string
    {
        $routeName = $request->route()?->getName();

        return match ($routeName) {
            'client.home.index' => 'homepage_form',
            'client.shop.index', 'client.product.category.index' => 'shop_form',
            'client.product.detail' => 'product_detail_form',
            default => 'form',
        };
    }
}
