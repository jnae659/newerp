<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation Accepted!</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 20px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { padding: 20px 0; }
        .success-card { background: #d4edda; border: 1px solid #c3e6cb; border-left: 4px solid #28a745; padding: 20px; margin: 20px 0; border-radius: 4px; }
        .accountant-info { background: #f8f9fa; padding: 15px; border-radius: 6px; margin: 15px 0; }
        .next-steps { background: #e7f3ff; padding: 15px; border-radius: 6px; margin: 15px 0; }
        .cta-button { display: inline-block; background: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; margin: 10px 0; }
        .footer { text-align: center; color: #666; font-size: 12px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Welcome Aboard!</h1>
            <p>Your accountant has accepted the invitation!</p>
        </div>

        <div class="content">
            <div class="success-card">
                <h3>✅ Invitation Accepted</h3>
                <p><strong>{{ $accountant->name }}</strong> has accepted your invitation to collaborate on your accounting needs.</p>
            </div>

            <div class="accountant-info">
                <h4>👤 Accountant Information</h4>
                <p><strong>Name:</strong> {{ $accountant->name }}</p>
                <p><strong>Email:</strong> {{ $accountant->email }}</p>
                <p><strong>Accepted on:</strong> {{ $invitation->accepted_at ? $invitation->accepted_at->format('F j, Y \a\t g:i A') : 'Just now' }}</p>

                @if($invitation->permissions)
                    <div style="margin-top: 10px;">
                        <strong>Access Permissions:</strong>
                        <div style="margin-top: 5px;">
                            @foreach($invitation->permissions as $permission)
                                <span style="background: #e9ecef; color: #495057; padding: 2px 6px; border-radius: 3px; font-size: 11px; margin: 1px; display: inline-block;">
                                    {{ ucfirst($permission) }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div class="next-steps">
                <h4>🚀 What's Next?</h4>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>The accountant can now access your accounting data according to the granted permissions</li>
                    <li>You can manage this connection from your dashboard</li>
                    <li>Start collaborating on your accounting needs</li>
                    <li>You can adjust permissions or remove access anytime</li>
                </ul>
            </div>

            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ route('accountant-invitations.index') }}" class="cta-button">
                    Manage Accountant Connections
                </a>
                <p style="margin: 15px 0 0 0; font-size: 14px; color: #666;">
                    Visit your dashboard to view all connected accountants and manage permissions.
                </p>
            </div>

            <div style="background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; border-radius: 6px; margin: 20px 0;">
                <strong>💡 Pro Tip:</strong> Consider setting up a welcome meeting with your accountant to discuss your specific accounting needs and expectations.
            </div>
        </div>

        <div class="footer">
            <p>This email was sent by the Accounting System. Welcome to your new collaboration!</p>
            <p>&copy; {{ date('Y') }} Accounting System. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
