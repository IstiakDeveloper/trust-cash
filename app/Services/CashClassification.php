<?php

namespace App\Services;

class CashClassification
{
    public const ACCOUNTS = ['capital', 'loan_payable', 'other_income:0', 'expense:0'];

    public static function category(string $account, string $direction): string
    {
        return match ($account) {
            'capital' => $direction === 'in' ? 'fund_in' : 'fund_out',
            'loan_payable' => 'loan_principal', 'other_income:0' => 'extra_income', 'expense:0' => 'expense',
            default => throw new \InvalidArgumentException('Choose a supported cash classification.'),
        };
    }
}
