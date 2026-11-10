<!DOCTYPE html><html><head><meta charset="UTF-8"><style>body{font-family:DejaVu Sans,sans-serif;font-size:11px}table{width:100%;border-collapse:collapse;margin-bottom:20px}th,td{border:1px solid #bbb;padding:8px}.amount{text-align:right}</style></head><body>
<h1>Balance Sheet</h1><p>Financial position as of {{ $end_date }}</p>
@foreach(['Assets'=>$property_and_assets,'Liabilities and Equity'=>$fund_and_liabilities] as $title=>$section)
<h2>{{ $title }}</h2><table><tbody>@foreach($section['rows'] as $row)<tr><td>{{ $row['name'] }}</td><td class="amount">{{ format_amount($row['amount'],true,$isBn) }}</td></tr>@endforeach<tr><th>Total</th><th class="amount">{{ format_amount($section['total'],true,$isBn) }}</th></tr></tbody></table>
@endforeach
@foreach($reconciliation as $name=>$amount) @if(bccomp($amount,'0',6)!==0)<p>Reconciliation required: {{ str_replace('_',' ',$name) }} = {{ $amount }}</p>@endif @endforeach
</body></html>
