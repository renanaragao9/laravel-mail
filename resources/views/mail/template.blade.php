@if ($isImage)
<img src="{{ $message->embed($imagePath) }}" alt="" style="max-width:100%;height:auto;">
@else
{!! $body !!}
@endif
