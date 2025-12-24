<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation Declined</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 20px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { padding: 20px 0; }
        .decline-card { background: #f8d7da; border: 1px solid #f5c6cb; border-left: 4px solid #dc3545; padding: 20px; margin: 20px 0; border-radius: 4px; }
        .accountant-info { background: #f8f9fa; padding: 15px; border-radius: 6px; margin: 15px 0; }
        .next-steps { background: #fff3cd; padding: 15px; border-radius: 6px; margin: 15px 0; }
        .cta-button { display: inline-block; background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; margin: 10px 0; }
        .marketplace-button { display: inline-block; background: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; margin: 10px 0; }
        .footer { text-align: center; color: #666; font-size: 12px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Invitation Update</h1>
            <p>An accountant has declined your invitation</p>
        </div>

        <div class="content">
            <div class="decline-card">
                <h3>❌ Invitation Declined</h3>
                <p><strong>{{ $accountant->name }}</strong> has declined your invitation to collaborate on your accounting needs.</p>
            </div>

            <div class="accountant-info">
                <h4>👤 Accountant Information</h4>
                <p><strong>Name:</strong> {{ $accountant->name }}</p>
                <p><strong>Email:</strong> {{ $accountant->email }}</p>
                <p><strong>Response on:</strong> {{ $invitation->updated_at->format('F j, Y \a\t g:i A') }}</p>
            </div>

            <div class="next-steps">
                <h4>🔄 What You Can Do Next</h4>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li><strong>Browse the marketplace</strong> to find other qualified accountants</li>
                    <li><strong>Send new invitations</strong> to different accountants</li>
                    <li><strong>Adjust your requirements</strong> and try again with different permissions or messaging</li>
                    <li><strong>Check availability</strong> - the accountant might be fully booked</li>
                </ul>
            </div>

            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ route('accountant-marketplace') }}" class="marketplace-button" style="margin-right: 10px;">
                    Browse Marketplace
                </a>
                <a href="{{ route('accountant-invitations.create') }}" class="cta-button">
                    Send New Invitation
                </a>
                <p style="margin: 15px 0 0 0; font-size: 14px; color: #666;">
                    Find the perfect accountant match for your business needs.
                </p>
            </div>

            <div style="background: #e2e3e5; border: 1px solid #d6d8db; padding: 15px; border-radius: 6px; margin: 20px 0;">
                <strong>💡 Remember:</strong> Finding the right accountant takes time. Different accountants have different specializations and availability. Keep searching until you find the perfect match!
            </div>
        </div>

        <div class="footer">
            <p>This email was sent by the Accounting System. Don't give up - the right accountant is out there!</p>
            <p>&copy; {{ date('Y') }} Accounting System. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
