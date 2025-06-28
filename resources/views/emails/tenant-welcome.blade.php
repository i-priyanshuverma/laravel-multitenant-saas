<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; background-color: #0f172a; color: #f8fafc; padding: 30px; }
        .container { max-width: 600px; margin: 0 auto; background: #1e293b; border-radius: 12px; padding: 30px; border: 1px solid #334155; }
        .btn { display: inline-block; padding: 12px 24px; background-color: #6366f1; color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: bold; margin-top: 20px; }
        .footer { margin-top: 30px; font-size: 12px; color: #94a3b8; text-center; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Welcome to {{ $tenant->name }}! 🚀</h2>
        <p>Hello {{ $user->name }},</p>
        <p>Your multi-tenant workspace has been successfully provisioned. You can access your workspace anytime using your dedicated subdomain link below:</p>
        <p><strong>Workspace Domain:</strong> {{ $tenant->slug }}.saas.com</p>
        <p>Your 14-day free trial is now active.</p>

        <a href="{{ $workspaceUrl }}" class="btn">Access Workspace Dashboard</a>

        <div class="footer">
            <p>If you have any questions, reply directly to this email.</p>
        </div>
    </div>
</body>
</html>
