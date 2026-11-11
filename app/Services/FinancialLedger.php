<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Tenant-local, balanced, append-only journal. Changes reverse old entries before replacement. */
class FinancialLedger
{
    public bool $initializing = false;

    public function sync(string $type, int $id, array $lines, string $date): void
    {
        if (! $this->initializing && $date > now()->toDateString()) {
            throw ValidationException::withMessages(['date' => 'Future accounting postings are not allowed.']);
        }
        if (! $this->initializing) {
            app(LegacyInventoryCarryForward::class)->ensure();
        }
        if (! $this->initializing && ! DB::table('inventory_policies')->where('id', 1)->value('accounting_ready')) {
            throw ValidationException::withMessages(['accounting' => 'Reconcile historical accounting before posting new transactions.']);
        }
        $locked = DB::table('inventory_policies')->where('id', 1)->value('locked_through');
        DB::transaction(function () use ($type, $id, $lines, $date, $locked) {
            $total = '0';
            foreach ($lines as $line) {
                $total = bcadd($total, (string) $line['amount'], 6);
            }
            if (bccomp($total, '0', 6) !== 0) {
                throw new \LogicException('Unbalanced journal document: '.$type.' #'.$id);
            }
            $fingerprint = hash('sha256', json_encode($lines, JSON_THROW_ON_ERROR));
            $document = DB::table('ledger_documents')->where('source_type', $type)->where('source_id', $id)->lockForUpdate()->first();
            if ($document && $document->fingerprint === $fingerprint) {
                return;
            }
            if ($locked && $date <= $locked) {
                throw ValidationException::withMessages(['date' => 'This accounting period is locked. Post a correction in an open period.']);
            }
            $version = $document ? $document->version + 1 : 1;
            if ($document) {
                foreach (json_decode($document->lines, true, 512, JSON_THROW_ON_ERROR) as $line) {
                    DB::table('ledger_lines')->insert([
                        'document_id' => $document->id, 'version' => $version, 'date' => now()->toDateString(),
                        'account' => $line['account'], 'amount' => bcmul((string) $line['amount'], '-1', 6), 'reversal' => true, 'created_at' => now(),
                        'category' => $line['category'] ?? null,
                        'bank_account_id' => $line['bank_account_id'] ?? null,
                        'created_by' => auth()->id(),
                    ]);
                }
                $date = now()->toDateString();
                DB::table('ledger_documents')->where('id', $document->id)->update(['version' => $version, 'fingerprint' => $fingerprint, 'lines' => json_encode($lines)]);
                $documentId = $document->id;
            } else {
                $documentId = DB::table('ledger_documents')->insertGetId([
                    'source_type' => $type, 'source_id' => $id, 'version' => 1, 'fingerprint' => $fingerprint, 'lines' => json_encode($lines),
                ]);
            }
            foreach ($lines as $line) {
                DB::table('ledger_lines')->insert([
                    'document_id' => $documentId, 'version' => $version, 'date' => $date,
                    'account' => $line['account'], 'amount' => $line['amount'], 'reversal' => false, 'created_at' => now(),
                    'category' => $line['category'] ?? null,
                    'bank_account_id' => $line['bank_account_id'] ?? null,
                    'created_by' => auth()->id(),
                ]);
            }
        });
    }

    public function pair(string $type, int $id, string $debit, string $credit, $amount, string $date, ?string $category = null, ?int $bankAccountId = null): void
    {
        $this->sync($type, $id, [
            ['account' => $debit, 'amount' => bcadd((string) $amount, '0', 6), 'category' => $category, 'bank_account_id' => $bankAccountId],
            ['account' => $credit, 'amount' => bcmul((string) $amount, '-1', 6), 'category' => $category, 'bank_account_id' => $bankAccountId],
        ], $date);
    }

    public function reverse(string $type, int $id): void
    {
        $this->sync($type, $id, [], now()->toDateString());
    }

    public function balances(string $end, ?string $start = null): array
    {
        app(LegacyInventoryCarryForward::class)->ensure();
        if (! DB::table('inventory_policies')->where('id', 1)->value('accounting_ready')) {
            throw ValidationException::withMessages(['accounting' => 'Historical accounting must be reconciled before financial reports are available.']);
        }
        $balances = [];
        foreach (DB::table('ledger_lines')->where('date', '<=', $end)->when($start, fn ($q) => $q->where('date', '>=', $start))->orderBy('id')->get() as $line) {
            $balances[$line->account] = bcadd($balances[$line->account] ?? '0', (string) $line->amount, 6);
        }

        return $balances;
    }

    public function total(array $balances, string $prefix): string
    {
        $total = '0';
        foreach ($balances as $account => $value) {
            if ($account === $prefix || str_starts_with($account, $prefix.':')) {
                $total = bcadd($total, $value, 6);
            }
        }

        return $total;
    }
}
