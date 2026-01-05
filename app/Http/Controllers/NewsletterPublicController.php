<?php

namespace App\Http\Controllers;

use App\Models\Newsletter;
use Illuminate\Http\RedirectResponse;

class NewsletterPublicController extends Controller
{
    public function verify(string $token): RedirectResponse
    {
        $subscription = Newsletter::where('verify_token', $token)->first();

        if (! $subscription) {
            return redirect()->route('client.home.index')
                ->with('error', 'Liên kết xác nhận không hợp lệ hoặc đã được sử dụng.');
        }

        if ($subscription->status === Newsletter::STATUS_SUBSCRIBED && $subscription->verified_at) {
            return redirect()->route('client.home.index')
                ->with('info', 'Email của bạn đã được xác nhận trước đó, không cần xác nhận lại.');
        }

        if ($subscription->status === Newsletter::STATUS_UNSUBSCRIBED) {
            return redirect()->route('client.home.index')
                ->with('error', 'Email này đã hủy đăng ký nhận bản tin.');
        }

        $subscription->status = Newsletter::STATUS_SUBSCRIBED;
        $subscription->verified_at = now();
        $subscription->is_verified = true;
        $subscription->save();

        return redirect()->route('client.home.index')
            ->with('success', 'Bạn đã xác nhận đăng ký nhận bản tin thành công.');
    }

    public function unsubscribe(string $token): RedirectResponse
    {
        $subscription = Newsletter::where('verify_token', $token)->first();

        if (! $subscription) {
            return redirect()->route('client.home.index')
                ->with('error', 'Liên kết hủy đăng ký không hợp lệ hoặc đã được sử dụng.');
        }

        $subscription->status = Newsletter::STATUS_UNSUBSCRIBED;
        $subscription->unsubscribed_at = now();
        $subscription->save();

        return redirect()->route('client.home.index')
            ->with('success', 'Bạn đã hủy đăng ký nhận bản tin thành công.');
    }
}
