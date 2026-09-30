@php
    $isBn = $isBn ?? (app()->getLocale() === 'bn');
    $methodNames = [
        'cash' => $isBn ? 'নগদ' : 'Cash',
        'card' => $isBn ? 'কার্ড' : 'Card',
        'bank' => $isBn ? 'ব্যাংক স্থানান্তর' : 'Bank Transfer',
        'mobile_banking' => $isBn ? 'মোবাইল ব্যাংকিং' : 'Mobile Banking',
    ];
    $statusNames = [
        'paid' => $isBn ? 'পরিশোধিত' : 'Paid',
        'partial' => $isBn ? 'আংশিক' : 'Partial',
        'due' => $isBn ? 'বকেয়া' : 'Due',
    ];
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $isBn ? 'বিক্রয় রিপোর্ট' : 'Sales Report' }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            line-height: 1.3;
            margin: 15px;
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
            font-size: 9px;
            color: #333;
            margin-bottom: 4px;
        }

        .report-title {
            font-size: 13px;
            font-weight: bold;
            margin: 4px 0 2px 0;
            text-transform: uppercase;
        }

        .date-range {
            font-size: 9px;
            color: #222;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        th, td {
            border: 1px solid #444;
            padding: 4px 6px;
            font-size: 8px;
        }

        th {
            background-color: #f0f0f0;
            font-weight: bold;
            color: #000;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .text-left {
            text-align: left;
        }

        .month-section {
            margin-top: 15px;
            page-break-inside: auto;
        }

        .month-header {
            background: #e8e8e8;
            padding: 6px 8px;
            margin-bottom: 8px;
            font-weight: bold;
            border: 1px solid #444;
            font-size: 10px;
        }

        .payment-methods {
            margin: 6px 0;
            padding: 5px 8px;
            background: #fafafa;
            border: 1px solid #666;
            font-size: 8px;
        }

        .day-section {
            margin-top: 10px;
        }

        .day-header {
            background: #f4f4f4;
            padding: 4px 6px;
            margin-bottom: 4px;
            font-weight: bold;
            border-left: 3px solid #000;
            font-size: 9px;
        }

        .subtotal-row {
            background: #f0f0f0;
            font-weight: bold;
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
        <div class="company-name">{{ business_name() }}</div>
        @if(business_details())
        <div class="company-details">
            {{ business_details(' | ') }}
        </div>
        @endif
        <div class="report-title">{{ $isBn ? 'বিক্রয় রিপোর্ট' : 'Sales Report' }}</div>
        <div class="date-range">
            {{ $isBn ? 'সময়কাল: ' : 'Period: ' }}{{ $filters['from_date'] }} {{ $isBn ? 'হতে' : 'to' }} {{ $filters['to_date'] }}
        </div>
    </div>

    <!-- Overall Summary -->
    <table>
        <thead>
            <tr>
                <th class="text-center">{{ $isBn ? 'মোট বিক্রয়' : 'Total Sales' }}</th>
                <th class="text-right">{{ $isBn ? 'উপমোট' : 'Subtotal' }}</th>
                <th class="text-right">{{ $isBn ? 'ছাড়' : 'Discount' }}</th>
                <th class="text-right">{{ $isBn ? 'ট্যাক্স' : 'Tax' }}</th>
                <th class="text-right">{{ $isBn ? 'সর্বমোট' : 'Total Amount' }}</th>
                <th class="text-right">{{ $isBn ? 'প্রাপ্ত' : 'Received' }}</th>
                <th class="text-right">{{ $isBn ? 'বকেয়া' : 'Due' }}</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center">{{ $isBn ? to_bangla_number($summary['total_sales']) : $summary['total_sales'] }}</td>
                <td class="text-right">{{ format_amount($summary['subtotal'], true, $isBn) }}</td>
                <td class="text-right">{{ format_amount($summary['discount'], true, $isBn) }}</td>
                <td class="text-right">{{ format_amount($summary['tax'], true, $isBn) }}</td>
                <td class="text-right"><strong>{{ format_amount($summary['total_amount'], true, $isBn) }}</strong></td>
                <td class="text-right"><strong>{{ format_amount($summary['received'], true, $isBn) }}</strong></td>
                <td class="text-right"><strong>{{ format_amount($summary['due'], true, $isBn) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <!-- Monthly Reports -->
    @foreach ($monthly_reports as $report)
        <div class="month-section">
            <div class="month-header">
                {{ $report['month'] }}
                <div style="font-size: 8px; font-weight: normal; margin-top: 3px;">
                    {{ $isBn ? 'বিক্রয়: ' : 'Sales: ' }}{{ $isBn ? to_bangla_number($report['summary']['total_sales']) : $report['summary']['total_sales'] }} |
                    {{ $isBn ? 'মোট: ' : 'Amount: ' }}{{ format_amount($report['summary']['total_amount'], true, $isBn) }} |
                    {{ $isBn ? 'প্রাপ্ত: ' : 'Received: ' }}{{ format_amount($report['summary']['received'], true, $isBn) }} |
                    {{ $isBn ? 'বকেয়া: ' : 'Due: ' }}{{ format_amount($report['summary']['due'], true, $isBn) }}
                </div>
            </div>

            <!-- Daily Sales -->
            @foreach ($report['daily_sales'] as $day)
                <div class="day-section">
                    <div class="day-header">{{ $day['date'] }}</div>

                    <table>
                        <thead>
                            <tr>
                                <th style="width: 10%;">{{ $isBn ? 'সময়' : 'Time' }}</th>
                                <th style="width: 12%;">{{ $isBn ? 'ইনভয়েস নং' : 'Invoice' }}</th>
                                <th style="width: 18%;">{{ $isBn ? 'ক্রেতা' : 'Customer' }}</th>
                                <th class="text-right" style="width: 10%;">{{ $isBn ? 'মোট' : 'Total' }}</th>
                                <th class="text-right" style="width: 10%;">{{ $isBn ? 'প্রাপ্ত' : 'Paid' }}</th>
                                <th class="text-right" style="width: 10%;">{{ $isBn ? 'বকেয়া' : 'Due' }}</th>
                                <th class="text-center" style="width: 10%;">{{ $isBn ? 'অবস্থা' : 'Status' }}</th>
                                <th style="width: 20%;">{{ $isBn ? 'পেমেন্ট বিবরণ' : 'Payment Details' }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($day['sales'] as $sale)
                                <tr>
                                    <td>{{ $sale['created_at'] }}</td>
                                    <td>{{ $sale['invoice_no'] }}</td>
                                    <td>{{ $sale['customer'] }}</td>
                                    <td class="text-right">{{ format_amount($sale['total'], true, $isBn) }}</td>
                                    <td class="text-right">{{ format_amount($sale['paid'], true, $isBn) }}</td>
                                    <td class="text-right">{{ format_amount($sale['due'], true, $isBn) }}</td>
                                    <td class="text-center">
                                        {{ $statusNames[$sale['payment_status']] ?? ucfirst($sale['payment_status']) }}
                                    </td>
                                    <td>
                                        @foreach ($sale['payments'] as $payment)
                                            {{ $methodNames[$payment['method']] ?? ucfirst($payment['method']) }}:
                                            {{ format_amount($payment['amount'], true, $isBn) }}
                                            @if (!empty($payment['bank_account']))
                                                <br>
                                                <span style="color: #444; font-size: 7px;">
                                                    {{ $payment['bank_account']['name'] }}
                                                    ({{ $payment['bank_account']['account'] }})
                                                    @if ($payment['transaction_id'])
                                                        #{{ $payment['transaction_id'] }}
                                                    @endif
                                                </span>
                                            @endif
                                            @if (!$loop->last)
                                                <br>
                                            @endif
                                        @endforeach
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="subtotal-row">
                                <td colspan="3">{{ $isBn ? 'দৈনিক মোট' : 'Day Total' }}</td>
                                <td class="text-right">{{ format_amount($day['summary']['total_amount'], true, $isBn) }}</td>
                                <td class="text-right">{{ format_amount($day['summary']['received'], true, $isBn) }}</td>
                                <td class="text-right">{{ format_amount($day['summary']['due'], true, $isBn) }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endforeach

            <!-- Monthly Summary -->
            <div class="payment-methods">
                <strong>{{ $isBn ? 'মাসিক সংক্ষেপ: ' : 'Monthly Summary: ' }}</strong>
                {{ $isBn ? 'সর্বমোট: ' : 'Total: ' }}{{ format_amount($report['summary']['total_amount'], true, $isBn) }} |
                {{ $isBn ? 'প্রাপ্ত: ' : 'Received: ' }}{{ format_amount($report['summary']['received'], true, $isBn) }} |
                {{ $isBn ? 'বকেয়া: ' : 'Due: ' }}{{ format_amount($report['summary']['due'], true, $isBn) }}
            </div>
        </div>
    @endforeach

    <div class="footer">
        {{ $isBn ? 'প্রস্তুতকরণ সময়: ' . to_bangla_date(now()->format('Y-m-d')) . ' ' . to_bangla_number(now()->format('h:i A')) : 'Generated on ' . now()->format('d M, Y h:i A') }}
    </div>
</body>
</html>
