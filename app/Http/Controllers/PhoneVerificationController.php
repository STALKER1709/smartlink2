<?php

namespace App\Http\Controllers;

use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PhoneVerificationController extends Controller
{
    public function __construct(private readonly OtpService $otpService) {}

    public function show(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasVerifiedPhone()) {
            return redirect()->route('dashboard');
        }

        return view('phone-verification.show');
    }

    public function send(Request $request): RedirectResponse
    {
        $user = $request->user();

        $this->otpService->send($user->phone);

        return back()->with('status', __('ui.phone_verification.resend_success', ['phone' => $user->phone]));
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        $user = $request->user();

        if (! $this->otpService->verify($user->phone, $request->input('code'))) {
            return back()->withErrors(['code' => __('ui.phone_verification.invalid')]);
        }

        $user->markPhoneAsVerified();

        return redirect()->route('dashboard')->with('status', __('ui.phone_verification.success'));
    }
}
