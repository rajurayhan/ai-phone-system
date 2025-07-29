@extends('emails.layouts.base')

@section('content')
    <h2>Subscription Cancelled 📋</h2>
    
    <p>Hello <strong>{{ $user->name }}</strong>, we're sorry to see you go. Your subscription has been cancelled as requested.</p>
    
    <div class="card warning-card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">📅 What Happens Next</h3>
        <p>Your subscription will remain active until <strong>{{ $endDate }}</strong>. After that, you'll lose access to premium features but can still access your account and basic features.</p>
    </div>
    
    <div class="card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">📊 Subscription Details</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
            <div>
                <strong>Current Plan:</strong><br>
                <span style="color: #4a5568;">{{ $package->name }}</span>
            </div>
            <div>
                <strong>Access Until:</strong><br>
                <span style="color: #4a5568;">{{ $endDate }}</span>
            </div>
            <div>
                <strong>Cancellation Date:</strong><br>
                <span style="color: #4a5568;">{{ $cancelledDate }}</span>
            </div>
            <div>
                <strong>Account Status:</strong><br>
                <span style="color: #ed8936; font-weight: bold;">⚠️ Cancelled</span>
            </div>
        </div>
    </div>
    
    <h3 style="color: #2d3748; margin: 30px 0 20px 0;">💾 Your Data</h3>
    
    <ul class="feature-list">
        <li>Your voice assistants will be preserved</li>
        <li>You can still access your account</li>
        <li>Your data will be kept for 30 days</li>
        <li>You can reactivate anytime</li>
    </ul>
    
    <div class="text-center mb-30">
        <a href="{{ config('app.url') }}/dashboard" class="cta-button">
            🚀 Access Dashboard
        </a>
    </div>
    
    <div class="card info-card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">🔄 Want to Reactivate?</h3>
        <p>You can reactivate your subscription anytime from your dashboard. Simply choose a plan and your access will be restored immediately.</p>
    </div>
    
    <div class="text-center mt-30">
        <a href="{{ config('app.url') }}/subscriptions" class="secondary-button">
            📦 View Plans
        </a>
        <a href="{{ config('app.url') }}/support" class="secondary-button">
            📞 Contact Support
        </a>
    </div>
    
    <p style="margin-top: 30px; color: #4a5568;">
        We'd love to hear your feedback about your experience with HiveAIPhone. Your input helps us improve our platform for all users.
    </p>
    
    <p style="margin-top: 20px; font-size: 14px; color: #6c757d;">
        <strong>Thank you for being part of our community!</strong> We hope to see you again soon.
    </p>
    
    <p style="margin-top: 20px; font-size: 12px; color: #6c757d;">
        By using our service, you agree to our <a href="{{ config('app.url') }}/terms" style="color: #667eea;">Terms of Service</a> and <a href="{{ config('app.url') }}/privacy" style="color: #667eea;">Privacy Policy</a>.
    </p>
@endsection 