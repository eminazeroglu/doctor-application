<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xoş Gəlmisiniz!</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #333;
            text-align: center;
        }
        p {
            line-height: 1.6;
            color: #666;
        }
        .btn {
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 5px;
            text-align: center;
            margin-top: 20px;
            display: block;
        }
        .btn:hover {
            background-color: #45a049;
        }
        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #999;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="email-container">
    <h1>Xoş Gəlmisiniz, {{ $user->first_name }}!</h1>
    <p>Sistemimizə qoşulduğunuz üçün təşəkkür edirik.</p>
    <p>Zəhmət olmasa, hesabınızı təsdiqləmək üçün aşağıdakı linkə klikləyin:</p>

    <a href="{{ $verificationUrl }}/verification-email?key={{ \App\Helpers\Helper::encrypt($user->id) }}" class="btn">Hesabınızı Təsdiqləyin</a>

    <p>Əgər hər hansı sualınız varsa, bizə müraciət etməkdən çəkinməyin.</p>
    <div class="footer">
        <p>Komanda adından</p>
    </div>
</div>
</body>
</html>
