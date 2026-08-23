<!doctype html>
<html lang="{{ $payload['locale'] ?? 'ar' }}" dir="{{ ($payload['locale'] ?? 'ar') === 'ar' ? 'rtl' : 'ltr' }}">
<head>
 <meta charset="utf-8">
 <meta name="viewport" content="width=device-width,initial-scale=1">
 <title>{{ $payload['title'] }}</title>
</head>
<body style="margin:0;background:#f5f6f9;color:#182033;font-family:Arial,sans-serif">
 <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:28px 12px">
  <tr>
   <td align="center">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#fff;border:1px solid #e4e8ef;border-radius:14px;overflow:hidden">
     <tr><td style="height:5px;background:#dc2637"></td></tr>
     <tr>
      <td style="padding:28px">
       <div style="font-size:12px;font-weight:700;color:#697386;margin-bottom:10px">SokratCRM</div>
       <h1 style="margin:0 0 12px;font-size:22px;line-height:1.4">{{ $payload['title'] }}</h1>
       <p style="margin:0;color:#4b5568;font-size:15px;line-height:1.8">{{ $payload['body'] }}</p>
       @if (!empty($payload['action_url']))
        <p style="margin:24px 0 0">
         <a href="{{ $payload['action_url'] }}" style="display:inline-block;padding:11px 18px;border-radius:9px;background:#dc2637;color:#fff;text-decoration:none;font-weight:700">
          {{ ($payload['locale'] ?? 'ar') === 'ar' ? 'فتح في النظام' : 'Open in CRM' }}
         </a>
        </p>
       @endif
      </td>
     </tr>
    </table>
   </td>
  </tr>
 </table>
</body>
</html>
