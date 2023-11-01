<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bagou450 - New udpate of {{$productName}}</title>
    <style>
        body {
            font-family: sans-serif;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 30px;
            background-color: #F5F5F5;
            border: 2px solid #0000FF;
        }

        .content {
            padding: 20px;
            text-align: center;
        }

        h2 {
            color: #0072c6;
            text-align: center;
        }

        a {
            color: #0072c6;
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
    </style>
</head>
<body>
<div class="container">
    <div class="content">
        <h2>Bagou450 - New udpate of {{$productName}}</h2>
        <p style="color: black;">Dear {{$username}},</p>
        <p style="color: black;">We are writing to inform you that a new update has been released for {{$productName}}.  <br/>Please find the product link below:</p>

        <a href="https://bagou450.com/product/{{$productId}}" class="button">
            Download update
        </a>

        <p style="color: black;">If you have any questions or require further assistance, please do not hesitate to contact us.</p>
        <p style="color: black;">Kind regards,</p>
        <p style="color: black;">Bagou450 Team</p>
    </div>
    <p style="text-align: center; color: black;">Thank you for choosing us!</p>
    <footer>
        <p style="color: black;">For more information or to contact us, please visit our <a href="https://bagou450.com">website</a>.</p>
    </footer>
</div>
</body>
</html>
