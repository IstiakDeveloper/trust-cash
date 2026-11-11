<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $sale->invoice_no }}</title>
    <style>
        @page {
            size: {{ $widthMm }}mm auto;
            margin: 1mm 1.5mm;
        }

        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            padding: 0;
            color: #000;
            background: #fff;
            font-family: "Nirmala UI", "Noto Sans Bengali", "Segoe UI", sans-serif;
            font-size: {{ $widthMm < 70 ? 8 : 9 }}px;
            line-height: 1.25;
        }

        .slip { width: {{ $widthMm - 3 }}mm; margin: 0 auto; }

        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: 700; }
        .muted { font-size: {{ $widthMm < 70 ? 7 : 8 }}px; }
        .shop { font-size: {{ $widthMm < 70 ? 11 : 12 }}px; font-weight: 800; line-height: 1.1; }
        .title { margin-top: 1px; font-size: {{ $widthMm < 70 ? 8 : 9 }}px; letter-spacing: 0.4px; }
        .rule { border: 0; border-top: 1px dashed #000; margin: 3px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; padding: 0; }
        .meta td { padding: 0; }
        .item-name { font-weight: 700; padding-right: 3px; }
        .qty, .amt { white-space: nowrap; }
        .grand td { font-size: {{ $widthMm < 70 ? 9 : 11 }}px; font-weight: 800; border-top: 1px solid #000; padding-top: 2px; }
        .thanks { margin-top: 3px; font-weight: 700; }
        .copy { margin-top: 2px; }

        @media screen {
            body { background: #e5e7eb; }
            .slip {
                width: {{ $widthMm }}mm;
                margin: 12px auto;
                padding: 6px 6px 8px;
                background: #fff;
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            }
        }

        @media print {
            body { background: #fff; }
            .slip { width: auto; margin: 0; padding: 0; box-shadow: none; }
        }
    </style>
</head>
<body>
@php
    $money = fn ($amount) => number_format((float) $amount, 2);
    $qty = function ($amount) {
        $text = number_format((float) $amount, 2, '.', '');
        return rtrim(rtrim($text, '0'), '.');
    };
    $change = max(0, (float) $sale->paid - (float) $sale->total);
@endphp
<div class="slip">
    <div class="center">
        <div class="shop">{{ business_name() }}</div>
        @if (business_address())
            <div class="muted">{{ business_address() }}</div>
        @endif
        @if (business_phone())
            <div class="muted">{{ business_phone() }}</div>
        @endif
        <div class="bold title">SALES RECEIPT</div>
    </div>

    <hr class="rule">

    <table class="meta">
        <tr>
            <td>Invoice</td>
            <td class="right bold">{{ $sale->invoice_no }}</td>
        </tr>
        <tr>
            <td>Date</td>
            <td class="right">{{ $sale->created_at?->timezone(config('app.timezone'))->format('d/m/Y h:i A') }}</td>
        </tr>
        @if ($sale->createdBy)
            <tr>
                <td>Cashier</td>
                <td class="right">{{ $sale->createdBy->name }}</td>
            </tr>
        @endif
        <tr>
            <td>Customer</td>
            <td class="right">{{ $sale->customer->name ?? 'Walk-in' }}</td>
        </tr>
        @if ($sale->customer?->phone)
            <tr>
                <td>Phone</td>
                <td class="right">{{ $sale->customer->phone }}</td>
            </tr>
        @endif
        @if ($sale->bankAccount)
            <tr>
                <td>Paid from</td>
                <td class="right">{{ $sale->bankAccount->bank_name }}</td>
            </tr>
        @endif
    </table>

    <hr class="rule">

    <table>
        @foreach ($sale->saleItems as $item)
            <tr>
                <td class="item-name">{{ $item->product->name ?? 'Item' }}</td>
                <td class="right qty">{{ $qty($item->quantity) }} x {{ $money($item->unit_price) }}</td>
                <td class="right amt">{{ $money($item->subtotal) }}</td>
            </tr>
        @endforeach
    </table>

    <hr class="rule">

    <table>
        <tr>
            <td>Subtotal</td>
            <td class="right">{{ $money($sale->subtotal) }}</td>
        </tr>
        @if ((float) $sale->discount > 0)
            <tr>
                <td>Discount</td>
                <td class="right">-{{ $money($sale->discount) }}</td>
            </tr>
        @endif
        @if ((float) $sale->tax > 0)
            <tr>
                <td>Tax</td>
                <td class="right">{{ $money($sale->tax) }}</td>
            </tr>
        @endif
        <tr class="grand">
            <td>TOTAL</td>
            <td class="right">{{ $money($sale->total) }}</td>
        </tr>
        <tr>
            <td>Paid</td>
            <td class="right">{{ $money($sale->paid) }}</td>
        </tr>
        @if ((float) $sale->due > 0)
            <tr class="bold">
                <td>Due</td>
                <td class="right">{{ $money($sale->due) }}</td>
            </tr>
        @endif
        @if ($change > 0)
            <tr class="bold">
                <td>Change</td>
                <td class="right">{{ $money($change) }}</td>
            </tr>
        @endif
    </table>

    <div class="center">
        <div class="thanks">Thank you</div>
        @if ($footer)
            <div class="muted">{{ $footer }}</div>
        @endif
        <div class="muted copy">{{ $sale->invoice_no }}</div>
    </div>
</div>
<script>
    window.addEventListener('load', function () {
        window.print();
    });
</script>
</body>
</html>
