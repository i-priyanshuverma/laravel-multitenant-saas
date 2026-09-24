<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; background-color: #0f172a; color: #f8fafc; padding: 30px; }
        .container { max-width: 600px; margin: 0 auto; background: #1e293b; border-radius: 12px; padding: 30px; border: 1px solid #334155; }
        .btn { display: inline-block; padding: 12px 24px; background-color: #6366f1; color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: bold; margin-top: 20px; }
        .footer { margin-top: 30px; font-size: 12px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <h2>You're invited to join {{ $tenant?->name ?? 'a workspace' }}! 🚀</h2>
        <p>Hello,</p>
        <p>You have been invited to join the <strong>{{ $tenant?->name ?? 'workspace' }}</strong> team as a <strong>{{ ucfirst($invitation->role) }}</strong>.</p>
        <p>Click the button below to accept your invitation and join your team:</p>

        <a href="{{ $acceptUrl }}" class="btn">Accept Invitation</a>

        <div class="footer">
            <p>This invitation link will expire in 7 days. If you did not expect this invitation, you can ignore this email.</p>
        </div>
    </div>
</body>
</html>
