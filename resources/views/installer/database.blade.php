@extends('meraki::installer.layout')
@php($currentStep = 2)

@section('content')
<h1>Cài đặt Database</h1>
<p class="subtitle">Wizard sẽ chạy migrations để thiết lập cấu trúc database.</p>

@if(isset($error))
    <div class="alert alert-error">{{ $error }}</div>
@endif

@if($dbCheck)
    <div class="alert alert-success">Kết nối database thành công.</div>
@else
    <div class="alert alert-error">Không thể kết nối database. Vui lòng kiểm tra cấu hình <code>.env</code> (DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD) rồi tải lại trang.</div>
@endif

<form method="POST" action="{{ route('meraki.install.database') }}">
    @csrf
    <div class="actions">
        <a href="{{ route('meraki.install.environment') }}" class="btn btn-secondary">← Quay lại</a>
        <button type="submit" class="btn" @if(!$dbCheck) disabled @endif>
            Chạy Migrations →
        </button>
    </div>
</form>
@endsection
