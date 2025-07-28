@extends('emails.layouts.base')

@section('content')
    <h2>Reset Your Password 🔐</h2>
    
    <p>Hello <strong>{{ $user->name }}</strong>, we received a request to reset your password for your XpartFone account.</p>
    
    <div class="card warning-card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">⚠️ Security Notice</h3>
        <p>If you didn't request this password reset, please ignore this email. Your account is secure and no action is needed.</p>
    </div>
    
    <div class="text-center mb-30">
        <a href="{{ $resetUrl }}" class="cta-button">
            🔑 Reset Password
        </a>
    </div>
    
    <div class="card info-card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">⏰ Important</h3>
        <p>This password reset link will expire in <strong>60 minutes</strong> for security reasons. If you don't reset your password within this time, you can request a new reset link.</p>
    </div>
    
    <div class="card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">🔒 Security Tips</h3>
        <ul class="feature-list">
            <li>Choose a strong, unique password</li>
            <li>Use a combination of letters, numbers, and symbols</li>
            <li>Don't share your password with anyone</li>
            <li>Enable two-factor authentication if available</li>
            <li>Log out from shared devices</li>
        </ul>
    </div>
    
    <div class="text-center mt-30">
        <a href="{{ config('app.url') }}/login" class="secondary-button">
            📱 Login Page
        </a>
        <a href="{{ config('app.url') }}/support" class="secondary-button">
            📞 Get Help
        </a>
    </div>
    
    <p style="margin-top: 30px; font-size: 14px; color: #6c757d;">
        <strong>Need help?</strong> If you're having trouble resetting your password, contact our support team and we'll be happy to assist you.
    </p>
@endsection 