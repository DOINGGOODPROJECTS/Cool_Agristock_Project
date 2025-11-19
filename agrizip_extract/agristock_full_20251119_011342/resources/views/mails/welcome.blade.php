<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@lang('locale.welcome_mail')</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: #F9F9F9;
        }

        .container {
            width: 800px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        header {
            text-align: center;
            padding: 40px 20px;
            background: linear-gradient(to bottom, #ffffff, #F0F0F0);
            border-bottom: 2px solid #ddd;
        }

        .logo {
            width: 80px;
        }

        h1 {
            font-size: 22px;
            font-weight: bold;
            color: #0A503D;
        }

        .tagline {
            font-size: 14px;
            color: #666;
        }

        .content {
            padding: 40px 20px;
        }

        h2 {
            font-size: 18px;
            font-weight: bold;
            color: #0A503D;
            margin-bottom: 10px;
        }

        p {
            font-size: 14px;
            color: #333;
            margin-bottom: 10px;
        }

        footer {
            background: #F0F0F0;
            padding: 40px 20px;
            text-align: center;
            position: relative;
        }

        .signature {
            width: 100px;
            display: block;
            margin: 0 auto;
        }

        .name {
            font-size: 14px;
            font-weight: bold;
            color: #333;
            margin-top: 5px;
        }

        .contact-info p {
            font-size: 12px;
            color: #555;
            margin-bottom: 5px;
        }

        .graphic-design {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 60px;
            background: url('design.png') no-repeat center;
            background-size: cover;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <img src="logo.png" alt="LOGO" class="logo">
            <h1>COOL AGRISTOCK</h1>
            {{-- <p class="tagline">YOUR TAGLINE GOES HERE</p> --}}
        </header>

        <section class="content">
            <h2>{{ $user->name }}</h2>
            <p>@lang('locale.welcome_to_our_platform_text').</p>
        </section>

        <footer>
            <div class="contact-info">
                <p>Abidjan, Djorobité</p>
                <p>📞 +225 0565855202</p>
                <p>📧 info@coolagristock.com</p>
                <p>🌍 www.coolagristock.com</p>
            </div>
            <div class="graphic-design"></div>
        </footer>
    </div>
</body>
</html>


