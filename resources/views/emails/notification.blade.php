<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $notification->title }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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
        .notification-icon {
            width: 60px;
            height: 60px;
            background: #667eea;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }
        .notification-title {
            font-size: 20px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 15px;
            text-align: center;
        }
        .notification-content {
            font-size: 16px;
            color: #4a5568;
            line-height: 1.6;
            margin-bottom: 25px;
        }
        .action-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px auto;
            display: block;
            width: fit-content;
            text-align: center;
            transition: transform 0.2s;
        }
        .action-button:hover {
            transform: translateY(-1px);
        }
        .footer {
            background-color: #f7fafc;
            padding: 20px 30px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 14px;
            color: #718096;
        }
        .notification-meta {
            background-color: #f7fafc;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
            font-size: 14px;
            color: #718096;
        }
        .greeting {
            margin-bottom: 20px;
            font-size: 16px;
            color: #2d3748;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>{{ config('app.name') }}</h1>
    </div>

    <div class="content">
        <div class="greeting">
            Salam {{ $user->name }},
        </div>

        @if($notification->icon)
            <div class="notification-icon">
                {{ $notification->icon }}
            </div>
        @endif

        <div class="notification-title">
            {{ $notification->title }}
        </div>

        <div class="notification-content">
            {!! nl2br(e($customContent)) !!}
        </div>

        @if($actionUrl)
            <a href="{{ $actionUrl }}" class="action-button">
                {{ $actionText }}
            </a>
        @endif

        <div class="notification-meta">
            <strong>Göndərilmə tarixi:</strong> {{ $notification->created_at->format('d.m.Y H:i') }}<br>
            <strong>Notification növü:</strong> {{ $notification->type_text }}
        </div>
    </div>

    <div class="footer">
        <p>Bu email avtomatik olaraq göndərilmişdir. Cavab verməyin.</p>
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Bütün hüquqlar qorunur.</p>
    </div>
</div>
</body>
</html>
