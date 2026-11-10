<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ReceiptPaymentExport;
use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Services\CashReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ReceiptPaymentController extends Controller
{
    private function data(Request $request): array
    {
        $request->validate([
            'start_date' => 'nullable|date', 'end_date' => 'nullable|date|after_or_equal:start_date',
            'year' => 'nullable|integer|min:1900|max:9999', 'month' => 'nullable|integer|between:1,12',
            'bank_account_id' => 'nullable|string',
        ]);
        $month = $request->filled(['year', 'month']) ? Carbon::create($request->integer('year'), $request->integer('month'), 1) : now();
        $start = $request->filled('start_date') ? Carbon::parse($request->start_date)->toDateString() : $month->copy()->startOfMonth()->toDateString();
        $end = $request->filled('end_date') ? Carbon::parse($request->end_date)->toDateString() : $month->copy()->endOfMonth()->toDateString();
        if ($start > $end) {
            throw ValidationException::withMessages(['end_date' => 'End date must follow start date.']);
        }
        $id = $request->input('bank_account_id');
        if ($id !== null && $id !== '' && $id !== 'all' && (! ctype_digit((string) $id) || (int) $id < 1)) {
            throw ValidationException::withMessages(['bank_account_id' => 'Select a valid account.']);
        }
        $id = $id && $id !== 'all' ? (int) $id : null;
        $account = $id ? BankAccount::withTrashed()->findOrFail($id) : null;

        return app(CashReport::class)->build($start, $end, $id) + [
            'bankAccounts' => BankAccount::withTrashed()->get(['id', 'account_name', 'bank_name', 'status']),
            'selectedBankAccountId' => $id, 'bank_account' => $account,
            'start_date' => $start, 'end_date' => $end,
            'filters' => ['start_date' => $start, 'end_date' => $end, 'bank_account_id' => $id],
        ];
    }

    public function index(Request $request)
    {
        return Inertia::render('Admin/ReceiptPayment/Index', $this->data($request));
    }

    public function downloadPdf(Request $request)
    {
        $data = $this->data($request);

        return Pdf::loadView('pdf.receipt-payment', $data + ['isBn' => $request->input('locale', 'bn') === 'bn'])
            ->setPaper('a4', 'landscape')->download('receipt-payment-'.$data['start_date'].'-to-'.$data['end_date'].'.pdf');
    }

    public function downloadExcel(Request $request)
    {
        $data = $this->data($request);

        return Excel::download(new ReceiptPaymentExport(
            $data['receipt'], $data['payment'], $data['bank_account']?->account_name ?? 'All accounts', $data['start_date'], $data['end_date']
        ), 'receipt-payment-'.$data['start_date'].'-to-'.$data['end_date'].'.xlsx');
    }
}
