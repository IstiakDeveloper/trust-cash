<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Income & Expenditure Statement</title>
    <style>
        @page {
            margin: 20mm 15mm;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 8pt;
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
            font-size: 12pt;
            font-weight: bold;
            color: #000;
            margin-bottom: 15px;
        }

        .report-period {
            text-align: center;
            font-size: 8pt;
            color: #222;
            margin-bottom: 20px;
        }

        /* Side by side layout like balance sheet */
        .statement-content {
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
            font-size: 9pt;
            color: #000;
            margin-bottom: 10px;
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
            font-size: 7pt;
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

        .net-result {
            margin-top: 20px;
            border: 1px solid #000;
            padding: 10px;
            background-color: #f8f9fa;
            clear: both;
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
            <div class="company-name">{{ business_name() }}</div>
            <div class="sub-company-name"></div>
            @if(business_details())
            <div class="company-details">
                {{ business_details(' | ') }}
            </div>
            @endif
        </div>

        <!-- Report Title and Period -->
        <div class="report-title">{{ ($isBn ?? false) ? 'লাভ-ক্ষতি ও আয়-ব্যয় বিবরণী' : 'Income & Expenditure Statement' }}</div>
        <div class="report-period">
            @if (isset($filters['month_name']))
                {{ ($isBn ?? false) ? 'সময়কাল:' : 'Period:' }} {{ $filters['month_name'] }} {{ $filters['year'] }}
            @else
                {{ ($isBn ?? false) ? 'সময়কাল:' : 'Period:' }} {{ ($isBn ?? false) ? to_bangla_date($filters['start_date'], 'd M Y') : \Carbon\Carbon::parse($filters['start_date'])->format('d M Y') }} {{ ($isBn ?? false) ? 'হতে' : 'to' }}
                {{ ($isBn ?? false) ? to_bangla_date($filters['end_date'], 'd M Y') : \Carbon\Carbon::parse($filters['end_date'])->format('d M Y') }}
            @endif
        </div>

        <!-- Statement Content in side by side layout -->
        @php
            $incomeCount = 1 + count($income['extra_income']['categories'] ?? []);
            $expenditureCount = count($expenditure['categories'] ?? []);
            $maxTargetRows = max($incomeCount + 3, $expenditureCount + 1);
            $incomeSpacers = max(0, $maxTargetRows - ($incomeCount + 3));
            $expenditureSpacers = max(0, $maxTargetRows - ($expenditureCount + 1));
        @endphp
        <div class="statement-content">
            <!-- Income Section -->
            <div class="section">
                <div class="section-header">{{ ($isBn ?? false) ? 'আয়' : 'Income' }}</div>
                <table>
                    <thead>
                        <tr>
                            <th>{{ ($isBn ?? false) ? 'বিবরণ' : 'Description' }}</th>
                            <th class="text-right">{{ ($isBn ?? false) ? 'চলতি সময়' : 'Month' }}</th>
                            <th class="text-right">{{ ($isBn ?? false) ? 'ক্রমপুঞ্জিত' : 'Cumulative' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Sales Profit Row -->
                        <tr>
                            <td class="font-bold">{{ ($isBn ?? false) ? 'বিক্রয় মুনাফা' : 'Sales Profit' }}</td>
                            <td class="text-right">
                                {{ format_amount($income['sales_profit']['period'], true, $isBn ?? false) }}
                            </td>
                            <td class="text-right">
                                {{ format_amount($income['sales_profit']['cumulative'], true, $isBn ?? false) }}
                            </td>
                        </tr>

                        <!-- Extra Income Categories -->
                        @foreach ($income['extra_income']['categories'] as $category)
                            <tr>
                                <td>{{ $category['name'] }} <span class="text-muted">{{ ($isBn ?? false) ? '(অন্যান্য আয়)' : '(Others Income)' }}</span></td>
                                <td class="text-right">{{ format_amount($category['period'], true, $isBn ?? false) }}</td>
                                <td class="text-right">{{ format_amount($category['cumulative'], true, $isBn ?? false) }}</td>
                            </tr>
                        @endforeach

                        <!-- Spacer rows to align Grand Total with Total Expenditure on the same line -->
                        @for ($i = 0; $i < $incomeSpacers; $i++)
                            <tr>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                            </tr>
                        @endfor

                        <!-- Total Income Row -->
                        <tr class="total-row">
                            <td><strong>{{ ($isBn ?? false) ? 'মোট আয়' : 'Total Income' }}</strong></td>
                            <td style="font-size: 9px;" class="text-right">
                                {{ format_amount($income['total']['period'], true, $isBn ?? false) }}</td>
                            <td style="font-size: 9px;" class="text-right">
                                {{ format_amount($income['total']['cumulative'], true, $isBn ?? false) }}</td>
                        </tr>

                        <!-- Surplus Row -->
                        <tr class="total-row">
                            <td><strong>{{ ($isBn ?? false) ? 'উদ্বৃত্ত (লাভ/ক্ষতি)' : 'Surplus' }}</strong></td>
                            <td class="text-right" style="font-size: 9px;">
                                {{ format_amount($income['total']['period'] - $expenditure['total']['period'], true, $isBn ?? false) }}
                            </td>
                            <td class="text-right" style="font-size: 9px;">
                                {{ format_amount($income['total']['cumulative'] - $expenditure['total']['cumulative'], true, $isBn ?? false) }}
                            </td>
                        </tr>

                        <!-- Grand Total Row -->
                        <tr class="total-row">
                            <td><strong>{{ ($isBn ?? false) ? 'সর্বমোট' : 'Grand Total' }}</strong></td>
                            <td style="font-size: 9px;" class="text-right">
                                {{ format_amount($expenditure['total']['period'], true, $isBn ?? false) }}</td>
                            <td style="font-size: 9px;" class="text-right">
                                {{ format_amount($expenditure['total']['cumulative'], true, $isBn ?? false) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Expenditure Section -->
            <div class="section">
                <div class="section-header">{{ ($isBn ?? false) ? 'ব্যয়' : 'Expenditure' }}</div>
                <table>
                    <thead>
                        <tr>
                            <th>{{ ($isBn ?? false) ? 'বিবরণ' : 'Description' }}</th>
                            <th class="text-right">{{ ($isBn ?? false) ? 'চলতি সময়' : 'Month' }}</th>
                            <th class="text-right">{{ ($isBn ?? false) ? 'ক্রমপুঞ্জিত' : 'Cumulative' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Expenditure Categories -->
                        @foreach ($expenditure['categories'] as $category)
                            <tr>
                                <td>{{ $category['name'] }}</td>
                                <td class="text-right">{{ format_amount($category['period'], true, $isBn ?? false) }}</td>
                                <td class="text-right">{{ format_amount($category['cumulative'], true, $isBn ?? false) }}</td>
                            </tr>
                        @endforeach

                        <!-- Spacer rows to align Total Expenditure with Grand Total on the same line -->
                        @for ($i = 0; $i < $expenditureSpacers; $i++)
                            <tr>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                            </tr>
                        @endfor

                        <!-- Total Expenditure Row -->
                        <tr class="total-row">
                            <td><strong>{{ ($isBn ?? false) ? 'মোট ব্যয়' : 'Total Expenditure' }}</strong></td>
                            <td class="text-right">{{ format_amount($expenditure['total']['period'], true, $isBn ?? false) }}</td>
                            <td class="text-right">{{ format_amount($expenditure['total']['cumulative'], true, $isBn ?? false) }}</td>
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
