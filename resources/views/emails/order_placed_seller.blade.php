<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:30px 0;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);">

      <!-- Header -->
      <tr><td style="background:linear-gradient(135deg,#10B981,#FCB040);padding:30px 40px;text-align:center;">
        <h1 style="color:#fff;margin:0;font-size:22px;font-weight:700;">New Order Received!</h1>
        <p style="color:rgba(255,255,255,.85);margin:6px 0 0;font-size:14px;">You have a new order waiting to be fulfilled.</p>
      </td></tr>

      <!-- Body -->
      <tr><td style="padding:32px 40px;">
        <p style="color:#333;font-size:15px;margin:0 0 20px;">Hi <strong>{{ $order->seller->first_name }}</strong>,</p>
        <p style="color:#555;font-size:14px;line-height:1.7;margin:0 0 24px;">
          Great news! <strong>{{ $buyer->first_name }} {{ $buyer->last_name }}</strong> has placed an order for your product <strong>{{ $item->title }}</strong>.
        </p>

        <!-- Order Details Box -->
        <table width="100%" cellpadding="0" cellspacing="0" style="background:#f9f9f9;border:1px solid #e8e8e8;border-radius:8px;margin:0 0 24px;">
          <tr><td style="padding:20px 24px;">
            <h3 style="color:#333;font-size:14px;font-weight:700;margin:0 0 14px;text-transform:uppercase;letter-spacing:.5px;">Order Details</h3>
            <table width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td style="color:#777;font-size:13px;padding:5px 0;">Order Reference</td>
                <td style="color:#333;font-size:13px;font-weight:600;text-align:right;">{{ $order->order_id_string }}</td>
              </tr>
              <tr>
                <td style="color:#777;font-size:13px;padding:5px 0;">Product</td>
                <td style="color:#333;font-size:13px;font-weight:600;text-align:right;">{{ $item->title }}</td>
              </tr>
              <tr>
                <td style="color:#777;font-size:13px;padding:5px 0;">Quantity</td>
                <td style="color:#333;font-size:13px;font-weight:600;text-align:right;">{{ $order->quantity }}</td>
              </tr>
              <tr>
                <td style="color:#777;font-size:13px;padding:5px 0;">Payment Method</td>
                <td style="color:#333;font-size:13px;font-weight:600;text-align:right;">{{ $order->payment_method === 'cod' ? 'Cash on Delivery' : 'Online Payment' }}</td>
              </tr>
              <tr>
                <td style="color:#777;font-size:13px;padding:5px 0;">Buyer</td>
                <td style="color:#333;font-size:13px;font-weight:600;text-align:right;">{{ $buyer->first_name }} {{ $buyer->last_name }}</td>
              </tr>
              <tr>
                <td colspan="2" style="border-top:1px solid #e8e8e8;padding-top:10px;"></td>
              </tr>
              <tr>
                <td style="color:#333;font-size:14px;font-weight:700;padding:4px 0;">Order Amount</td>
                <td style="color:#10B981;font-size:15px;font-weight:700;text-align:right;">LKR {{ number_format($order->amount, 2) }}</td>
              </tr>
            </table>
          </td></tr>
        </table>

        @if($order->delivery_deadline)
        <p style="color:#ED1C24;font-size:13px;font-weight:600;margin:0 0 16px;">
          ⚠️ Please dispatch by: {{ \Carbon\Carbon::parse($order->delivery_deadline)->format('M d, Y') }}
        </p>
        @endif

        <p style="color:#555;font-size:13px;line-height:1.7;margin:0;">
          Please log in to your dashboard to view the full order details, update courier information, and manage this delivery.
        </p>
      </td></tr>

      <!-- Footer -->
      <tr><td style="background:#f9f9f9;border-top:1px solid #eee;padding:20px 40px;text-align:center;">
        <p style="color:#aaa;font-size:12px;margin:0;">© {{ date('Y') }} Research Connect Platform. All rights reserved.</p>
      </td></tr>

    </table>
  </td></tr>
</table>
</body>
</html>