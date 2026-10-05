@php
    $faviconSetting = \App\Models\Setting::current();
    $faviconUrl = $faviconSetting->logoUrl() ?: asset("favicon.ico");
    $faviconVersion = $faviconSetting->updated_at?->timestamp;
    $faviconHref = $faviconUrl.($faviconVersion ? "?v=".$faviconVersion : "");
@endphp
<link rel="icon" href="{{ $faviconHref }}">
<link rel="apple-touch-icon" href="{{ $faviconHref }}">
