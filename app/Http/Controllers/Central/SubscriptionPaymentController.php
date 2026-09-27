<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantPayment;
use App\Models\TenantSubscription;
use App\Services\Payment\SSLCommerzService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SubscriptionPaymentController extends Controller
{
    protected SSLCommerzService $sslCommerz;

    public function __construct(SSLCommerzService $sslCommerz)
    {
        $this->sslCommerz = $sslCommerz;
    }

    public function initiate(Request $request)
    {
        $validated = $request->validate([
            'tenant_id'     => 'required|exists:tenants,id',
            'plan_id'       => 'required|exists:plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
        ]);

        $tenant = Tenant::findOrFail($validated['tenant_id']);
        $plan = Plan::findOrFail($validated['plan_id']);

        $amount = $validated['billing_cycle'] === 'yearly' ? $plan->price_yearly : $plan->price_monthly;
        $tranId = 'SUB-' . date('Ymd') . '-' . strtoupper(Str::random(8));

        // Create pending payment record
        TenantPayment::create([
            'tenant_id'        => $tenant->id,
            'amount'           => $amount,
            'currency'         => 'BDT',
            'payment_method'   => 'sslcommerz',
            'transaction_id'   => $tranId,
            'status'           => 'pending',
            'note'             => "Subscription renewal: {$plan->name} ({$validated['billing_cycle']})",
        ]);

        $session = $this->sslCommerz->initiatePayment([
            'amount'       => $amount,
            'tran_id'      => $tranId,
            'cus_name'     => $tenant->name,
            'cus_email'    => $tenant->email,
            'product_name' => "TrustCash POS {$plan->name} Plan",
        ]);

        if ($session['success'] && !empty($session['gateway_url'])) {
            return redirect()->away($session['gateway_url']);
        }

        return redirect()->back()->with('error', $session['message'] ?? 'Could not connect to payment gateway.');
    }

    public function success(Request $request)
    {
        $tranId = $request->input('tran_id');
        $payment = TenantPayment::where('transaction_id', $tranId)->firstOrFail();

        $payment->update([
            'status'           => 'paid',
            'paid_at'          => now(),
            'gateway_response' => $request->all(),
        ]);

        $tenant = $payment->tenant;
        if ($tenant) {
            $plan = $tenant->plan;
            $billingCycle = str_contains($payment->note ?? '', 'yearly') ? 'yearly' : 'monthly';
            $extensionPeriod = $billingCycle === 'yearly' ? now()->addYear() : now()->addMonth();

            $subscription = TenantSubscription::updateOrCreate(
                ['tenant_id' => $tenant->id],
                [
                    'plan_id'       => $tenant->plan_id,
                    'starts_at'     => now(),
                    'ends_at'       => $extensionPeriod,
                    'status'        => 'active',
                    'billing_cycle' => $billingCycle,
                    'auto_renew'    => true,
                ]
            );

            $payment->update(['subscription_id' => $subscription->id]);
            $tenant->update(['status' => 'active']);
        }

        return redirect()->route('home')->with('success', 'Your subscription payment was successful! Your store is active.');
    }

    public function fail(Request $request)
    {
        $tranId = $request->input('tran_id');
        if ($payment = TenantPayment::where('transaction_id', $tranId)->first()) {
            $payment->update(['status' => 'failed', 'gateway_response' => $request->all()]);
        }

        return redirect()->route('home')->with('error', 'Payment was unsuccessful. Please try again.');
    }

    public function cancel(Request $request)
    {
        return redirect()->route('home')->with('info', 'Payment was cancelled.');
    }
}
