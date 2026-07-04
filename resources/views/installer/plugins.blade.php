@extends('meraki::installer.layout')
@php($currentStep = 4)

@section('content')
<h1>Chọn Plugins</h1>
<p class="subtitle">Chọn các plugin muốn kích hoạt. Có thể thay đổi sau trong admin panel.</p>

<form method="POST" action="{{ route('meraki.install.plugins') }}">
    @csrf
    @if($plugins && count($plugins) > 0)
        <div style="margin-bottom:1.5rem;">
            @foreach($plugins as $plugin)
            <div class="plugin-item">
                <input type="checkbox" id="plugin_{{ $plugin->id() }}" name="plugins[]" value="{{ $plugin->id() }}" checked>
                <div>
                    <label for="plugin_{{ $plugin->id() }}" style="cursor:pointer;display:inline;font-size:.9rem;">
                        <strong>{{ $plugin->name() }}</strong> <span style="color:#9ca3af;font-size:.8rem;">v{{ $plugin->version() }}</span>
                    </label>
                    @if($plugin->description())
                    <div style="color:#6b7280;font-size:.8rem;">{{ $plugin->description() }}</div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    @else
        <div class="alert" style="background:#f9fafb;border:1px solid #e5e7eb;color:#6b7280;">Không có plugin nào được phát hiện. Bạn có thể cài plugin sau khi hoàn tất.</div>
    @endif

    <div class="actions">
        <a href="{{ route('meraki.install.admin') }}" class="btn btn-secondary">← Quay lại</a>
        <button type="submit" class="btn">Tiếp theo →</button>
    </div>
</form>
@endsection
