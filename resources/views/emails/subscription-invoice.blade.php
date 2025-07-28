<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - {{ $package->name }} Subscription</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content {
            padding: 30px;
        }
        .invoice-details {
            background-color: #f8f9fa;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .invoice-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .invoice-row:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 18px;
            color: #28a745;
        }
        .label {
            font-weight: 500;
            color: #6c757d;
        }
        .value {
            font-weight: 600;
        }
        .status-badge {
            background-color: #28a745;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .package-details {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 20px;
            margin: 20px 0;
            border-radius: 0 6px 6px 0;
        }
        .features-list {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }
        .features-list li {
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .features-list li:before {
            content: "✓";
            color: #28a745;
            font-weight: bold;
            margin-right: 10px;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            margin: 20px 0;
            text-align: center;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">XpartFone</div>
            <h1>Invoice</h1>
            <p>Thank you for your subscription!</p>
        </div>

        <div class="content">
            <h2>Hello {{ $user->name }}!</h2>
            <p>Thank you for subscribing to <strong>{{ $package->name }}</strong>. Your payment has been processed successfully and your subscription is now active.</p>

            <div class="invoice-details">
                <h3>Invoice Details</h3>
                <div class="invoice-row">
                    <span class="label">Invoice Number:</span>
                    <span class="value">{{ $invoiceNumber }}</span>
                </div>
                <div class="invoice-row">
                    <span class="label">Invoice Date:</span>
                    <span class="value">{{ $invoiceDate }}</span>
                </div>
                <div class="invoice-row">
                    <span class="label">Due Date:</span>
                    <span class="value">{{ $dueDate }}</span>
                </div>
                <div class="invoice-row">
                    <span class="label">Payment Status:</span>
                    <span class="value"><span class="status-badge">PAID</span></span>
                </div>
                <div class="invoice-row">
                    <span class="label">Payment Method:</span>
                    <span class="value">Credit Card</span>
                </div>
                <div class="invoice-row">
                    <span class="label">Amount:</span>
                    <span class="value">${{ number_format($amount, 2) }}</span>
                </div>
            </div>

            <div class="package-details">
                <h3>Package Details</h3>
                <p><strong>{{ $package->name }}</strong></p>
                <p>{{ $package->description }}</p>
                <p><strong>Billing Period:</strong> {{ $periodStart }} to {{ $periodEnd }}</p>
            </div>

            <h3>What's included in your plan:</h3>
            <ul class="features-list">
                <li>{{ $package->assistant_limit }} Voice Assistants</li>
                <li>Advanced AI capabilities</li>
                <li>24/7 Customer Support</li>
                <li>Regular updates and new features</li>
                <li>Secure and reliable platform</li>
                <li>Easy-to-use interface</li>
            </ul>

            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ config('app.url') }}/dashboard" class="cta-button">
                    Access Your Dashboard
                </a>
            </div>

            <p>Your subscription is now active and you can start creating voice assistants immediately. If you have any questions about your invoice or subscription, please don't hesitate to contact our support team.</p>

            <p>Thank you for choosing XpartFone!</p>
        </div>

        <div class="footer">
            <p><strong>XpartFone Team</strong></p>
            <p>This is an automated invoice. Please do not reply to this email.</p>
            <p>For support, contact us at support@xpartfone.com</p>
        </div>
    </div>
</body>
</html> 