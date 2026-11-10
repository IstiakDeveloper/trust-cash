<?php

namespace App\Services;

class CashTransactionCategory
{
    public static function infer(?string $description): string
    {
        $text = strtolower(trim($description ?? ''));

        return match (true) {
            str_starts_with($text, 'customer sale refund'), str_starts_with($text, 'reversed payment'),
            str_starts_with($text, 'payment reduction') => 'customer_refund',
            str_starts_with($text, 'payment received'), str_starts_with($text, 'additional payment') => 'sale_collection',
            str_starts_with($text, 'stock purchase') => 'stock_purchase',
            str_starts_with($text, 'supplier payment'), str_starts_with($text, 'purchase payment') => 'supplier_payment',
            str_starts_with($text, 'fixed asset') => 'fixed_asset',
            str_starts_with($text, 'extra income') => 'extra_income',
            str_starts_with($text, 'expense:') => 'expense',
            str_starts_with($text, 'fund in') => 'fund_in',
            str_starts_with($text, 'fund out') => 'fund_out',
            default => 'other',
        };
    }
}
