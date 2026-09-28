<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Balance Sheet Report</title>
    <style>
        @page {
            margin: 20mm 15mm;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.6;
            color: #333;
        }

        .container {
            width: 100%;
            margin: 0 auto;
        }

        .company-header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #4a4a4a;
            padding-bottom: 15px;
        }

        .company-name {
            font-size: 18pt;
            font-weight: bold;
            color: #1a1a1a;

        }

        .sub-company-name {
            font-size: 14pt;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 5px;
        }

        .company-details {
            font-size: 9pt;
            color: #666;
            margin-bottom: 10px;
        }

        .report-title {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            color: #000;
            margin-bottom: 15px;
        }

        .report-period {
            text-align: center;
            font-size: 10pt;
            color: #222;
            margin-bottom: 20px;
        }

        .balance-sheet {
            width: 100%;
            display: table;
        }

        .section {
            display: table-cell;
            width: 50%;
            padding: 10px;
        }

        .section-header {
            background-color: #f2f2f2;
            padding: 8px 10px;
            border: 1px solid #000;
            font-weight: bold;
            font-size: 11pt;
            color: #000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        table th,
        table td {
            padding: 6px 10px;
            border: 1px solid #000;
            font-size: 9pt;
            color: #000;
        }

        table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #000;
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .total-row {
            font-weight: bold;
            background-color: #f2f2f2;
            color: #000;
        }

        .green {
            color: #000;
        }

        .red {
            color: #000;
        }

        .footer {
            position: fixed;
            bottom: 10mm;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8pt;
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
            <div class="company-name">{{ config('app.name', 'Your Company Name') }}/ Variety Store</div>
            <div class="sub-company-name"></div>
            <div class="company-details">
                Ukilpara, Naogaon Sadar, Naogaon.<br>
                Phone: (+88) 01334766435 | Email: mou.prokashon@gmail.com
            </div>
        </div>

        <!-- Report Title and Period -->
        <div class="report-title">{{ ($isBn ?? false) ? 'ব্যালেন্স শীট বিবরণী' : 'Balance Sheet Report' }}</div>
        <div class="report-period">
            {{ ($isBn ?? false) ? 'সময়কাল:' : 'Period:' }} {{ ($isBn ?? false) ? to_bangla_date($start_date, 'd M Y') : \Carbon\Carbon::parse($start_date)->format('d M Y') }}
            {{ ($isBn ?? false) ? 'হতে' : 'to' }}
            {{ ($isBn ?? false) ? to_bangla_date($end_date, 'd M Y') : \Carbon\Carbon::parse($end_date)->format('d M Y') }}
        </div>

        <!-- Balance Sheet Content -->
        <div class="balance-sheet">
            <!-- Fund & Liabilities Section -->
            <div class="section">
                <div class="section-header">{{ ($isBn ?? false) ? 'তহবিল ও দায়' : 'Fund & Liabilities' }}</div>
                <table>
                    <thead>
                        <tr>
                            <th>{{ ($isBn ?? false) ? 'বিবরণ' : 'Description' }}</th>
                            <th class="text-right">{{ ($isBn ?? false) ? 'টাকা' : 'Amount (' . config('app.currency', 'BDT') . ')' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ ($isBn ?? false) ? 'তহবিল' : 'Fund' }}</td>
                            <td class="text-right">{{ format_amount($fund_and_liabilities['fund']['period'], true, $isBn ?? false) }}
                            </td>
                        </tr>
                        <tr>
                            <td>{{ ($isBn ?? false) ? 'নিট লাভ' : 'Net Profit' }}</td>
                            <td class="text-right">
                                {{ format_amount($fund_and_liabilities['net_profit']['period'], true, $isBn ?? false) }}
                            </td>
                        </tr>
                        <tr>
                            <td>{{ ($isBn ?? false) ? 'সরবরাহকারী বকেয়া (প্রদেয়)' : 'Supplier Due (Payable)' }}</td>
                            <td class="text-right">
                                {{ format_amount($fund_and_liabilities['supplier_due']['period'] ?? 0, true, $isBn ?? false) }}
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="height: 25px;"></td>
                        </tr>
                        <tr class="total-row">
                            <td>{{ ($isBn ?? false) ? 'মোট তহবিল ও দায়' : 'Total Fund & Liabilities' }}</td>
                            <td class="text-right">{{ format_amount($fund_and_liabilities['total'], true, $isBn ?? false) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Property & Assets Section -->
            <div class="section">
                <div class="section-header">{{ ($isBn ?? false) ? 'সম্পত্তি ও পরিসম্পদ' : 'Property & Assets' }}</div>
                <table>
                    <thead>
                        <tr>
                            <th>{{ ($isBn ?? false) ? 'বিবরণ' : 'Description' }}</th>
                            <th class="text-right">{{ ($isBn ?? false) ? 'টাকা' : 'Amount (' . config('app.currency', 'BDT') . ')' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ ($isBn ?? false) ? 'ব্যাংক ব্যালেন্স' : 'Bank Balance' }}</td>
                            <td class="text-right">
                                {{ format_amount($property_and_assets['bank_balance']['period'], true, $isBn ?? false) }}</td>
                        </tr>
                        <tr>
                            <td>{{ ($isBn ?? false) ? 'গ্রাহক বকেয়া (পাওনা)' : 'Customer Due' }}</td>
                            <td class="text-right">
                                {{ format_amount($property_and_assets['customer_due']['period'], true, $isBn ?? false) }}</td>
                        </tr>
                        <tr>
                            <td>{{ ($isBn ?? false) ? 'স্থায়ী সম্পদ' : 'Fixed Assets' }}</td>
                            <td class="text-right">{{ format_amount($property_and_assets['fixed_assets'], true, $isBn ?? false) }}</td>
                        </tr>
                        <tr>
                            <td>{{ ($isBn ?? false) ? 'মজুদ পণ্যের মূল্য' : 'Stock Value' }}</td>
                            <td class="text-right">
                                {{ format_amount($property_and_assets['stock_value']['period'], true, $isBn ?? false) }}</td>
                        </tr>
                        <tr class="total-row">
                            <td>{{ ($isBn ?? false) ? 'মোট সম্পত্তি ও পরিসম্পদ' : 'Total Property & Assets' }}</td>
                            <td class="text-right">{{ format_amount($property_and_assets['total'], true, $isBn ?? false) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Footer with Page Number -->
        <div class="footer">
            {{ ($isBn ?? false) ? 'পৃষ্ঠা' : 'Page' }} <span class="page-number"></span>
            <br>
            {{ ($isBn ?? false) ? 'তৈরির সময়:' : 'Generated on:' }} {{ ($isBn ?? false) ? to_bangla_date(now(), 'd M Y, h:i A') : now()->format('d M Y H:i:s') }}
        </div>
    </div>
</body>

</html>

