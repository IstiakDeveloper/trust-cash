<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bank Transaction Report</title>
    <style>
        @page {
            margin: 10mm;
            size: landscape;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 7.5pt;
            line-height: 1.2;
            color: #000;
            background: #fff;
        }

        .container {
            width: 100%;
            margin: 0 auto;
        }

        .company-header {
            text-align: center;
            margin-bottom: 8px;
            border-bottom: 2px solid #000;
            padding-bottom: 6px;
        }

        .company-name {
            font-size: 13pt;
            font-weight: bold;
            color: #000;
        }

        .company-details {
            font-size: 8pt;
            color: #333;
            margin-top: 2px;
        }

        .report-title {
            text-align: center;
            font-size: 11pt;
            font-weight: bold;
            color: #000;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .report-period {
            text-align: center;
            font-size: 8.5pt;
            color: #222;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 6.5pt;
            border: 1px solid #000;
        }

        table th,
        table td {
            padding: 3.5px 2px;
            border: 1px solid #000;
            text-align: right;
            vertical-align: middle;
            color: #000;
        }

        table th {
            font-weight: bold;
            color: #000;
            text-align: center;
            background-color: #f0f0f0;
        }

        .text-center {
            text-align: center;
        }

        .text-left {
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .bg-head-group {
            background-color: #e5e5e5;
        }

        .bg-subhead {
            background-color: #f7f7f7;
        }

        .total-col {
            background-color: #ededed;
            font-weight: bold;
        }

        .total-row {
            font-weight: bold;
            background-color: #e0e0e0;
        }

        .footer {
            position: fixed;
            bottom: 5mm;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 7pt;
            color: #444;
        }

        .page-number:before {
            content: counter(page);
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Company Header -->
        <div class="company-header">
            <div class="company-name">{{ config('app.name', 'TrustCash') }}</div>
            <div class="company-details">
                Ukilpara, Naogaon Sadar, Naogaon.<br>
                Phone: (+88) 01334766435 | Email: mou.prokashon@gmail.com
            </div>
        </div>

        <!-- Report Title and Period -->
        <div class="report-title">{{ ($isBn ?? false) ? 'ব্যাংক লেনদেন রিপোর্ট' : 'Bank Transaction Report' }}</div>
        <div class="report-period">
            <strong>{{ ($isBn ?? false) ? 'অ্যাকাউন্ট:' : 'Account:' }}</strong> {{ $bankAccount ? ($bankAccount->bank_name . ' - ' . $bankAccount->account_name) : (($isBn ?? false) ? 'সকল ব্যাংক অ্যাকাউন্ট (একত্রে)' : 'All Accounts (Consolidated)') }} &nbsp;|&nbsp;
            <strong>{{ ($isBn ?? false) ? 'সময়কাল:' : 'Period:' }}</strong> {{ $month }} {{ $year }}
        </div>

        <!-- Transaction Table -->
        <table>
            <thead>
                <tr>
                    <th rowspan="2" class="bg-head-group" style="width: 7%; text-align: center;">{{ ($isBn ?? false) ? 'তারিখ' : 'Date' }}</th>
                    <th colspan="5" class="bg-head-group" style="width: 37%; text-align: center;">{{ ($isBn ?? false) ? 'জমা (ইনফ্লো)' : 'Deposit (Inflow)' }}</th>
                    <th colspan="7" class="bg-head-group" style="width: 46%; text-align: center;">{{ ($isBn ?? false) ? 'উত্তোলন ও খরচ (আউটফ্লো)' : 'Withdrawal (Outflow)' }}</th>
                    <th rowspan="2" class="bg-head-group" style="width: 10%; text-align: right;">{{ ($isBn ?? false) ? 'ব্যাংক ব্যালেন্স' : 'Bank Balance' }}</th>
                </tr>
                <tr>
                    <!-- Deposit Headers (5 columns) -->
                    <th class="bg-subhead" style="width: 7%; text-align: center;">{{ ($isBn ?? false) ? 'তহবিল' : 'Fund' }}</th>
                    <th class="bg-subhead" style="width: 8%; text-align: center;">{{ ($isBn ?? false) ? 'আদায়' : 'Receive' }}</th>
                    <th class="bg-subhead" style="width: 7%; text-align: center;">{{ ($isBn ?? false) ? 'অন্যান্য' : 'Others' }}</th>
                    <th class="bg-subhead" style="width: 7%; text-align: center;">{{ ($isBn ?? false) ? 'রিফান্ড' : 'Refund' }}</th>
                    <th class="total-col" style="width: 8%; text-align: center;">{{ ($isBn ?? false) ? 'মোট' : 'Total' }}</th>

                    <!-- Withdrawal Headers (7 columns) -->
                    <th class="bg-subhead" style="width: 6.5%; text-align: center;">{{ ($isBn ?? false) ? 'তহবিল' : 'Fund' }}</th>
                    <th class="bg-subhead" style="width: 6.5%; text-align: center;">{{ ($isBn ?? false) ? 'ক্রয়' : 'Purchase' }}</th>
                    <th class="bg-subhead" style="width: 7%; text-align: center;">{{ ($isBn ?? false) ? 'সরবরাহকারী' : 'Supplier' }}</th>
                    <th class="bg-subhead" style="width: 7%; text-align: center;">{{ ($isBn ?? false) ? 'স্থায়ী সম্পদ' : 'Fixed Asset' }}</th>
                    <th class="bg-subhead" style="width: 6%; text-align: center;">{{ ($isBn ?? false) ? 'খরচ' : 'Expense' }}</th>
                    <th class="bg-subhead" style="width: 6%; text-align: center;">{{ ($isBn ?? false) ? 'অন্যান্য' : 'Others' }}</th>
                    <th class="total-col" style="width: 7%; text-align: center;">{{ ($isBn ?? false) ? 'মোট' : 'Total' }}</th>
                </tr>
            </thead>
            <tbody>
                <!-- Previous Balance Row -->
                <tr style="background-color: #f9f9f9; font-weight: bold;">
                    <td class="text-left">{{ ($isBn ?? false) ? 'পূর্বের ব্যালেন্স' : 'Prev. Balance' }}</td>
                    <td class="text-center">{{ ($isBn ?? false) ? '০' : '0' }}</td>
                    <td class="text-center">{{ ($isBn ?? false) ? '০' : '0' }}</td>
                    <td class="text-center">{{ ($isBn ?? false) ? '০' : '0' }}</td>
                    <td class="text-center">{{ ($isBn ?? false) ? '০' : '0' }}</td>
                    <td class="text-center total-col">{{ ($isBn ?? false) ? '০' : '0' }}</td>
                    <td class="text-center">{{ ($isBn ?? false) ? '০' : '0' }}</td>
                    <td class="text-center">{{ ($isBn ?? false) ? '০' : '0' }}</td>
                    <td class="text-center">{{ ($isBn ?? false) ? '০' : '0' }}</td>
                    <td class="text-center">{{ ($isBn ?? false) ? '০' : '0' }}</td>
                    <td class="text-center">{{ ($isBn ?? false) ? '০' : '0' }}</td>
                    <td class="text-center">{{ ($isBn ?? false) ? '০' : '0' }}</td>
                    <td class="text-center total-col">{{ ($isBn ?? false) ? '০' : '0' }}</td>
                    <td class="text-right" style="font-weight: bold;">{{ format_amount($previousMonthBalance, true, $isBn ?? false) }}</td>
                </tr>

                @foreach($dailyTransactions as $transaction)
                @php
                    $inOther = (float)($transaction['in']['extra'] ?? 0) + (float)($transaction['in']['other'] ?? 0);
                @endphp
                <tr>
                    <td class="text-center">{{ ($isBn ?? false) ? to_bangla_date($transaction['date']) : \Carbon\Carbon::parse($transaction['date'])->format('d/m/Y') }}</td>

                    <!-- Deposit Columns -->
                    <td class="text-right">
                        {{ format_amount($transaction['in']['fund'], true, $isBn ?? false) }}
                    </td>
                    <td class="text-right">
                        {{ format_amount($transaction['in']['payment'], true, $isBn ?? false) }}
                    </td>
                    <td class="text-right">
                        {{ format_amount($inOther, true, $isBn ?? false) }}
                    </td>
                    <td class="text-right">
                        {{ format_amount($transaction['in']['refund'], true, $isBn ?? false) }}
                    </td>
                    <td class="text-right total-col">
                        {{ format_amount($transaction['in']['total'], true, $isBn ?? false) }}
                    </td>

                    <!-- Withdrawal Columns -->
                    <td class="text-right">
                        {{ format_amount($transaction['out']['fund'], true, $isBn ?? false) }}
                    </td>
                    <td class="text-right">
                        {{ format_amount($transaction['out']['purchase'], true, $isBn ?? false) }}
                    </td>
                    <td class="text-right">
                        {{ format_amount($transaction['out']['supplier_payment'] ?? 0, true, $isBn ?? false) }}
                    </td>
                    <td class="text-right">
                        {{ format_amount($transaction['out']['fixed_asset'] ?? 0, true, $isBn ?? false) }}
                    </td>
                    <td class="text-right">
                        {{ format_amount($transaction['out']['expense'], true, $isBn ?? false) }}
                    </td>
                    <td class="text-right">
                        {{ format_amount($transaction['out']['other'] ?? 0, true, $isBn ?? false) }}
                    </td>
                    <td class="text-right total-col">
                        {{ format_amount($transaction['out']['total'], true, $isBn ?? false) }}
                    </td>

                    <td class="text-right" style="font-weight: bold;">{{ format_amount($transaction['balance'], true, $isBn ?? false) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                @php
                    $lastBalance = !empty($dailyTransactions) ? end($dailyTransactions)['balance'] : $previousMonthBalance;
                    $totalInOther = (float)($monthTotals['in']['extra'] ?? 0) + (float)($monthTotals['in']['other'] ?? 0);
                @endphp
                <tr class="total-row">
                    <td class="text-left" style="font-weight: bold;">{{ ($isBn ?? false) ? 'মাসিক মোট:' : 'Month Total:' }}</td>
                    <!-- Deposit Totals -->
                    <td class="text-right" style="font-weight: bold;">{{ format_amount($monthTotals['in']['fund'], true, $isBn ?? false) }}</td>
                    <td class="text-right" style="font-weight: bold;">{{ format_amount($monthTotals['in']['payment'], true, $isBn ?? false) }}</td>
                    <td class="text-right" style="font-weight: bold;">{{ format_amount($totalInOther, true, $isBn ?? false) }}</td>
                    <td class="text-right" style="font-weight: bold;">{{ format_amount($monthTotals['in']['refund'], true, $isBn ?? false) }}</td>
                    <td class="text-right total-col" style="font-weight: bold;">{{ format_amount($monthTotals['in']['total'], true, $isBn ?? false) }}</td>

                    <!-- Withdrawal Totals -->
                    <td class="text-right" style="font-weight: bold;">{{ format_amount($monthTotals['out']['fund'], true, $isBn ?? false) }}</td>
                    <td class="text-right" style="font-weight: bold;">{{ format_amount($monthTotals['out']['purchase'], true, $isBn ?? false) }}</td>
                    <td class="text-right" style="font-weight: bold;">{{ format_amount($monthTotals['out']['supplier_payment'] ?? 0, true, $isBn ?? false) }}</td>
                    <td class="text-right" style="font-weight: bold;">{{ format_amount($monthTotals['out']['fixed_asset'] ?? 0, true, $isBn ?? false) }}</td>
                    <td class="text-right" style="font-weight: bold;">{{ format_amount($monthTotals['out']['expense'], true, $isBn ?? false) }}</td>
                    <td class="text-right" style="font-weight: bold;">{{ format_amount($monthTotals['out']['other'] ?? 0, true, $isBn ?? false) }}</td>
                    <td class="text-right total-col" style="font-weight: bold;">{{ format_amount($monthTotals['out']['total'], true, $isBn ?? false) }}</td>

                    <td class="text-right" style="font-weight: bold;">{{ format_amount($lastBalance, true, $isBn ?? false) }}</td>
                </tr>
            </tfoot>
        </table>

        <!-- Footer -->
        <div class="footer">
            {{ ($isBn ?? false) ? 'পৃষ্ঠা' : 'Page' }} <span class="page-number"></span>
            &nbsp;|&nbsp;
            {{ ($isBn ?? false) ? 'তৈরির সময়:' : 'Generated on:' }} {{ ($isBn ?? false) ? to_bangla_date(now(), 'd M Y, h:i A') : now()->format('d M Y, h:i A') }}
        </div>
    </div>
</body>
</html>

