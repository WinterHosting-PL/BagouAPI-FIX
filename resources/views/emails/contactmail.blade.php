<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bagou450 Mail contact</title>
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
        Bagou450 Contact
    </h2>
    <p>Dear Romain,</p>
    <p>We have received a request from a customer here are the details:</p>
    <p>First Name: {{$firstname}}</p>
    <p>Last Name: {{$lastname}}</p>
    <p>Email: <a href="mailto:{{$email}}">{{$email}}</a></p>
    <p>Phone: {{$phone}}</p>
    <p>Society: {{$society}}</p>
    <p>Message: {!! nl2br($messages) !!}</p>

    <p>Kind regards,</p>
    <p>The Bagou450 Team</p>
</div>
<footer>
    <p>For more information or to contact us, please visit our <a href="https://bagou450.com">website</a>.</p>
    <p>Thank you for choosing us!</p>

</footer>
</body>

</html>
