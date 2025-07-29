@extends('emails.layouts.base')

@section('content')
    <h2>Welcome to Hive AI Phone, {{ $user->name }}! 🎉</h2>
    
    <p>We're thrilled to have you join our community of voice AI enthusiasts. You're now part of a platform that's revolutionizing how people interact with artificial intelligence through voice.</p>
    
    <div class="card success-card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">🎯 What's Next?</h3>
        <p>To get started with your voice agent platform, please verify your email address by clicking the button below. This ensures your account is secure and you can access all features.</p>
    </div>
    
    <div class="text-center mb-30">
        <a href="{{ $verificationUrl }}" class="cta-button">
            Verify Email Address
        </a>
    </div>
    
    <div class="card info-card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">⏰ Important</h3>
        <p>This verification link will expire in <strong>60 minutes</strong> for security reasons. If you don't verify within this time, you can request a new verification link from your dashboard.</p>
    </div>
    
    <h3 style="color: #2d3748; margin: 30px 0 20px 0;">🚀 What You Can Do With Hive AI Phone</h3>
    
    <ul class="feature-list">
        <li>Create intelligent voice assistants</li>
        <li>Customize AI personalities and responses</li>
        <li>Integrate with your favorite platforms</li>
        <li>Access advanced AI capabilities</li>
        <li>Get 24/7 customer support</li>
        <li>Enjoy regular updates and new features</li>
    </ul>
    
    <div class="card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">💡 Quick Start Guide</h3>
        <p><strong>Step 1:</strong> Verify your email (click the button above)</p>
        <p><strong>Step 2:</strong> Complete your profile setup</p>
        <p><strong>Step 3:</strong> Choose a subscription plan</p>
        <p><strong>Step 4:</strong> Create your first voice assistant</p>
    </div>
    
    <div class="text-center mt-30">
        <a href="{{ config('app.url') }}/dashboard" class="secondary-button">
            Access Dashboard
        </a>
        <a href="{{ config('app.url') }}/support" class="secondary-button">
            Get Help
        </a>
    </div>
    
    <p style="margin-top: 30px; font-size: 14px; color: #6c757d;">
        <strong>Note:</strong> If you did not create an account with Hive AI Phone, please ignore this email. No further action is required.
    </p>
    
    <p style="margin-top: 20px; font-size: 12px; color: #6c757d;">
        By using our service, you agree to our <a href="{{ config('app.url') }}/terms" style="color: #667eea;">Terms of Service</a> and <a href="{{ config('app.url') }}/privacy" style="color: #667eea;">Privacy Policy</a>.
    </p>
@endsection 