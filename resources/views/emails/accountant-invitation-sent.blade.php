<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accountant Invitation</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 20px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { padding: 20px 0; }
        .invitation-card { background: #f8f9fa; border-left: 4px solid #007bff; padding: 20px; margin: 20px 0; border-radius: 4px; }
        .permissions { background: white; padding: 15px; border-radius: 6px; margin: 15px 0; }
        .permissions .badge { background: #e9ecef; color: #495057; padding: 4px 8px; border-radius: 4px; font-size: 12px; margin: 2px; display: inline-block; }
        .cta-button { display: inline-block; background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; margin: 10px 0; }
        .footer { text-align: center; color: #666; font-size: 12px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; }
        .company-info { background: #e3f2fd; padding: 15px; border-radius: 6px; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏢 Accountant Invitation</h1>
            <p>You've been invited to work with a company!</p>
        </div>

        <div class="content">
            <div class="company-info">
                <h3>Hello {{ $accountant->name }}!</h3>
                <p><strong>{{ $company->name }}</strong> has invited you to collaborate on their accounting needs.</p>
            </div>

            <div class="invitation-card">
                <h4>📋 Invitation Details</h4>
                <p><strong>Company:</strong> {{ $company->name }}</p>
                <p><strong>Email:</strong> {{ $company->email }}</p>
                <p><strong>Invited on:</strong> {{ $invitation->created_at->format('F j, Y \a\t g:i A') }}</p>

                @if($invitation->message)
                    <div style="background: white; padding: 15px; border-radius: 6px; margin: 15px 0; border-left: 3px solid #007bff;">
                        <strong>💬 Personal Message:</strong>
                        <p style="margin: 8px 0 0 0;">{{ $invitation->message }}</p>
                    </div>
                @endif

                <div class="permissions">
                    <strong>🔐 Access Permissions:</strong>
                    @if($invitation->permissions)
                        <div style="margin-top: 8px;">
                            @foreach($invitation->permissions as $permission)
                                <span class="badge">{{ ucfirst($permission) }}</span>
                            @endforeach
                        </div>
                    @else
                        <p style="margin: 8px 0 0 0; color: #666;">Full access permissions will be discussed upon acceptance.</p>
                    @endif
                </div>
            </div>

            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ route('accountant-invitations.pending') }}" class="cta-button">
                    View Invitation & Respond
                </a>
                <p style="margin: 15px 0 0 0; font-size: 14px; color: #666;">
                    Log in to your accountant dashboard to accept or decline this invitation.
                </p>
            </div>

            <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 6px; margin: 20px 0;">
                <strong>⚠️ Important:</strong> This invitation will expire if not responded to within 30 days.
            </div>
        </div>

        <div class="footer">
            <p>This email was sent by the Accounting System. If you have any questions, please contact support.</p>
            <p>&copy; {{ date('Y') }} Accounting System. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
