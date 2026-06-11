@extends('emails.layout')

@section('subject', 'Welcome to the Innlaunch Newsletter')
@section('badge', 'Newsletter')

@section('content')

  {{-- Heading --}}
  <h1 style="margin:0 0 8px;font-size:26px;font-weight:900;color:#0f172a;text-align:center;letter-spacing:-0.5px;">
    You're subscribed! 🎉
  </h1>
  <p style="margin:0 0 32px;font-size:15px;color:#64748b;text-align:center;line-height:1.6;">
    Thanks for subscribing to the Innlaunch newsletter.<br>
    You'll now be the first to hear about the latest innovations, research, and opportunities on our platform.
  </p>

  {{-- Divider --}}
  <div style="border-top:1px solid #f1f5f9;margin-bottom:32px;"></div>

  {{-- What to expect --}}
  <div style="background-color:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:16px 20px;margin-bottom:24px;">
    <p style="margin:0 0 6px;font-size:12px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#94a3b8;">What to expect</p>
    <p style="margin:0;font-size:14px;color:#334155;line-height:1.7;">
      &bull; New innovations and research highlights<br>
      &bull; Investor zone opportunities<br>
      &bull; Career openings and community events<br>
      &bull; Platform updates and features
    </p>
  </div>

  <p style="margin:0;font-size:13px;color:#94a3b8;text-align:center;">
    You're receiving this because {{ $subscriberEmail }} was subscribed to our newsletter on Innlaunch.<br>
    If this wasn't you, you can safely ignore this email.
  </p>

@endsection