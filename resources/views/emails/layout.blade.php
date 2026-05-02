<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>@yield('subject', 'Innlaunch')</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:'Segoe UI',Arial,sans-serif;">

  <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9;padding:40px 0;">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

          {{-- ── HEADER ── --}}
          <tr>
            <td style="background-color:#0f172a;border-radius:16px 16px 0 0;padding:36px 48px;">
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td>
                    {{-- Wordmark --}}
                    <span style="font-size:26px;font-weight:900;letter-spacing:-0.5px;color:#ffffff;">
                      Inn<span style="color:#ef4444;">launch</span>
                    </span>
                  </td>
                  <td align="right">
                    {{-- Badge --}}
                    <span style="display:inline-block;background-color:rgba(239,68,68,0.15);border:1px solid rgba(239,68,68,0.3);color:#fca5a5;font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;padding:4px 10px;border-radius:20px;">
                      @yield('badge', 'Notification')
                    </span>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          {{-- ── BODY ── --}}
          <tr>
            <td style="background-color:#ffffff;padding:48px 48px 40px;">
              @yield('content')
            </td>
          </tr>

          {{-- ── FOOTER ── --}}
          <tr>
            <td style="background-color:#0f172a;border-radius:0 0 16px 16px;padding:24px 48px;">
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td>
                    <p style="margin:0;font-size:12px;color:#64748b;line-height:1.6;">
                      &copy; {{ date('Y') }} Innlaunch. All rights reserved.<br>
                      If you did not request this email, you can safely ignore it.
                    </p>
                  </td>
                  <td align="right" style="vertical-align:middle;">
                    <span style="font-size:18px;font-weight:900;color:#334155;">
                      Inn<span style="color:#ef4444;">launch</span>
                    </span>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>

</body>
</html>