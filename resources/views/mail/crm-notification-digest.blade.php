<!doctype html>
<html lang="{{ $locale }}" dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>{{ __('crm.notification_daily_digest') }}</title></head>
<body style="margin:0;background:#f5f6f9;color:#182033;font-family:Arial,sans-serif">
 <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:28px 12px">
  <tr><td align="center">
   <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#fff;border:1px solid #e4e8ef;border-radius:14px;overflow:hidden">
    <tr><td style="height:5px;background:#dc2637"></td></tr>
    <tr><td style="padding:28px">
     <div style="font-size:12px;font-weight:700;color:#697386;margin-bottom:8px">SokratCRM</div>
     <h1 style="margin:0 0 20px;font-size:22px">{{ trans('crm.notification_daily_digest', [], $locale) }}</h1>
     @foreach ($notifications as $notification)
      <div style="padding:16px 0;border-top:1px solid #e4e8ef">
       <strong style="display:block;font-size:16px;margin-bottom:6px">{{ $notification['title'] }}</strong>
       <span style="display:block;color:#4b5568;line-height:1.7">{{ $notification['body'] }}</span>
       @if (!empty($notification['action_url']))
        <a href="{{ $notification['action_url'] }}" style="display:inline-block;margin-top:9px;color:#c92032;font-weight:700;text-decoration:none">{{ $locale === 'ar' ? 'فتح في النظام' : 'Open in CRM' }}</a>
       @endif
      </div>
     @endforeach
    </td></tr>
   </table>
  </td></tr>
 </table>
</body>
</html>
