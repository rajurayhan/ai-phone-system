@extends('emails.layouts.base')

@section('content')
    <h2>Payment Failed ❌</h2>
    
    <p>Hello <strong>{{ $user->name }}</strong>, we were unable to process your payment for your XpartFone subscription.</p>
    
    <div class="card warning-card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">⚠️ Action Required</h3>
        <p>Your subscription has been temporarily suspended due to the payment failure. Please update your payment method to restore access to your account.</p>
    </div>
    
    <div class="card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">📊 Payment Details</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
            <div>
                <strong>Plan:</strong><br>
                <span style="color: #4a5568;">{{ $package->name }}</span>
            </div>
            <div>
                <strong>Amount:</strong><br>
                <span style="color: #2d3748; font-size: 18px; font-weight: bold;">${{ number_format($amount, 2) }}</span>
            </div>
            <div>
                <strong>Failure Date:</strong><br>
                <span style="color: #4a5568;">{{ $failureDate }}</span>
            </div>
            <div>
                <strong>Status:</strong><br>
                <span style="color: #e53e3e; font-weight: bold;">❌ Failed</span>
            </div>
        </div>
    </div>
    
    <h3 style="color: #2d3748; margin: 30px 0 20px 0;">🔧 Common Payment Issues</h3>
    
    <ul class="feature-list">
        <li>Expired credit card</li>
        <li>Insufficient funds</li>
        <li>Incorrect billing information</li>
        <li>Card security restrictions</li>
        <li>Bank declined the transaction</li>
    </ul>
    
    <div class="text-center mb-30">
        <a href="{{ config('app.url') }}/billing" class="cta-button">
            🔧 Update Payment Method
        </a>
    </div>
    
    <div class="card info-card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">💾 Your Data is Safe</h3>
        <p>Don't worry - your voice assistants and account data are preserved. Once you update your payment method, you'll have immediate access to all your features.</p>
    </div>
    
    <div class="card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">📅 What Happens Next</h3>
        <p><strong>Immediate:</strong> Your subscription is suspended</p>
        <p><strong>Within 24 hours:</strong> We'll retry the payment automatically</p>
        <p><strong>After 7 days:</strong> If payment still fails, your account will be downgraded</p>
        <p><strong>After 30 days:</strong> Your account may be deactivated</p>
    </div>
    
    <div class="text-center mt-30">
        <a href="{{ config('app.url') }}/support" class="secondary-button">
            📞 Contact Support
        </a>
        <a href="{{ config('app.url') }}/billing/history" class="secondary-button">
            📋 Payment History
        </a>
    </div>
    
    <p style="margin-top: 30px; color: #4a5568;">
        If you believe this is an error or need assistance, please contact our support team. We're here to help resolve any payment issues quickly.
    </p>
    
    <p style="margin-top: 20px; font-size: 14px; color: #6c757d;">
        <strong>Need help?</strong> Our support team is available 24/7 to assist with payment issues.
    </p>
@endsection 