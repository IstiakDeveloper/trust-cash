@php
    $isBn = $isBn ?? (app()->getLocale() === 'bn');
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $isBn ? 'ব্যাংক রিপোর্ট' : 'Bank Report' }}</title>
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
            border-bottom: 1.5px solid #000;
            padding-bottom: 8px;
            margin-bottom: 12px;
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
            text-transform: uppercase;
            margin: 4px 0 2px 0;
        }

        .date-range {
            font-size: 9px;
            color: #222;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .summary-table th,
        .summary-table td {
            padding: 5px 6px;
            border: 1px solid #444;
            font-size: 8px;
        }

        .summary-table th {
            background: #f0f0f0;
            font-weight: bold;
            text-transform: uppercase;
            text-align: center;
        }

        .account-section {
            margin-top: 15px;
            page-break-inside: auto;
        }

        .account-header {
            background: #e8e8e8;
            padding: 6px 8px;
            margin-bottom: 6px;
            border: 1px solid #444;
        }

        .account-name {
            font-size: 11px;
            font-weight: bold;
        }

        .account-details {
            font-size: 8px;
            color: #333;
            margin-top: 2px;
        }

        .month-header {
            background: #f4f4f4;
            padding: 4px 6px;
            margin-top: 8px;
            margin-bottom: 4px;
            font-weight: bold;
            border-left: 3px solid #000;
            font-size: 9px;
        }

        .transactions-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
            margin-bottom: 10px;
        }

        .transactions-table th,
        .transactions-table td {
            padding: 4px 6px;
            border: 1px solid #444;
        }

        .transactions-table th {
            background: #f0f0f0;
            font-weight: bold;
        }

        .monthly-summary {
            background: #f0f0f0;
            font-weight: bold;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .amount {
            white-space: nowrap;
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

        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ config('app.name', 'TrustCash') }}</div>
        <div class="company-details">
            Ukilpara, Naogaon Sadar, Naogaon. | Phone: (+88) 01334766435 | Email: mou.prokashon@gmail.com
        </div>
        <div class="report-title">{{ $isBn ? 'ব্যাংক ব্যালেন্স রিপোর্ট' : 'Bank Balance Report' }}</div>
        <div class="date-range">
            {{ $isBn ? 'সময়কাল: ' : 'Period: ' }}{{ $date_range['from'] }} {{ $isBn ? 'হতে' : 'to' }} {{ $date_range['to'] }}
        </div>
    </div>

    <table class="summary-table">
        <thead>
            <tr>
                <th>{{ $isBn ? 'মোট অ্যাকাউন্ট' : 'Total Accounts' }}</th>
                <th>{{ $isBn ? 'মোট ব্যালেন্স' : 'Total Balance' }}</th>
                <th>{{ $isBn ? 'মোট ইনফ্লো (আদায়)' : 'Total Inflows' }}</th>
                <th>{{ $isBn ? 'মোট আউটফ্লো (খরচ)' : 'Total Outflows' }}</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center">{{ $isBn ? to_bangla_number($summary['total_accounts']) : $summary['total_accounts'] }}</td>
                <td class="text-right amount"><strong>{{ format_amount($summary['total_balance'], true, $isBn) }}</strong></td>
                <td class="text-right amount">{{ format_amount($summary['total_inflows'], true, $isBn) }}</td>
                <td class="text-right amount">{{ format_amount($summary['total_outflows'], true, $isBn) }}</td>
            </tr>
        </tbody>
    </table>

    @foreach($reports as $index => $report)
        <div class="account-section">
            @if($index > 0)
                <div class="page-break"></div>
            @endif

            <div class="account-header">
                <div class="account-name">{{ $report['account']['bank'] }} - {{ $report['account']['name'] }}</div>
                <div class="account-details">
                    {{ $isBn ? 'অ্যাকাউন্ট নং: ' : 'Account No: ' }}{{ $report['account']['number'] }} |
                    {{ $isBn ? 'প্রারম্ভিক ব্যালেন্স: ' : 'Opening Balance: ' }}{{ format_amount($report['opening_balance'], true, $isBn) }} |
                    {{ $isBn ? 'পূর্ববর্তী ব্যালেন্স: ' : 'Previous Balance: ' }}{{ format_amount($report['previous_balance'], true, $isBn) }}
                </div>
            </div>

            @foreach($report['monthly_data'] as $monthData)
                <div class="month-header">
                    {{ $monthData['month'] }}
                </div>

                <table class="transactions-table">
                    <thead>
                        <tr>
                            <th style="width: 15%;">{{ $isBn ? 'তারিখ' : 'Date' }}</th>
                            <th style="width: 35%;">{{ $isBn ? 'বিবরণ' : 'Description' }}</th>
                            <th class="text-center" style="width: 10%;">{{ $isBn ? 'ধরন' : 'Type' }}</th>
                            <th class="text-right" style="width: 13%;">{{ $isBn ? 'ইনফ্লো' : 'Inflow' }}</th>
                            <th class="text-right" style="width: 13%;">{{ $isBn ? 'আউটফ্লো' : 'Outflow' }}</th>
                            <th class="text-right" style="width: 14%;">{{ $isBn ? 'ব্যালেন্স' : 'Balance' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($monthData['transactions'] as $transaction)
                            <tr>
                                <td>
                                    {{ $isBn ? to_bangla_date(\Carbon\Carbon::parse($transaction['date'])->format('Y-m-d')) : $transaction['date'] }}
                                </td>
                                <td>{{ $transaction['description'] }}</td>
                                <td class="text-center">
                                    {{ $transaction['type'] === 'in' ? ($isBn ? 'ইনফ্লো' : 'Inflow') : ($isBn ? 'আউটফ্লো' : 'Outflow') }}
                                </td>
                                <td class="text-right amount">
                                    {{ $transaction['inflow'] > 0 ? format_amount($transaction['inflow'], true, $isBn) : '-' }}
                                </td>
                                <td class="text-right amount">
                                    {{ $transaction['outflow'] > 0 ? format_amount($transaction['outflow'], true, $isBn) : '-' }}
                                </td>
                                <td class="text-right amount">
                                    {{ format_amount($transaction['balance'], true, $isBn) }}
                                </td>
                            </tr>
                        @endforeach
                        <tr class="monthly-summary">
                            <td colspan="3">{{ $isBn ? 'মাসিক মোট' : 'Monthly Total' }}</td>
                            <td class="text-right amount">
                                {{ format_amount($monthData['summary']['inflows'], true, $isBn) }}
                            </td>
                            <td class="text-right amount">
                                {{ format_amount($monthData['summary']['outflows'], true, $isBn) }}
                            </td>
                            <td class="text-right amount">
                                <strong>{{ format_amount($monthData['summary']['ending_balance'], true, $isBn) }}</strong>
                            </td>
                        </tr>
                    </tbody>
                </table>
            @endforeach
        </div>
    @endforeach

    <div class="footer">
        {{ $isBn ? 'প্রস্তুতকরণ সময়: ' . to_bangla_date(now()->format('Y-m-d')) . ' ' . to_bangla_number(now()->format('h:i A')) : 'Generated on: ' . now()->format('d M, Y h:i A') }}
    </div>
</body>
</html>
