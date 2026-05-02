@extends('emails.layout')

@section('subject', 'Verify Your Email — Innlaunch')
@section('badge', 'Email Verification')

@section('content')

  {{-- Greeting --}}
  <h1 style="margin:0 0 8px;font-size:26px;font-weight:900;color:#0f172a;text-align:center;letter-spacing:-0.5px;">
    Verify your email address
  </h1>
  <p style="margin:0 0 32px;font-size:15px;color:#64748b;text-align:center;line-height:1.6;">
    Hi <strong style="color:#0f172a;">{{ $firstName }}</strong>, welcome to Innlaunch!<br>
    Click the button below to confirm your email and activate your account.
  </p>

  {{-- Divider --}}
  <div style="border-top:1px solid #f1f5f9;margin-bottom:32px;"></div>

  {{-- CTA Button --}}
  <div style="text-align:center;margin-bottom:32px;">
    <a href="{{ $url }}"
       style="display:inline-block;background-color:#dc2626;color:#ffffff;font-size:15px;font-weight:700;
              text-decoration:none;padding:14px 40px;border-radius:12px;letter-spacing:0.3px;">
      Verify Email Address
    </a>
  </div>

  {{-- Expiry note --}}
  <div style="background-color:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:16px 20px;margin-bottom:24px;">
    <p style="margin:0;font-size:13px;color:#64748b;line-height:1.5;">
      <strong style="color:#0f172a;">⏱ This link expires in 60 minutes.</strong><br>
      If the button doesn't work, copy and paste this URL into your browser:
    </p>
    <p style="margin:8px 0 0;font-size:12px;color:#94a3b8;word-break:break-all;">{{ $url }}</p>
  </div>

  <p style="margin:0;font-size:13px;color:#94a3b8;text-align:center;">
    If you didn't create an account with Innlaunch, you can ignore this email.
  </p>

@endsection