@extends('meraki::installer.layout')
@php($currentStep = 6)

@section('content')
<h1>Hoàn tất cài đặt</h1>
<p class="subtitle">Tất cả bước đã hoàn thành. Nhấn nút để kết thúc quá trình cài đặt.</p>

<div class="alert alert-success">Sẵn sàng cài đặt. Pipeline cài đặt sẽ chạy và đưa bạn vào ứng dụng.</div>

<form method="POST" action="{{ route('meraki.install.complete') }}">
    @csrf
    <div class="actions">
        <a href="{{ route('meraki.install.plugins') }}" class="btn btn-secondary">← Quay lại</a>
        <button type="submit" class="btn">Hoàn tất cài đặt →</button>
    </div>
</form>
@endsection
