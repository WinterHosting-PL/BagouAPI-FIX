<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bagou450 Licensing System</title>
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

        footer {
            text-align: center;
            padding: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="content">
            <h2>Bagou450 - Licensing System</h2>
            <p style="color: black;">Dear customer,</p>
<p style="color: black;">Thank you for purchasing our products.<br/> We are delighted to provide you with the license keys that will enable you to use our products on two panels. <br/>Please find your license keys in the table below:</p>

<table style="width: 100%; border-collapse: collapse;">
    <thead>
        <tr style="background-color: #0072c6; color: #FFF;">
            <th style="padding: 10px; border: 1px solid #000;">Product Name</th>
            <th style="padding: 10px; border: 1px solid #000;">License Key</th>
        </tr>
    </thead>
    <tbody>
        @foreach($licenses as $license)
        <tr>
            <td style="padding: 10px; border: 1px solid #000;">{{$license['fullname']}}</td>
            <td style="padding: 10px; border: 1px solid #000;">{{$license['transaction']}}</td>
        </tr>
        @endforeach
    </tbody>
</table>

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
