<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:30px 0;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.10);">

      <!-- Brand Header -->
      <tr><td style="background:linear-gradient(135deg,#ea580c,#dc2626);padding:28px 40px 24px;text-align:center;">
        <p style="color:rgba(255,255,255,.75);font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;margin:0 0 6px;">via</p>
        <h1 style="color:#fff;margin:0;font-size:28px;font-weight:900;letter-spacing:-0.5px;">innlaunch</h1>
        <p style="color:rgba(255,255,255,.8);margin:4px 0 0;font-size:13px;">Job Board &amp; Research Platform</p>
      </td></tr>

      <!-- Sub-header -->
      <tr><td style="background:#fff7ed;border-bottom:2px solid #fed7aa;padding:16px 40px;text-align:center;">
        <p style="margin:0;color:#c2410c;font-size:15px;font-weight:700;">📩 New Job Application Received</p>
        <p style="margin:4px 0 0;color:#9a3412;font-size:13px;">Someone applied to <strong>"{{ $job->title }}"</strong> at <strong>{{ $job->company_name }}</strong></p>
      </td></tr>

      <!-- Body -->
      <tr><td style="padding:32px 40px;">
        <p style="color:#374151;font-size:15px;margin:0 0 24px;">
          Hi <strong>{{ $job->user->first_name ?? 'there' }}</strong>, you have a new applicant for your job listing on innlaunch.
        </p>

        <!-- Verified Member Card -->
        <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;margin:0 0 24px;">
          <tr><td style="padding:20px 24px;">

            <!-- Verified badge row -->
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:14px;">
              <tr>
                <td>
                  <h3 style="color:#15803d;font-size:13px;font-weight:700;margin:0;text-transform:uppercase;letter-spacing:.5px;">Applicant Details</h3>
                </td>
                <td align="right">
                  <span style="display:inline-block;background:#dcfce7;border:1px solid #86efac;color:#166534;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;letter-spacing:.3px;">
                    ✓ Verified innlaunch Member
                  </span>
                </td>
              </tr>
            </table>

            <table width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td style="color:#6b7280;font-size:13px;padding:5px 0;width:35%;">Full Name</td>
                <td style="color:#111827;font-size:13px;font-weight:700;">{{ $applicant->first_name }} {{ $applicant->last_name }}</td>
              </tr>
              <tr>
                <td style="color:#6b7280;font-size:13px;padding:5px 0;">Email</td>
                <td style="color:#111827;font-size:13px;font-weight:600;">{{ $applicant->email }}</td>
              </tr>
              <tr>
                <td style="color:#6b7280;font-size:13px;padding:5px 0;">Member Since</td>
                <td style="color:#111827;font-size:13px;">{{ $applicant->created_at ? \Carbon\Carbon::parse($applicant->created_at)->format('M Y') : 'innlaunch Member' }}</td>
              </tr>
              <tr>
                <td style="color:#6b7280;font-size:13px;padding:5px 0;">Public Profile</td>
                <td style="font-size:13px;">
                  <a href="{{ $profileUrl }}" style="color:#16a34a;font-weight:700;text-decoration:none;">View Profile on innlaunch →</a>
                </td>
              </tr>
            </table>
          </td></tr>
        </table>

        <!-- Message -->
        @if($applicantMessage)
        <table width="100%" cellpadding="0" cellspacing="0" style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;margin:0 0 28px;">
          <tr><td style="padding:20px 24px;">
            <h3 style="color:#374151;font-size:13px;font-weight:700;margin:0 0 10px;text-transform:uppercase;letter-spacing:.5px;">Message from Applicant</h3>
            <p style="color:#4b5563;font-size:14px;line-height:1.75;margin:0;white-space:pre-line;">{{ $applicantMessage }}</p>
          </td></tr>
        </table>
        @endif

        <!-- CTA -->
        <table width="100%" cellpadding="0" cellspacing="0">
          <tr>
            <td>
              <a href="{{ $profileUrl }}"
                 style="display:inline-block;background:linear-gradient(135deg,#ea580c,#dc2626);color:#fff;padding:13px 30px;border-radius:9px;font-size:14px;font-weight:700;text-decoration:none;">
                View Applicant's Profile →
              </a>
            </td>
          </tr>
        </table>

        <p style="color:#9ca3af;font-size:12px;margin:20px 0 0;line-height:1.6;">
          This application was submitted through the <strong style="color:#ea580c;">innlaunch</strong> Job Board.
          The applicant is a registered and verified member of the innlaunch platform.
        </p>
      </td></tr>

      <!-- Footer -->
      <tr><td style="background:#f9fafb;border-top:1px solid #e5e7eb;padding:20px 40px;">
        <table width="100%" cellpadding="0" cellspacing="0">
          <tr>
            <td>
              <p style="color:#6b7280;font-size:12px;margin:0;">
                <strong style="color:#ea580c;">innlaunch</strong> · Job Board &amp; Research Platform
              </p>
              <p style="color:#9ca3af;font-size:11px;margin:4px 0 0;">
                <a href="{{ config('app.url') }}/careers" style="color:#ea580c;text-decoration:none;">Browse all jobs</a>
                &nbsp;·&nbsp;
                <a href="{{ config('app.url') }}" style="color:#ea580c;text-decoration:none;">Visit innlaunch</a>
              </p>
            </td>
            <td align="right">
              <p style="color:#d1d5db;font-size:11px;margin:0;">© {{ date('Y') }} innlaunch</p>
            </td>
          </tr>
        </table>
      </td></tr>

    </table>
  </td></tr>
</table>
</body>
</html>