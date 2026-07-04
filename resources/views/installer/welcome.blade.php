@extends('meraki::installer.layout')

@section('content')
<h1>Chào mừng đến Meraki</h1>
<p class="subtitle">Wizard sẽ hướng dẫn bạn qua các bước cài đặt cần thiết. Chỉ mất vài phút.</p>

<p style="margin-bottom:1.5rem;font-size:.9rem;color:#4b5563;">Các bước sẽ thực hiện:</p>
<ol style="padding-left:1.25rem;font-size:.875rem;color:#4b5563;line-height:2;">
    <li>Kiểm tra môi trường</li>
    <li>Cài đặt database</li>
    <li>Tạo tài khoản admin <em>(nếu meraki-auth đã cài)</em></li>
    <li>Chọn plugins</li>
</ol>

<div class="actions">
    <a href="{{ route('meraki.install.environment') }}" class="btn">Bắt đầu →</a>
</div>
@endsection
