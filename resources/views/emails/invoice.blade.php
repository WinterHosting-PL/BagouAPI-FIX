<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bagou450 Invoice</title>
    <style>
        @page {
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: sans-serif;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 30px;
            background-color: #F5F5F5;
            border: 2px solid #0000FF;
        }

        .row {
            display: flex;
            justify-content: space-between;
        }

        .col {
            flex-basis: 50%;
        }

        .logo img {
            width: 100%;
            max-width: 150px;
        }

        .company-info {
            text-align: right;
        }

        .invoice-details {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }

        .item-list {
            margin-top: 50px;
            width: 100%;
            border-collapse: collapse;
        }

        .item-list th, .item-list td {
            border: 1px solid #000;
            padding: 10px;
        }

        .item-list th {
            background-color: #0000FF;
            color: #FFF;
        }

        .item-list td {
            text-align: center;
        }

        .item-list tfoot td {
            font-weight: bold;
            text-align: right;
            padding-top: 20px;
        }

        .thanks {
            margin-top: 50px;
            text-align: center;
            font-style: italic;
            color: #999;
        }
        .addresses {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .address {
            white-space: pre-line;
            line-height: 3px;
        }
        .address-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        @media only screen and (max-width: 600px) {
            .container {
                padding: 10px;
            }
            .col {
                flex-basis: 100%;
            }
            .address {
                text-align: left;
                margin-bottom: 20px;
            }
            .item-list td {
                display: block;
                text-align: left;
            }
            .item-list th, .item-list td {
                text-align: left;
            }
            .item-list thead {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="row">
            <div class="col logo">
                <img src="https://cdn.bagou450.com/assets/img/logo_full_colored.png" alt="Bagou450 logo">
            </div>
            <div  class="address" style="margin-left: auto; text-align: right;">
                <p>Invoice ID: #{{$invoice_number}}</p>
                <p>Invoice date: {{$invoice_date}}</p>
                <p>Due date: {{$due_date}}</p>
            </div>
        </div>
        <div class="addresses">
    <div class="address">
        <p class="address-title">Company Address</p>
        <p>Bagou450 SARL</p>
        <p>02 rue des orchidées</p>
        <p>35450, Dourdain</p>
        <p>Bretagne, France</p>
        <p>contact@bagou450.com</p>
        <p>SIRET: 12345678901234</p> 
    </div>
    <div class="address" style="margin-left: auto; text-align: right;">
        <p class="address-title">Billing Address</p>
        <p>{{$customer['name']}}</p>
        <p>{{$customer['address']}}</p>
        <p>{{$customer['postal_code']}}, {{$customer['city']}}</p>
        <p>{{$customer['region']}}, {{$customer['country']}}</p>
        <p>{{$customer['email']}}</p>
    </div>
</div>
        <table class="item-list">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Price</th>
                    <th>License</th>

                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td>{{$item['name']}}</td>
                    <td>${{$item['price']}}</td>
                    <td>
                        @if(isset($item['license']) && $item['license'])
                            {{$item['license']}}
                        @else
                            No license
                        @endif
                    </td>

                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2">Total incl. Fees:</td>
                    <td>{{number_format(array_sum(array_column($items, 'price')), 2)}}€</td>
                </tr>

            </tfoot>
        </table>
        <p class="thanks">Thank you for choosing us!</p>
    </div>
</body>

</html>