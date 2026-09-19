@if (app()->environment('local'))
    <!DOCTYPE html>
    <html>
    <head>
        <title>Password Reset Link</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0;
            }
            .container {
                background: white;
                padding: 40px;
                border-radius: 8px;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
                max-width: 600px;
                text-align: center;
            }
            h1 {
                color: #333;
                margin-bottom: 10px;
            }
            .info {
                color: #666;
                margin-bottom: 30px;
                line-height: 1.6;
            }
            .reset-link {
                background: #f0f0f0;
                padding: 15px;
                border-radius: 5px;
                margin-bottom: 20px;
                word-break: break-all;
                font-size: 12px;
            }
            .button {
                display: inline-block;
                padding: 12px 30px;
                background: #667eea;
                color: white;
                text-decoration: none;
                border-radius: 5px;
                font-weight: bold;
                transition: background 0.3s;
                margin: 0 10px;
            }
            .button:hover {
                background: #5568d3;
            }
            .button.secondary {
                background: #999;
            }
            .button.secondary:hover {
                background: #777;
            }
            .note {
                margin-top: 20px;
                padding-top: 20px;
                border-top: 1px solid #eee;
                font-size: 12px;
                color: #999;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>✓ Password Reset Link Generated</h1>
            <div class="info">
                A password reset link has been generated for your development environment.
            </div>
            
            <div class="reset-link">
                {{ $resetUrl }}
            </div>
            
            <a href="{{ $resetUrl }}" target="_blank" class="button">Open Reset Link</a>
            <a href="javascript:history.back()" class="button secondary">Go Back</a>
            
            <div class="note">
                <strong>Note:</strong> This reset link will expire in {{ config('auth.passwords.users.expire') }} minutes.
            </div>
        </div>

        <script>
            // Auto-open the reset link in a new tab
            window.open('{{ $resetUrl }}', '_blank');
        </script>
    </body>
    </html>
@else
    <!DOCTYPE html>
    <html>
    <head>
        <title>Password Reset</title>
    </head>
    <body>
        <h1>Password Reset Link Sent</h1>
        <p>If your email is registered, you will receive a password reset link shortly.</p>
    </body>
    </html>
@endif
