<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:30px 0;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);">

      <tr><td style="background:linear-gradient(135deg,#1e3a5f,#2563eb);padding:30px 40px;text-align:center;">
        <h1 style="color:#fff;margin:0;font-size:22px;font-weight:700;">New Job Post Submitted</h1>
        <p style="color:rgba(255,255,255,.85);margin:6px 0 0;font-size:14px;">Admin action required — pending approval</p>
      </td></tr>

      <tr><td style="padding:32px 40px;">
        <p style="color:#333;font-size:15px;margin:0 0 20px;">Hello <strong>Admin</strong>,</p>
        <p style="color:#555;font-size:14px;line-height:1.7;margin:0 0 24px;">
          A new job listing has been submitted on <strong>innlaunch</strong> and requires your review.
        </p>

        <table width="100%" cellpadding="0" cellspacing="0" style="background:#f9f9f9;border:1px solid #e8e8e8;border-radius:8px;margin:0 0 24px;">
          <tr><td style="padding:20px 24px;">
            <h3 style="color:#333;font-size:14px;font-weight:700;margin:0 0 14px;text-transform:uppercase;letter-spacing:.5px;">Job Details</h3>
            <table width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td style="color:#777;font-size:13px;padding:5px 0;width:40%;">Posted By</td>
                <td style="color:#333;font-size:13px;font-weight:600;">{{ ($job->user->first_name ?? '') . ' ' . ($job->user->last_name ?? '') }} ({{ $job->user->email ?? '—' }})</td>
              </tr>
              <tr>
                <td style="color:#777;font-size:13px;padding:5px 0;">Job Title</td>
                <td style="color:#333;font-size:13px;font-weight:600;">{{ $job->title }}</td>
              </tr>
              <tr>
                <td style="color:#777;font-size:13px;padding:5px 0;">Company</td>
                <td style="color:#333;font-size:13px;font-weight:600;">{{ $job->company_name }}</td>
              </tr>
              <tr>
                <td style="color:#777;font-size:13px;padding:5px 0;">Category</td>
                <td style="color:#333;font-size:13px;font-weight:600;">{{ $job->category }}</td>
              </tr>
              <tr>
                <td style="color:#777;font-size:13px;padding:5px 0;">Location</td>
                <td style="color:#333;font-size:13px;font-weight:600;">{{ $job->location }}</td>
              </tr>
              <tr>
                <td style="color:#777;font-size:13px;padding:5px 0;">Job Type</td>
                <td style="color:#333;font-size:13px;font-weight:600;">{{ $job->job_type }}</td>
              </tr>
            </table>
          </td></tr>
        </table>

        <p style="margin:0 0 16px;">
          <a href="{{ config('app.url') }}/dashboard/super-admin/job-management"
             style="display:inline-block;background:#2563eb;color:#fff;padding:12px 28px;border-radius:8px;font-size:14px;font-weight:700;text-decoration:none;">
            Review in Admin Panel →
          </a>
        </p>
      </td></tr>

      <tr><td style="background:#f9f9f9;border-top:1px solid #eee;padding:20px 40px;text-align:center;">
        <p style="color:#aaa;font-size:12px;margin:0;">© {{ date('Y') }} innlaunch Admin System</p>
      </td></tr>

    </table>
  </td></tr>
</table>
</body>
</html>