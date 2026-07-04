@extends('meraki::installer.layout')
@php($currentStep = 1)

@section('content')
<h1>Kiểm tra môi trường</h1>
<p class="subtitle">Xác minh server của bạn đáp ứng các yêu cầu.</p>

@php($allPass = collect($checks)->every(fn($c) => $c['pass']))

@if($allPass)
    <div class="alert alert-success">Tất cả kiểm tra đều qua. Bạn có thể tiếp tục.</div>
@else
    <div class="alert alert-warning">Một số kiểm tra chưa qua. Bạn vẫn có thể tiếp tục nhưng app có thể không hoạt động đúng.</div>
@endif

<ul class="check-list">
    @foreach($checks as $check)
    <li>
        <span class="badge {{ $check['pass'] ? 'badge-pass' : 'badge-fail' }}">{{ $check['pass'] ? 'OK' : 'FAIL' }}</span>
        <div>
            <strong>{{ $check['label'] }}</strong>
            <div style="color:#6b7280;font-size:.8rem;">{{ $check['detail'] }}</div>
        </div>
    </li>
    @endforeach
</ul>

<form method="POST" action="{{ route('meraki.install.environment') }}">
    @csrf
    <div class="actions">
        <a href="{{ route('meraki.install.welcome') }}" class="btn btn-secondary">← Quay lại</a>
        <button type="submit" class="btn">Tiếp theo →</button>
    </div>
</form>
@endsection
