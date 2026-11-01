<!DOCTYPE html>
<html lang="pt-BR">
<body style="margin:0;padding:24px 0;background:{{ $background }};font-family:{!! $font !!};color:#27272a;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:8px;overflow:hidden;">
@foreach ($blocks as $block)
@if ($block['type'] === 'spacer')
<tr><td style="height:{{ $block['height'] }}px;line-height:{{ $block['height'] }}px;font-size:1px;">&nbsp;</td></tr>
@else
<tr><td style="padding:12px 24px;text-align:{{ $block['align'] ?? 'left' }};">
@switch($block['type'])
    @case('title')
        <h1 style="margin:0;font-size:{{ $block['size'] }}px;line-height:1.3;color:{{ $color }};">{{ $block['text'] }}</h1>
        @break
    @case('text')
        <div style="font-size:16px;line-height:1.6;">{!! $block['html'] !!}</div>
        @break
    @case('image')
        @php($src = $preview ? $block['url'] : $message->embed($block['path']))
        @if ($block['link'])<a href="{{ $block['link'] }}">@endif
        <img src="{{ $src }}" alt="" style="width:{{ $block['width'] }};max-width:100%;height:auto;border:0;border-radius:{{ $block['radius'] }}px;">
        @if ($block['link'])</a>@endif
        @break
    @case('button')
        <a href="{{ $block['url'] }}" style="display:inline-block;padding:12px 28px;border-radius:{{ $block['radius'] }}px;text-decoration:none;font-weight:bold;{{ $block['style'] === 'outline' ? 'border:2px solid '.$color.';color:'.$color.';background:#ffffff;' : 'background:'.$color.';color:#ffffff;' }}">{{ $block['label'] }}</a>
        @break
    @case('footer')
        <p style="margin:0;font-size:12px;line-height:1.5;color:#71717a;">{{ $block['text'] }}</p>
        @break
    @default
        <hr style="border:0;border-top:1px solid #e4e4e7;margin:0;">
@endswitch
</td></tr>
@endif
@endforeach
</table>
</td></tr>
</table>
</body>
</html>
