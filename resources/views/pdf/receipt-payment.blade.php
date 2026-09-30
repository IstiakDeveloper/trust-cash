<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Statement of Receipts & Payments</title>
    <style>
        @page {
            margin: 8mm 10mm;
            size: A4 landscape;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 8pt;
            line-height: 1.4;
            color: #000;
        }

        .container {
            width: 100%;
            margin: 0 auto;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            border-bottom: 2px solid #000;
            padding-bottom: 6px;
        }

        .company-name {
            font-size: 16pt;
            font-weight: bold;
            color: #000;
        }

        .company-details {
            font-size: 8pt;
            color: #444;
            margin-top: 2px;
        }

        .report-title {
            text-align: center;
            font-size: 13pt;
            font-weight: bold;
            color: #000;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .report-meta-table {
            width: 100%;
            margin-bottom: 8px;
            font-size: 8.5pt;
        }

        .statement-table-container {
            width: 100%;
            border-collapse: collapse;
        }

        .section-cell {
            width: 50%;
            vertical-align: top;
            padding: 0 3px;
        }

        .section-header {
            background-color: #e5e5e5;
            color: #000000;
            padding: 5px 8px;
            font-weight: bold;
            font-size: 9pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid #000;
            border-bottom: none;
        }

        .section-header.payment {
            background-color: #e5e5e5;
            color: #000000;
        }

        /* Full border on table and every single cell */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid #000;
        }

        table.data-table th,
        table.data-table td {
            padding: 4px 6px;
            border: 1px solid #000;
            font-size: 7.5pt;
        }

        table.data-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            color: #000;
            text-align: left;
            border: 1px solid #000;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .font-bold {
            font-weight: bold;
        }

        .sub-header-row {
            background-color: #f5f5f5;
            font-weight: bold;
            color: #000;
        }

        .sub-header-row td {
            border: 1px solid #000;
        }

        .sub-total-row {
            background-color: #fafafa;
            font-weight: bold;
            color: #000;
        }

        .sub-total-row td {
            border: 1px solid #000;
        }

        .total-row {
            font-weight: bold;
            font-size: 8pt;
            background-color: #e5e5e5;
            border-top: 2px solid #000;
            color: #000;
        }

        .total-row td {
            border: 1px solid #000;
        }

        .total-receipt {
            color: #000000;
        }

        .total-payment {
            color: #000000;
        }

        .pl-indent {
            padding-left: 16px !important;
            font-size: 7pt;
            color: #111;
        }

        .footer {
            position: fixed;
            bottom: 4mm;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 7pt;
            color: #555;
        }

        .page-number:before {
            content: counter(page);
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Company Header -->
        <table class="header-table">
            <tr>
                <td style="width: 65%;">
                    <div class="company-name">{{ business_name() }}</div>
                    @if(business_details())
                    <div class="company-details">
                        {{ business_details(' | ') }}
                    </div>
                    @endif
                </td>
                <td style="width: 35%; text-align: right; vertical-align: bottom;">
                    <div style="font-size: 8pt; color: #333;">
                        {{ ($isBn ?? false) ? 'অ্যাকাউন্ট:' : 'Account:' }} <strong>{{ $bank_account ? ($bank_account->account_name . ' (' . $bank_account->bank_name . ')') : (($isBn ?? false) ? 'সকল ব্যাংক অ্যাকাউন্ট (একত্রে)' : 'All Bank Accounts (Consolidated)') }}</strong>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Report Title & Date -->
        <table class="report-meta-table">
            <tr>
                <td style="width: 60%; vertical-align: middle;">
                    <span class="report-title">{{ ($isBn ?? false) ? 'রিসিপ্ট ও পেমেন্ট বিবরণী' : 'Statement of Receipts & Payments' }}</span>
                </td>
                <td style="width: 40%; text-align: right; vertical-align: middle; font-weight: bold; color: #000;">
                    {{ ($isBn ?? false) ? 'তারিখ:' : 'Date:' }} {{ ($isBn ?? false) ? to_bangla_date($start_date, 'd M Y') : \Carbon\Carbon::parse($start_date)->format('d M Y') }} {{ ($isBn ?? false) ? 'হতে' : 'to' }} {{ ($isBn ?? false) ? to_bangla_date($end_date, 'd M Y') : \Carbon\Carbon::parse($end_date)->format('d M Y') }}
                </td>
            </tr>
        </table>

        <!-- Two Column Content Table -->
        <table class="statement-table-container">
            <tr>
                <!-- ================= RECEIPTS ================= -->
                <td class="section-cell">
                    <div class="section-header">{{ ($isBn ?? false) ? 'রিসিপ্ট (প্রাপ্তি)' : 'Receipts' }}</div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 25px; text-align: center;">{{ ($isBn ?? false) ? 'ক্র.নং' : 'SL' }}</th>
                                <th>{{ ($isBn ?? false) ? 'বিবরণ' : 'Particulars' }}</th>
                                <th class="text-right" style="width: 75px;">{{ ($isBn ?? false) ? 'চলতি সময়' : 'Period' }}</th>
                                <th class="text-right" style="width: 75px;">{{ ($isBn ?? false) ? 'ক্রমপুঞ্জিত' : 'Cumulative' }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- 1. Opening Cash -->
                            <tr>
                                <td class="text-center font-bold">1</td>
                                <td class="font-bold">{{ ($isBn ?? false) ? 'প্রারম্ভিক ব্যাংক জমা' : 'Opening Cash in Hand / Bank' }}</td>
                                <td class="text-right font-bold">
                                    {{ format_amount($receipt['opening_cash_on_bank']['period'] ?? 0, true, $isBn ?? false) }}
                                </td>
                                <td class="text-right font-bold">
                                    {{ format_amount($receipt['opening_cash_on_bank']['cumulative'] ?? 0, true, $isBn ?? false) }}
                                </td>
                            </tr>

                            <!-- 2. Sale Collection -->
                            <tr>
                                <td class="text-center font-bold">2</td>
                                <td class="font-bold">{{ ($isBn ?? false) ? 'বিক্রয় আদায়' : 'Sale Collection' }}</td>
                                <td class="text-right font-bold">
                                    {{ format_amount($receipt['sale_collection']['period'] ?? 0, true, $isBn ?? false) }}
                                </td>
                                <td class="text-right font-bold">
                                    {{ format_amount($receipt['sale_collection']['cumulative'] ?? 0, true, $isBn ?? false) }}
                                </td>
                            </tr>

                            <!-- 3. Others Income -->
                            <tr class="sub-header-row">
                                <td class="text-center font-bold">3</td>
                                <td colspan="3" class="font-bold">{{ ($isBn ?? false) ? 'অন্যান্য আয়' : 'Others Income' }}</td>
                            </tr>
                            @if (!empty($receipt['extra_income']['categories']) && count($receipt['extra_income']['categories']) > 0)
                                @foreach ($receipt['extra_income']['categories'] as $cat)
                                    <tr>
                                        <td></td>
                                        <td class="pl-indent">&bull; {{ $cat['category'] }}</td>
                                        <td class="text-right">{{ format_amount($cat['period'] ?? 0, true, $isBn ?? false) }}</td>
                                        <td class="text-right">{{ format_amount($cat['cumulative'] ?? 0, true, $isBn ?? false) }}</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td></td>
                                    <td class="pl-indent" style="font-style: italic; color: #666;">No others income this period</td>
                                    <td class="text-right">0</td>
                                    <td class="text-right">0</td>
                                </tr>
                            @endif
                            <tr class="sub-total-row">
                                <td></td>
                                <td class="font-bold">{{ ($isBn ?? false) ? 'মোট অন্যান্য আয়' : 'Total Others Income' }}</td>
                                <td class="text-right font-bold">
                                    {{ format_amount($receipt['extra_income']['total']['period'] ?? 0, true, $isBn ?? false) }}
                                </td>
                                <td class="text-right font-bold">
                                    {{ format_amount($receipt['extra_income']['total']['cumulative'] ?? 0, true, $isBn ?? false) }}
                                </td>
                            </tr>

                            <!-- 4. Fund Receive -->
                            <tr class="sub-header-row">
                                <td class="text-center font-bold">4</td>
                                <td colspan="3" class="font-bold">{{ ($isBn ?? false) ? 'তহবিল গ্রহণ' : 'Fund Receive' }}</td>
                            </tr>
                            @if (!empty($receipt['fund_receive']['items']) && count($receipt['fund_receive']['items']) > 0)
                                @foreach ($receipt['fund_receive']['items'] as $item)
                                    <tr>
                                        <td></td>
                                        <td class="pl-indent">&bull; {{ $item['name'] }}</td>
                                        <td class="text-right">{{ format_amount($item['period'] ?? 0, true, $isBn ?? false) }}</td>
                                        <td class="text-right">{{ format_amount($item['cumulative'] ?? 0, true, $isBn ?? false) }}</td>
                                    </tr>
                                @endforeach
                            @endif
                            <tr class="sub-total-row">
                                <td></td>
                                <td class="font-bold">{{ ($isBn ?? false) ? 'মোট তহবিল গ্রহণ' : 'Total Fund Receive' }}</td>
                                <td class="text-right font-bold">
                                    {{ format_amount($receipt['fund_receive']['total']['period'] ?? 0, true, $isBn ?? false) }}
                                </td>
                                <td class="text-right font-bold">
                                    {{ format_amount($receipt['fund_receive']['total']['cumulative'] ?? 0, true, $isBn ?? false) }}
                                </td>
                            </tr>

@php
    $receipt_extra_count = !empty($receipt['extra_income']['categories']) ? count($receipt['extra_income']['categories']) : 1;
    $receipt_fund_count = !empty($receipt['fund_receive']['items']) ? count($receipt['fund_receive']['items']) : 0;
    $receipt_rows_count = 6 + $receipt_extra_count + $receipt_fund_count;

    $payment_fa_count = !empty($payment['fixed_assets']['items']) ? count($payment['fixed_assets']['items']) : 1;
    $payment_fund_count = !empty($payment['fund_refund']['items']) ? count($payment['fund_refund']['items']) : 0;
    $payment_exp_count = !empty($payment['expenses']['categories']) ? count($payment['expenses']['categories']) : 1;
    $payment_rows_count = 9 + $payment_fa_count + $payment_fund_count + $payment_exp_count;

    $row_diff = $payment_rows_count - $receipt_rows_count;
@endphp
                            @if ($row_diff > 0)
                                @for ($i = 0; $i < $row_diff; $i++)
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                    </tr>
                                @endfor
                            @endif

                            <!-- Total Receipt Row -->
                            <tr class="total-row">
                                <td class="text-center font-bold">&nbsp;</td>
                                <td class="font-bold total-receipt">{{ ($isBn ?? false) ? 'মোট রিসিপ্ট' : 'Total Receipt' }}</td>
                                <td class="text-right font-bold total-receipt">
                                    {{ format_amount($receipt['total']['period'] ?? 0, true, $isBn ?? false) }}
                                </td>
                                <td class="text-right font-bold total-receipt">
                                    {{ format_amount($receipt['total']['cumulative'] ?? 0, true, $isBn ?? false) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>

                <!-- ================= PAYMENTS ================= -->
                <td class="section-cell">
                    <div class="section-header payment">{{ ($isBn ?? false) ? 'পেমেন্ট (পরিশোধ)' : 'Payments' }}</div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 25px; text-align: center;">{{ ($isBn ?? false) ? 'ক্র.নং' : 'SL' }}</th>
                                <th>{{ ($isBn ?? false) ? 'বিবরণ' : 'Particulars' }}</th>
                                <th class="text-right" style="width: 75px;">{{ ($isBn ?? false) ? 'চলতি সময়' : 'Period' }}</th>
                                <th class="text-right" style="width: 75px;">{{ ($isBn ?? false) ? 'ক্রমপুঞ্জিত' : 'Cumulative' }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- 1. Purchase -->
                            <tr>
                                <td class="text-center font-bold">1</td>
                                <td class="font-bold">{{ ($isBn ?? false) ? 'পণ্য ক্রয়' : 'Purchase' }}</td>
                                <td class="text-right font-bold">
                                    {{ format_amount($payment['purchase']['period'] ?? 0, true, $isBn ?? false) }}
                                </td>
                                <td class="text-right font-bold">
                                    {{ format_amount($payment['purchase']['cumulative'] ?? 0, true, $isBn ?? false) }}
                                </td>
                            </tr>

                            <!-- 2. Supplier Payment -->
                            <tr>
                                <td class="text-center font-bold">2</td>
                                <td class="font-bold">{{ ($isBn ?? false) ? 'সরবরাহকারী পরিশোধ' : 'Supplier Payment (Due Paid)' }}</td>
                                <td class="text-right font-bold">
                                    {{ format_amount($payment['supplier_payment']['period'] ?? 0, true, $isBn ?? false) }}
                                </td>
                                <td class="text-right font-bold">
                                    {{ format_amount($payment['supplier_payment']['cumulative'] ?? 0, true, $isBn ?? false) }}
                                </td>
                            </tr>

                            <!-- 3. Fixed Asset Purchase (Breakdown matching expenses) -->
                            <tr class="sub-header-row">
                                <td class="text-center font-bold">3</td>
                                <td colspan="3" class="font-bold">{{ ($isBn ?? false) ? 'স্থায়ী সম্পদ ক্রয়' : 'Fixed Asset Purchase' }}</td>
                            </tr>
                            @if (!empty($payment['fixed_assets']['items']) && count($payment['fixed_assets']['items']) > 0)
                                @foreach ($payment['fixed_assets']['items'] as $item)
                                    <tr>
                                        <td></td>
                                        <td class="pl-indent">&bull; {{ $item['name'] }}</td>
                                        <td class="text-right">{{ format_amount($item['period'] ?? 0, true, $isBn ?? false) }}</td>
                                        <td class="text-right">{{ format_amount($item['cumulative'] ?? 0, true, $isBn ?? false) }}</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td></td>
                                    <td class="pl-indent" style="font-style: italic; color: #666;">No fixed asset purchase this period</td>
                                    <td class="text-right">0</td>
                                    <td class="text-right">0</td>
                                </tr>
                            @endif
                            <tr class="sub-total-row">
                                <td></td>
                                <td class="font-bold">{{ ($isBn ?? false) ? 'মোট স্থায়ী সম্পদ ক্রয়' : 'Total Fixed Asset Purchase' }}</td>
                                <td class="text-right font-bold">
                                    {{ format_amount($payment['fixed_assets']['total']['period'] ?? 0, true, $isBn ?? false) }}
                                </td>
                                <td class="text-right font-bold">
                                    {{ format_amount($payment['fixed_assets']['total']['cumulative'] ?? 0, true, $isBn ?? false) }}
                                </td>
                            </tr>

                            <!-- 4. Fund Refund / Fund Out -->
                            <tr class="sub-header-row">
                                <td class="text-center font-bold">4</td>
                                <td colspan="3" class="font-bold">{{ ($isBn ?? false) ? 'তহবিল ফেরত' : 'Fund Refund / Fund Out' }}</td>
                            </tr>
                            @if (!empty($payment['fund_refund']['items']) && count($payment['fund_refund']['items']) > 0)
                                @foreach ($payment['fund_refund']['items'] as $item)
                                    <tr>
                                        <td></td>
                                        <td class="pl-indent">&bull; {{ $item['name'] }}</td>
                                        <td class="text-right">{{ format_amount($item['period'] ?? 0, true, $isBn ?? false) }}</td>
                                        <td class="text-right">{{ format_amount($item['cumulative'] ?? 0, true, $isBn ?? false) }}</td>
                                    </tr>
                                @endforeach
                            @endif
                            <tr class="sub-total-row">
                                <td></td>
                                <td class="font-bold">{{ ($isBn ?? false) ? 'মোট তহবিল ফেরত' : 'Total Fund Refund / Out' }}</td>
                                <td class="text-right font-bold">
                                    {{ format_amount($payment['fund_refund']['total']['period'] ?? 0, true, $isBn ?? false) }}
                                </td>
                                <td class="text-right font-bold">
                                    {{ format_amount($payment['fund_refund']['total']['cumulative'] ?? 0, true, $isBn ?? false) }}
                                </td>
                            </tr>

                            <!-- 5. Expenses -->
                            <tr class="sub-header-row">
                                <td class="text-center font-bold">5</td>
                                <td colspan="3" class="font-bold">{{ ($isBn ?? false) ? 'পরিচালন ব্যয়' : 'Expenses' }}</td>
                            </tr>
                            @if (!empty($payment['expenses']['categories']) && count($payment['expenses']['categories']) > 0)
                                @foreach ($payment['expenses']['categories'] as $cat)
                                    <tr>
                                        <td></td>
                                        <td class="pl-indent">&bull; {{ $cat['category'] }}</td>
                                        <td class="text-right">{{ format_amount($cat['period'] ?? 0, true, $isBn ?? false) }}</td>
                                        <td class="text-right">{{ format_amount($cat['cumulative'] ?? 0, true, $isBn ?? false) }}</td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td></td>
                                    <td class="pl-indent" style="font-style: italic; color: #666;">No expenses this period</td>
                                    <td class="text-right">0</td>
                                    <td class="text-right">0</td>
                                </tr>
                            @endif
                            <tr class="sub-total-row">
                                <td></td>
                                <td class="font-bold">{{ ($isBn ?? false) ? 'মোট পরিচালন ব্যয়' : 'Total Expenses' }}</td>
                                <td class="text-right font-bold">
                                    {{ format_amount($payment['expenses']['total']['period'] ?? 0, true, $isBn ?? false) }}
                                </td>
                                <td class="text-right font-bold">
                                    {{ format_amount($payment['expenses']['total']['cumulative'] ?? 0, true, $isBn ?? false) }}
                                </td>
                            </tr>

                            <!-- 6. Closing Cash at Bank -->
                            <tr>
                                <td class="text-center font-bold">6</td>
                                <td class="font-bold">{{ ($isBn ?? false) ? 'সমাপনী ব্যাংক জমা' : 'Closing Cash at Bank' }}</td>
                                <td class="text-right font-bold">
                                    {{ format_amount($payment['closing_cash_at_bank']['period'] ?? 0, true, $isBn ?? false) }}
                                </td>
                                <td class="text-right font-bold">
                                    {{ format_amount($payment['closing_cash_at_bank']['cumulative'] ?? 0, true, $isBn ?? false) }}
                                </td>
                            </tr>

                            @if ($row_diff < 0)
                                @for ($i = 0; $i < abs($row_diff); $i++)
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                    </tr>
                                @endfor
                            @endif

                            <!-- Total Payment Row -->
                            <tr class="total-row">
                                <td class="text-center font-bold">&nbsp;</td>
                                <td class="font-bold total-payment">{{ ($isBn ?? false) ? 'মোট পেমেন্ট' : 'Total Payment' }}</td>
                                <td class="text-right font-bold total-payment">
                                    {{ format_amount($payment['total']['period'] ?? 0, true, $isBn ?? false) }}
                                </td>
                                <td class="text-right font-bold total-payment">
                                    {{ format_amount($payment['total']['cumulative'] ?? 0, true, $isBn ?? false) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Footer -->
        <div class="footer">
            {{ ($isBn ?? false) ? 'পৃষ্ঠা' : 'Page' }} <span class="page-number"></span> | {{ ($isBn ?? false) ? 'তৈরির সময়:' : 'Generated on:' }} {{ ($isBn ?? false) ? to_bangla_date(now(), 'd M Y, h:i A') : now()->format('d M Y h:i A') }}
        </div>
    </div>
</body>

</html>
