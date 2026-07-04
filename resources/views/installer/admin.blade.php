@extends('meraki::installer.layout')
@php($currentStep = 3)

@section('content')
<h1>Tạo tài khoản Admin</h1>
<p class="subtitle">Thiết lập tài khoản quản trị đầu tiên để đăng nhập sau khi cài đặt.</p>

@if($errors->any())
    <div class="alert alert-error">
        <ul style="list-style:none;">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('meraki.install.admin') }}">
    @csrf
    <div class="form-group">
        <label for="name">Họ tên</label>
        <input type="text" id="name" name="name" value="{{ old('name') }}" required>
    </div>
    <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" required>
    </div>
    <div class="form-group">
        <label for="password">Mật khẩu</label>
        <input type="password" id="password" name="password" required>
    </div>
    <div class="form-group">
        <label for="password_confirmation">Xác nhận mật khẩu</label>
        <input type="password" id="password_confirmation" name="password_confirmation" required>
    </div>
    <div class="actions">
        <a href="{{ route('meraki.install.database') }}" class="btn btn-secondary">← Quay lại</a>
        <button type="submit" class="btn">Tiếp theo →</button>
    </div>
</form>
@endsection
