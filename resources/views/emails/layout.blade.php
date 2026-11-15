<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>{{ $titulo }}</title></head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Inter,Arial,sans-serif;color:#1f2937;line-height:1.6">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:24px 0">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:8px;overflow:hidden">
    <tr><td style="background:#101828;padding:20px 32px;text-align:center">
        <span style="font-family:'Space Grotesk',Arial,sans-serif;font-size:22px;font-weight:700;letter-spacing:.5px">
            <span style="color:#ffffff">Minha</span><span style="color:#FFD60A">Tropa</span>
        </span>
    </td></tr>
    <tr><td style="padding:32px">
        <h1 style="margin:0 0 16px;font-size:20px;font-family:'Space Grotesk',Arial,sans-serif;color:#101828">{{ $titulo }}</h1>
        <div style="font-size:15px">{!! $blocos !!}</div>
        @if ($botaoTexto && $botaoUrl)
        <p style="text-align:center;margin:28px 0"><a href="{{ $botaoUrl }}" style="display:inline-block;background:#FFD60A;color:#101828;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;font-size:15px">{{ $botaoTexto }}</a></p>
        @endif
        @if ($rodape)
        <p style="font-size:13px;color:#6b7280;margin-top:24px">{{ $rodape }}</p>
        @endif
    </td></tr>
    <tr><td style="background:#f9fafb;padding:16px 32px;text-align:center;font-size:12px;color:#9ca3af">
        MinhaTropa &mdash; Gestão inteligente para Haras<br>
        <a href="{{ url('/') }}" style="color:#6b7280">minhatropa.com.br</a>
    </td></tr>
</table>
</td></tr>
</table>
</body>
</html>
