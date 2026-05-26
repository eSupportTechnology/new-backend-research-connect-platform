<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:30px 0;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);">

      <!-- Header -->
      <tr><td style="background:linear-gradient(135deg,#ED1C24,#FCB040);padding:30px 40px;text-align:center;">
        <h1 style="color:#fff;margin:0;font-size:22px;font-weight:700;">Order Confirmed!</h1>
        <p style="color:rgba(255,255,255,.85);margin:6px 0 0;font-size:14px;">Your order has been placed successfully.</p>
      </td></tr>

      <!-- Body -->
      <tr><td style="padding:32px 40px;">
        <p style="color:#333;font-size:15px;margin:0 0 20px;">Hi <strong>{{ $order->buyer->first_name }}</strong>,</p>
        <p style="color:#555;font-size:14px;line-height:1.7;margin:0 0 24px;">
          Thank you for your purchase! Your order for <strong>{{ $item->title }}</strong> has been received and is being processed.
        </p>

        <!-- Order Summary Box -->
        <table width="100%" cellpadding="0" cellspacing="0" style="background:#f9f9f9;border:1px solid #e8e8e8;border-radius:8px;margin:0 0 24px;">
          <tr><td style="padding:20px 24px;">
            <h3 style="color:#333;font-size:14px;font-weight:700;margin:0 0 14px;text-transform:uppercase;letter-spacing:.5px;">Order Summary</h3>
            <table width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td style="color:#777;font-size:13px;padding:5px 0;">Order Reference</td>
                <td style="color:#333;font-size:13px;font-weight:600;text-align:right;">{{ $order->order_id_string }}</td>
              </tr>
              <tr>
                <td style="color:#777;font-size:13px;padding:5px 0;">Item</td>
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
                <td colspan="2" style="border-top:1px solid #e8e8e8;padding-top:10px;margin-top:6px;"></td>
              </tr>
              <tr>
                <td style="color:#333;font-size:14px;font-weight:700;padding:4px 0;">Total Amount</td>
                <td style="color:#ED1C24;font-size:15px;font-weight:700;text-align:right;">LKR {{ number_format($order->amount, 2) }}</td>
              </tr>
            </table>
          </td></tr>
        </table>

        <!-- Seller Info -->
        <p style="color:#555;font-size:13px;margin:0 0 6px;">Sold by: <strong>{{ $order->business_name ?? ($seller->first_name . ' ' . $seller->last_name) }}</strong></p>
        @if($order->delivery_deadline)
        <p style="color:#555;font-size:13px;margin:0 0 24px;">Expected delivery by: <strong>{{ \Carbon\Carbon::parse($order->delivery_deadline)->format('M d, Y') }}</strong></p>
        @endif

        <p style="color:#555;font-size:13px;line-height:1.7;margin:0;">
          You can track your order status in your <a href="#" style="color:#FCB040;font-weight:600;">profile orders</a> page.
          The seller will update your order with courier details once dispatched.
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