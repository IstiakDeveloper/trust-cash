<!DOCTYPE html>
<html><head><meta charset="UTF-8"><style>body{font-family:DejaVu Sans,sans-serif;font-size:10px;color:#111}table{width:100%;border-collapse:collapse;margin-bottom:20px}th,td{border:1px solid #bbb;padding:7px}th{background:#eee}td.amount{text-align:right}h1{font-size:18px}</style></head><body>
<h1>Statement of Receipts and Payments</h1><p>{{ $start_date }} — {{ $end_date }} · {{ $bank_account?->account_name ?? 'All accounts (consolidated)' }}</p>
@foreach ([['title'=>'Receipts','rows'=>array_merge([array_merge(['name'=>'Opening cash and bank'],$receipt['opening_cash_on_bank'])],$receipt['rows']),'total'=>$receipt['total']],['title'=>'Payments','rows'=>array_merge($payment['rows'],[array_merge(['name'=>'Closing cash and bank'],$payment['closing_cash_at_bank'])]),'total'=>$payment['total']]] as $section)
<h2>{{ $section['title'] }}</h2><table><thead><tr><th>Particulars</th><th>Period</th><th>Cumulative</th></tr></thead><tbody>
@foreach($section['rows'] as $row)<tr><td>{{ $row['name'] }}</td><td class="amount">{{ format_amount($row['period'],true,$isBn) }}</td><td class="amount">{{ format_amount($row['cumulative'],true,$isBn) }}</td></tr>@endforeach
<tr><th>Total</th><th>{{ format_amount($section['total']['period'],true,$isBn) }}</th><th>{{ format_amount($section['total']['cumulative'],true,$isBn) }}</th></tr></tbody></table>
@endforeach
@if(bccomp($reconciliation_difference,'0',6)!==0)<p>Reconciliation difference: {{ $reconciliation_difference }}</p>@endif
</body></html>
