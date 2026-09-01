@props(['path' => null, 'disk' => 'public'])
@if(blank($path))
  <span style="color:#999">No image yet</span>
@elseif(! \Illuminate\Support\Facades\Storage::disk($disk)->exists($path))
  <span style="color:#c00">File not found: {{ $path }}</span>
@else
  @php $url = \Illuminate\Support\Facades\Storage::disk($disk)->url($path); @endphp
  <a href="{{ $url }}" target="_blank" style="display:inline-block">
    <img src="{{ $url }}" style="max-width:320px;max-height:220px;border:1px solid #ddd;border-radius:8px;object-fit:contain" loading="lazy">
  </a>
  <div style="font-size:12px;color:#666;margin-top:4px">{{ $path }}</div>
@endif
