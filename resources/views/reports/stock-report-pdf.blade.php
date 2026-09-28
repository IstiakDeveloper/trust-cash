@php
    $isBn = $isBn ?? (app()->getLocale() === 'bn');
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $isBn ? 'স্টক মুভমেন্ট রিপোর্ট' : 'Stock Movement Report' }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 15px;
            font-size: 9px;
            line-height: 1.3;
            color: #000;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1.5px solid #000;
        }

        .company-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .company-details {
            color: #333;
            margin-bottom: 4px;
            font-size: 9px;
        }

        .report-title {
            font-size: 13px;
            font-weight: bold;
            margin: 4px 0 2px 0;
            text-transform: uppercase;
        }

        .date-range {
            color: #222;
            font-size: 9px;
        }

        .product-section {
            margin-bottom: 20px;
            page-break-inside: auto;
        }

        .product-header {
            background: #f0f0f0;
            padding: 6px 8px;
            border: 1px solid #444;
            margin-bottom: 8px;
        }

        .product-name {
            font-size: 11px;
            font-weight: bold;
        }

        .product-info {
            color: #333;
            font-size: 8px;
            margin-top: 2px;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .summary-table th,
        .summary-table td {
            border: 1px solid #444;
            padding: 5px;
            text-align: center;
            font-size: 8px;
        }

        .summary-table th {
            background-color: #f5f5f5;
            font-weight: normal;
            color: #333;
        }

        .summary-table td {
            font-weight: bold;
            font-size: 9px;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        table.data-table th,
        table.data-table td {
            padding: 4px 6px;
            border: 1px solid #444;
            font-size: 8px;
        }

        table.data-table th {
            background: #e8e8e8;
            font-weight: bold;
        }

        table.data-table thead tr:first-child th {
            background: #d8d8d8;
            text-align: center;
            font-size: 9px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            padding: 6px;
            font-size: 8px;
            color: #444;
            border-top: 1px solid #ccc;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ config('app.name', 'TrustCash') }}</div>
        <div class="company-details">
            Ukilpara, Naogaon Sadar, Naogaon. | Phone: (+88) 01334766435 | Email: mou.prokashon@gmail.com
        </div>
        <div class="report-title">{{ $isBn ? 'স্টক মুভমেন্ট রিপোর্ট' : 'Stock Movement Report' }}</div>
        <div class="date-range">
            {{ $isBn ? 'সময়কাল: ' : 'Period: ' }}{{ $filters['from_date'] }} {{ $isBn ? 'হতে' : 'to' }} {{ $filters['to_date'] }}
        </div>
    </div>

    @foreach ($reports as $report)
        <div class="product-section">
            <div class="product-header">
                <div class="product-name">
                    {{ $report['product']['name'] }}
                    @if(!empty($report['product']['sku']))
                        <span style="font-size: 9px; font-weight: normal; color: #444;">({{ $report['product']['sku'] }})</span>
                    @endif
                </div>
                <div class="product-info">
                    {{ $isBn ? 'ক্যাটাগরি: ' : 'Category: ' }}{{ $report['product']['category'] }} |
                    {{ $isBn ? 'ইউনিট: ' : 'Unit: ' }}{{ $report['product']['unit'] }}
                </div>
            </div>

            <!-- Summary Table -->
            <table class="summary-table">
                <thead>
                    <tr>
                        <th>{{ $isBn ? 'প্রারম্ভিক স্টক' : 'Opening Stock' }}</th>
                        <th>{{ $isBn ? 'মোট ক্রয়' : 'Total Purchased' }}</th>
                        <th>{{ $isBn ? 'মোট বিক্রয়' : 'Total Sold' }}</th>
                        <th>{{ $isBn ? 'বর্তমান স্টক' : 'Current Stock' }}</th>
                        <th>{{ $isBn ? 'গড় ক্রয়দর' : 'Average Cost' }}</th>
                        <th>{{ $isBn ? 'স্টক মূল্য' : 'Stock Value' }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ format_amount($report['summary']['opening_stock'], false, $isBn) }}</td>
                        <td>{{ format_amount($report['summary']['total_purchased'], false, $isBn) }}</td>
                        <td>{{ format_amount($report['summary']['total_sold'], false, $isBn) }}</td>
                        <td>{{ format_amount($report['summary']['current_stock'], false, $isBn) }}</td>
                        <td>{{ format_amount($report['summary']['avg_cost'], true, $isBn) }}</td>
                        <td>{{ format_amount($report['summary']['stock_value'], true, $isBn) }}</td>
                    </tr>
                </tbody>
            </table>

            @if (count($report['purchases']) > 0)
                <table class="data-table">
                    <thead>
                        <tr>
                            <th colspan="5">{{ $isBn ? 'ক্রয় ইতিহাস' : 'Purchase History' }}</th>
                        </tr>
                        <tr>
                            <th style="width: 20%;">{{ $isBn ? 'তারিখ' : 'Date' }}</th>
                            <th class="text-right" style="width: 20%;">{{ $isBn ? 'পরিমাণ' : 'Quantity' }}</th>
                            <th class="text-right" style="width: 20%;">{{ $isBn ? 'একক দর' : 'Unit Cost' }}</th>
                            <th class="text-right" style="width: 20%;">{{ $isBn ? 'মোট খরচ' : 'Total Cost' }}</th>
                            <th class="text-right" style="width: 20%;">{{ $isBn ? 'অবশিষ্ট' : 'Available' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($report['purchases'] as $purchase)
                            <tr>
                                <td>
                                    {{ $isBn ? to_bangla_date(\Carbon\Carbon::parse($purchase['date'])->format('Y-m-d')) : \Carbon\Carbon::parse($purchase['date'])->format('d M, Y') }}
                                </td>
                                <td class="text-right">{{ format_amount($purchase['quantity'], false, $isBn) }}</td>
                                <td class="text-right">{{ format_amount($purchase['unit_cost'], true, $isBn) }}</td>
                                <td class="text-right">{{ format_amount($purchase['total_cost'], true, $isBn) }}</td>
                                <td class="text-right">{{ format_amount($purchase['available_quantity'], false, $isBn) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            @if (count($report['sales']) > 0)
                <table class="data-table">
                    <thead>
                        <tr>
                            <th colspan="6">{{ $isBn ? 'বিক্রয় ইতিহাস' : 'Sales History' }}</th>
                        </tr>
                        <tr>
                            <th style="width: 18%;">{{ $isBn ? 'তারিখ' : 'Date' }}</th>
                            <th style="width: 22%;">{{ $isBn ? 'ইনভয়েস নং' : 'Invoice' }}</th>
                            <th class="text-right" style="width: 15%;">{{ $isBn ? 'পরিমাণ' : 'Quantity' }}</th>
                            <th class="text-right" style="width: 15%;">{{ $isBn ? 'বিক্রয় দর' : 'Unit Price' }}</th>
                            <th class="text-right" style="width: 15%;">{{ $isBn ? 'মোট মূল্য' : 'Total' }}</th>
                            <th class="text-right" style="width: 15%;">{{ $isBn ? 'অবশিষ্ট' : 'Available' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($report['sales'] as $sale)
                            <tr>
                                <td>
                                    {{ $isBn ? to_bangla_date(\Carbon\Carbon::parse($sale['date'])->format('Y-m-d')) : \Carbon\Carbon::parse($sale['date'])->format('d M, Y') }}
                                </td>
                                <td>{{ $sale['invoice_no'] }}</td>
                                <td class="text-right">{{ format_amount($sale['quantity'], false, $isBn) }}</td>
                                <td class="text-right">{{ format_amount($sale['unit_price'], true, $isBn) }}</td>
                                <td class="text-right">{{ format_amount($sale['total'], true, $isBn) }}</td>
                                <td class="text-right">{{ format_amount($sale['available_quantity'], false, $isBn) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @endforeach

    <div class="footer">
        {{ $isBn ? 'প্রস্তুতকরণ সময়: ' . to_bangla_date(now()->format('Y-m-d')) . ' ' . to_bangla_number(now()->format('h:i A')) : 'Generated on: ' . now()->format('d M, Y h:i A') }}
    </div>
</body>
</html>
