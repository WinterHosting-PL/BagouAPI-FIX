<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bagou450 Authentication</title>
    <style>
        body {
            font-family: sans-serif;
            line-height: 1.5;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 30px;
            background-color: #F5F5F5;
            border: 2px solid #0000FF;
        }

        a.button {
            background-color: #0072c6;
            color: #ffffff;
            text-decoration: none;
            padding: 12px 24px;
            margin: 20px 0;
            display: inline-block;
            font-weight: bold;
            border-radius: 4px;
        }

        footer {
            text-align: center;
            padding: 10px;
        }

        footer a {
            color: #0072c6;
            text-decoration: none;
        }
        footer p {
            word-break: break-word;
        }
    </style>
</head>

<body>
<div class="container">
    <h2 style="color: #0072c6; text-align: center;">
        Bagou450 authentication
    </h2>
    <p>Dear user,</p>
    <p>We have received a request to add a new Passkey to your account. Please click the button below to proceed:</p>
    <a href="https://privatewebsite.bagou450.com/login/addkey/{{$passkey_token}}" class="button">
        Add new Passkey
    </a>
    <p>If you are unable to click the button above, please copy and paste the following link into your browser:</p>
    <p>https://privatewebsite.bagou450.com/login/addkey/{{$passkey_token}}</p>
    <p>If you did not make this request, you can ignore this email. <br/>If you have any questions or need assistance, please do not hesitate to contact us.</p>
    <p>Kind regards,</p>
    <p>The Bagou450 Team</p>
</div>
<footer>
    <p>For more information or to contact us, please visit our <a href="https://bagou450.com">website</a>.</p>
    <p>Thank you for choosing us!</p>

</footer>
</body>

</html>
