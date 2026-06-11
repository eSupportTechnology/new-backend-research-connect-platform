@extends('emails.layout')

@section('subject', 'New Contact Message — Innlaunch')
@section('badge', 'Contact Form')

@section('content')

  {{-- Heading --}}
  <h1 style="margin:0 0 8px;font-size:26px;font-weight:900;color:#0f172a;text-align:center;letter-spacing:-0.5px;">
    New contact message
  </h1>
  <p style="margin:0 0 32px;font-size:15px;color:#64748b;text-align:center;line-height:1.6;">
    Someone has reached out through the Innlaunch contact form.
  </p>

  {{-- Divider --}}
  <div style="border-top:1px solid #f1f5f9;margin-bottom:32px;"></div>

  {{-- Sender details --}}
  <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
    <tr>
      <td style="padding:10px 0;border-bottom:1px solid #f1f5f9;font-size:13px;color:#94a3b8;width:120px;">Name</td>
      <td style="padding:10px 0;border-bottom:1px solid #f1f5f9;font-size:14px;color:#0f172a;font-weight:600;">{{ $contactMessage->name }}</td>
    </tr>
    <tr>
      <td style="padding:10px 0;border-bottom:1px solid #f1f5f9;font-size:13px;color:#94a3b8;">Email</td>
      <td style="padding:10px 0;border-bottom:1px solid #f1f5f9;font-size:14px;color:#0f172a;font-weight:600;">
        <a href="mailto:{{ $contactMessage->email }}" style="color:#dc2626;text-decoration:none;">{{ $contactMessage->email }}</a>
      </td>
    </tr>
    <tr>
      <td style="padding:10px 0;border-bottom:1px solid #f1f5f9;font-size:13px;color:#94a3b8;">Subject</td>
      <td style="padding:10px 0;border-bottom:1px solid #f1f5f9;font-size:14px;color:#0f172a;font-weight:600;">{{ $contactMessage->subject ?: 'General Inquiry' }}</td>
    </tr>
    <tr>
      <td style="padding:10px 0;font-size:13px;color:#94a3b8;">Received</td>
      <td style="padding:10px 0;font-size:14px;color:#0f172a;font-weight:600;">{{ $contactMessage->created_at->format('M d, Y h:i A') }}</td>
    </tr>
  </table>

  {{-- Message --}}
  <div style="background-color:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:16px 20px;margin-bottom:24px;">
    <p style="margin:0 0 6px;font-size:12px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#94a3b8;">Message</p>
    <p style="margin:0;font-size:14px;color:#334155;line-height:1.7;white-space:pre-line;">{{ $contactMessage->message }}</p>
  </div>

  <p style="margin:0;font-size:13px;color:#94a3b8;text-align:center;">
    Reply directly to this email to respond to {{ $contactMessage->name }}.
  </p>

@endsection