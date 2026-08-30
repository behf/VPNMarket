@extends('layouts.frontend')

@section('title', 'خوش آمدید - VPNMarket')

@push('styles')
    <link rel="stylesheet" href="{{ asset('themes/welcome/css/style.css') }}">
@endpush

@section('content')
    <div class="welcome-box" data-aos="fade-up">
        <h1>به Skyline خوش آمدید</h1>
        <p>
            برای شروع ابتدا از طریق دکمه زیر ثبت نام کنید.
        </p>
        <a href="/register" class="btn-admin-panel">
            <i class="fas fa-user-plus me-2"></i>
            ثبت نام
        </a>
    </div>
@endsection

@push('scripts')

    <script>
        AOS.init({
            duration: 800,
            once: true,
        });
    </script>
@endpush
