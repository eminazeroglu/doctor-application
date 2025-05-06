<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Şifrəni Sıfırla</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
            padding: 20px;
            margin: 0;
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
        }
        p {
            line-height: 1.6;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin-top: 20px;
            font-size: 16px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .btn:hover {
            background-color: #45a049;
        }
        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #999;
        }
    </style>
</head>
<body>
<div class="email-container">
    <h1>Şifrəni Sıfırlamaq</h1>
    <p>Salam,</p>
    <p>Bu mesajı, sizə göndərməyimizin səbəbi şifrənizi sıfırlamaq üçün müraciət etməyinizdir. Şifrənizi sıfırlamaq üçün aşağıdakı düyməyə klikləyin:</p>

    <a href="{{ $resetLink }}" class="btn">Şifrəni Sıfırla</a>

    <p>Əgər şifrə sıfırlamaq üçün müraciət etməmisinizsə, bu e-poçtu gözardı edə bilərsiniz.</p>

    <div class="footer">
        <p>Təşəkkür edirik,<br>Komanda adından</p>
    </div>
</div>
</body>
</html>
