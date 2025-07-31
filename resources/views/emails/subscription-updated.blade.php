@extends('emails.layouts.base')

@section('content')
    <h2>Subscription Updated 📈</h2>
    
    <p>Hello <strong>{{ $user->name }}</strong>, your subscription has been successfully updated!</p>
    
    <div class="card success-card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">✅ Update Confirmed</h3>
        <p>Your subscription has been updated to <strong>{{ $newPackage->name }}</strong>. The changes are now active on your account.</p>
    </div>
    
    <div class="card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">📊 Plan Comparison</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div style="background: #f7fafc; padding: 15px; border-radius: 8px; border: 2px solid #e2e8f0;">
                <h4 style="color: #2d3748; margin-bottom: 10px;">Previous Plan</h4>
                <p style="color: #4a5568; font-weight: bold;">{{ $oldPackage->name }}</p>
                <p style="color: #6c757d; font-size: 14px;">{{ $oldPackage->assistant_limit }} Assistants</p>
            </div>
            <div style="background: #f0fff4; padding: 15px; border-radius: 8px; border: 2px solid #48bb78;">
                <h4 style="color: #2d3748; margin-bottom: 10px;">New Plan</h4>
                <p style="color: #4a5568; font-weight: bold;">{{ $newPackage->name }}</p>
                <p style="color: #6c757d; font-size: 14px;">{{ $newPackage->assistant_limit }} Assistants</p>
            </div>
        </div>
    </div>
    
    <div class="card info-card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">💰 Billing Information</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
            <div>
                <strong>New Monthly Rate:</strong><br>
                <span style="color: #2d3748; font-size: 18px; font-weight: bold;">${{ number_format($newPackage->price, 2) }}</span>
            </div>
            <div>
                <strong>Next Billing Date:</strong><br>
                <span style="color: #4a5568;">{{ $nextBillingDate }}</span>
            </div>
            <div>
                <strong>Change Type:</strong><br>
                <span style="color: {{ $isUpgrade ? '#48bb78' : '#ed8936' }}; font-weight: bold;">
                    {{ $isUpgrade ? '⬆️ Upgrade' : '⬇️ Downgrade' }}
                </span>
            </div>
            <div>
                <strong>Effective Date:</strong><br>
                <span style="color: #4a5568;">{{ $effectiveDate }}</span>
            </div>
        </div>
    </div>
    
    <h3 style="color: #2d3748; margin: 30px 0 20px 0;">🎁 New Features Available</h3>
    
    <ul class="feature-list">
        <li>{{ $newPackage->assistant_limit }} Voice Assistants</li>
        <li>Advanced AI capabilities and natural language processing</li>
        <li>24/7 Customer Support</li>
        <li>Regular updates and new features</li>
        <li>Secure and reliable platform</li>
        <li>Easy-to-use interface</li>
        @if($isUpgrade)
            <li>Priority support and faster response times</li>
            <li>Advanced analytics and insights</li>
            <li>Custom integrations and API access</li>
        @endif
    </ul>
    
    <div class="text-center mb-30">
        <a href="{{ config('app.url') }}/dashboard" class="cta-button">
            🚀 Access Your Dashboard
        </a>
    </div>
    
    <div class="card">
        <h3 style="color: #2d3748; margin-bottom: 15px;">💡 Getting the Most Out of Your Plan</h3>
        <p><strong>Step 1:</strong> Explore your new features</p>
        <p><strong>Step 2:</strong> Create additional voice assistants</p>
        <p><strong>Step 3:</strong> Customize AI personalities</p>
        <p><strong>Step 4:</strong> Integrate with your platforms</p>
    </div>
    
    <div class="text-center mt-30">
        <a href="{{ config('app.url') }}/docs" class="secondary-button">
            📚 Documentation
        </a>
        <a href="{{ config('app.url') }}/support" class="secondary-button">
            📞 Get Support
        </a>
    </div>
    
    <p style="margin-top: 30px; color: #4a5568;">
        Thank you for choosing sulus.ai! We're excited to see what you'll create with your {{ $isUpgrade ? 'enhanced' : 'new' }} plan.
    </p>
    
    <p style="margin-top: 20px; font-size: 14px; color: #6c757d;">
        <strong>Questions?</strong> Our support team is here to help you make the most of your subscription.
    </p>
    
    <p style="margin-top: 20px; font-size: 12px; color: #6c757d;">
        By using our service, you agree to our <a href="{{ config('app.url') }}/terms" style="color: #667eea;">Terms of Service</a> and <a href="{{ config('app.url') }}/privacy" style="color: #667eea;">Privacy Policy</a>.
    </p>
@endsection 