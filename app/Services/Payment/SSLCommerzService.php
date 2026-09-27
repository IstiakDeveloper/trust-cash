<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SSLCommerzService
{
    protected string $storeId;
    protected string $storePassword;
    protected bool $isSandbox;
    protected string $apiUrl;

    public function __construct()
    {
        $this->storeId = env('SSLCOMMERZ_STORE_ID', 'testbox');
        $this->storePassword = env('SSLCOMMERZ_STORE_PASSWORD', 'qwerty');
        $this->isSandbox = (bool) env('SSLCOMMERZ_IS_SANDBOX', true);

        $this->apiUrl = $this->isSandbox
            ? 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php'
            : 'https://securepay.sslcommerz.com/gwprocess/v4/api.php';
    }

    public function initiatePayment(array $data)
    {
        $payload = [
            'store_id'         => $this->storeId,
            'store_passwd'     => $this->storePassword,
            'total_amount'     => $data['amount'],
            'currency'         => 'BDT',
            'tran_id'          => $data['tran_id'],
            'success_url'      => route('payment.sslcommerz.success'),
            'fail_url'         => route('payment.sslcommerz.fail'),
            'cancel_url'       => route('payment.sslcommerz.cancel'),
            'ipn_url'          => route('payment.sslcommerz.ipn'),
            'cus_name'         => $data['cus_name'] ?? 'Store Owner',
            'cus_email'        => $data['cus_email'] ?? 'customer@example.com',
            'cus_add1'         => $data['cus_address'] ?? 'Dhaka, Bangladesh',
            'cus_city'         => 'Dhaka',
            'cus_country'      => 'Bangladesh',
            'cus_phone'        => $data['cus_phone'] ?? '01700000000',
            'shipping_method'  => 'NO',
            'product_name'     => $data['product_name'] ?? 'TrustCash POS SaaS Subscription',
            'product_category' => 'Software',
            'product_profile'  => 'non-physical-goods',
        ];

        try {
            $response = Http::asForm()->post($this->apiUrl, $payload);
            $result = $response->json();

            if (isset($result['status']) && $result['status'] === 'SUCCESS') {
                return [
                    'success'     => true,
                    'gateway_url' => $result['GatewayPageURL'],
                ];
            }

            return [
                'success' => false,
                'message' => $result['failedreason'] ?? 'SSLCommerz session creation failed',
            ];
        } catch (\Exception $e) {
            Log::error('SSLCommerz initiation error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
