<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bagou450 - Ticket Status Updated</title>
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
        <h2>Bagou450 - Ticket Status Updated</h2>
        <p>{{ $ticket->user ? 'Dear ' . $ticket->user->name . ',' : 'Hello,' }}</p>
        <p>The status of your ticket (#{{ $ticket->id }} - {{ $ticket->name }}) has been updated.</p>
        <p>You can view your ticket by clicking the button below:</p>
        <a href="https://bagou450.com/account/tickets/{{ $ticket->id }}" target="_blank">View Ticket</a>
    </div>
    <p style="text-align: center; color: black;">Thank you for choosing us!</p>
    <footer>
        <p>For more information or to contact us, please visit our <a href="https://bagou450.com">website</a>.</p>
    </footer>
</div>
</body>
</html>
