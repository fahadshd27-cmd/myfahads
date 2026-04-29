<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Services\DepositService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminPaymentSettingsController extends Controller
{
    public function show(Request $request): View
    {
        return view('admin.payment-settings', [
            'mode' => AppSetting::getString('payments.mode', DepositService::MODE_TESTING),
            'pending_expiry_minutes' => AppSetting::getInt('payments.pending_expiry_minutes', 30),
            'paylink_checkout_url_template' => AppSetting::getString('payments.paylink.checkout_url_template', ''),
            'paylink_webhook_secret' => AppSetting::getString('payments.paylink.webhook_secret', ''),
            'bitpay_checkout_url_template' => AppSetting::getString('payments.bitpay.checkout_url_template', ''),
            'bitpay_webhook_secret' => AppSetting::getString('payments.bitpay.webhook_secret', ''),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'mode' => ['required', 'in:testing,production'],
            'pending_expiry_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'paylink_checkout_url_template' => ['nullable', 'string', 'max:2000'],
            'paylink_webhook_secret' => ['nullable', 'string', 'max:500'],
            'bitpay_checkout_url_template' => ['nullable', 'string', 'max:2000'],
            'bitpay_webhook_secret' => ['nullable', 'string', 'max:500'],
        ]);

        AppSetting::putString('payments.mode', $data['mode']);
        AppSetting::putString('payments.pending_expiry_minutes', (string) $data['pending_expiry_minutes']);
        AppSetting::putString('payments.paylink.checkout_url_template', $data['paylink_checkout_url_template'] ?? '');
        AppSetting::putString('payments.paylink.webhook_secret', $data['paylink_webhook_secret'] ?? '');
        AppSetting::putString('payments.bitpay.checkout_url_template', $data['bitpay_checkout_url_template'] ?? '');
        AppSetting::putString('payments.bitpay.webhook_secret', $data['bitpay_webhook_secret'] ?? '');

        return back()->with('status', 'Saved');
    }
}
