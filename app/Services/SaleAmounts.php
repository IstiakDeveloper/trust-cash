<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class SaleAmounts
{
    public function allocate(array $weights, $total): array
    {
        $sum = '0';
        foreach ($weights as $weight) {
            $sum = bcadd($sum, (string) $weight, 12);
        }
        if (! $weights) {
            return [];
        }
        $values = [];
        $fractions = [];
        $allocated = '0';
        foreach (array_values($weights) as $i => $weight) {
            $raw = bccomp($sum, '0', 12) > 0 ? bcmul(bcdiv((string) $weight, $sum, 16), (string) $total, 12) : bcdiv((string) $total, (string) count($weights), 12);
            $value = bcadd($raw, '0', 2);
            $values[$i] = $value;
            $fractions[$i] = bcsub($raw, $value, 12);
            $allocated = bcadd($allocated, $value, 2);
        }
        arsort($fractions, SORT_NUMERIC);
        $residual = (int) bcmul(bcsub((string) $total, $allocated, 2), '100', 0);
        foreach (array_keys($fractions) as $i) {
            if ($residual-- <= 0) {
                break;
            } $values[$i] = bcadd($values[$i], '0.01', 2);
        }

        return $values;
    }

    public function lineSubtotals(array $items, $subtotal): array
    {
        $values = [];
        $fractions = [];
        $allocated = '0';
        foreach (array_values($items) as $i => $item) {
            $raw = bcmul(DecimalInput::normalize($item['quantity'], 6), DecimalInput::normalize($item['unit_price'], 2), 6);
            $value = bcadd($raw, '0', 2);
            $values[] = $value;
            $fractions[$i] = bcsub($raw, $value, 6);
            $allocated = bcadd($allocated, $value, 2);
        }
        arsort($fractions, SORT_NUMERIC);
        $residual = (int) bcmul(bcsub((string) $subtotal, $allocated, 2), '100', 0);
        foreach (array_keys($fractions) as $i) {
            if ($residual-- <= 0) {
                break;
            }
            $values[$i] = bcadd($values[$i], '0.01', 2);
        }

        return $values;
    }

    public function validate(array $items, $subtotal, $discount, $total, $paid): void
    {
        $calculated = '0';
        foreach ($items as $item) {
            $item['quantity'] = DecimalInput::normalize($item['quantity'], 6);
            $item['unit_price'] = DecimalInput::normalize($item['unit_price'], 2);
            if (bccomp((string) $item['quantity'], '0', 6) <= 0 || bccomp((string) $item['unit_price'], '0', 2) < 0) {
                $this->fail();
            }
            $calculated = bcadd($calculated, bcmul((string) $item['quantity'], (string) $item['unit_price'], 6), 6);
        }
        $calculated = bcadd($calculated, '0.005', 2);
        if (! $items || bccomp($calculated, (string) $subtotal, 2) !== 0
            || bccomp((string) $discount, '0', 2) < 0 || bccomp((string) $discount, $calculated, 2) > 0
            || bccomp(bcsub($calculated, (string) $discount, 2), (string) $total, 2) !== 0
            || bccomp((string) $paid, '0', 2) < 0 || bccomp((string) $paid, (string) $total, 2) > 0) {
            $this->fail();
        }
    }

    private function fail(): never
    {
        throw ValidationException::withMessages(['total' => 'Invoice totals do not match the item quantities, prices, discount and payment.']);
    }
}
