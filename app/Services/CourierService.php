<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CourierService
{
    public function __construct(
        protected FraudCheckService $fraudService
    ) {}

    /**
     * Compute the exact Cash on Delivery (COD) collection amount in BDT.
     * Takes into account whether advance delivery fee (৳100/৳150) has already been captured via bKash/Nagad.
     */
    public function calculateCodAmount(Order $order): float
    {
        // 1. If entire order was prepaid (card, full bKash, etc.)
        if ($order->payment_status === 'paid') {
            return 0.00;
        }

        $total = (float) $order->total;

        // 2. If customer paid advance delivery fee via bKash/Nagad, deduct from COD collectable
        if ($order->advance_payment_status === 'paid' && $order->advance_delivery_charge > 0) {
            return max(0.00, round($total - (float) $order->advance_delivery_charge, 2));
        }

        return round($total, 2);
    }

    /**
     * Dispatch an order to a Bangladeshi courier service (Steadfast, Pathao, RedX).
     */
    public function dispatchOrder(Order $order, string $provider = 'steadfast', array $options = []): array
    {
        $shippingAddress = $order->shipping_address ?? [];
        $recipientName = $shippingAddress['name'] ?? $order->customer?->name ?? 'Valued Customer';
        $rawPhone = $shippingAddress['phone'] ?? $order->customer?->phone ?? '';
        $recipientPhone = $this->fraudService->normalizeBdPhone($rawPhone) ?? $rawPhone;

        $recipientAddress = is_array($shippingAddress)
            ? implode(', ', array_filter([
                $shippingAddress['address'] ?? $shippingAddress['address_line_1'] ?? '',
                $shippingAddress['area'] ?? '',
                $shippingAddress['city'] ?? '',
                $shippingAddress['state'] ?? '',
            ]))
            : (string) $shippingAddress;

        if (empty($recipientAddress)) {
            $recipientAddress = 'Dhaka, Bangladesh';
        }

        $codAmount = $this->calculateCodAmount($order);
        $orderNote = $options['note'] ?? $order->notes ?? "Order #{$order->order_number}";

        $result = match ($provider) {
            'steadfast' => $this->dispatchToSteadfast($order, $recipientName, $recipientPhone, $recipientAddress, $codAmount, $orderNote),
            'pathao' => $this->dispatchToPathao($order, $recipientName, $recipientPhone, $recipientAddress, $codAmount, $orderNote),
            default => $this->dispatchToSteadfast($order, $recipientName, $recipientPhone, $recipientAddress, $codAmount, $orderNote),
        };

        if ($result['success']) {
            $updateData = [
                'courier_provider' => $provider,
                'courier_consignment_id' => $result['consignment_id'],
                'courier_tracking_code' => $result['tracking_code'],
                'courier_status' => $result['status'] ?? 'pending',
                'courier_cod_amount' => $codAmount,
                'courier_dispatched_at' => now(),
                'courier_payload' => $result['payload'] ?? [],
            ];

            // Transition fulfillment state to shipped
            if (in_array($order->status, ['pending', 'processing'])) {
                $updateData['status'] = 'shipped';
                if (! $order->shipped_at) {
                    $updateData['shipped_at'] = now();
                }
            }

            $order->update($updateData);
        }

        return $result;
    }

    /**
     * Dispatch parcel to Steadfast Courier.
     * Portal: https://portal.steadfast.com.bd
     */
    protected function dispatchToSteadfast(Order $order, string $name, string $phone, string $address, float $cod, string $note): array
    {
        $apiKey = Setting::get('steadfast_api_key') ?? Setting::get('fraud_courier_api_key');
        $secretKey = Setting::get('steadfast_secret_key');

        // Live API call when credentials are configured
        if (! empty($apiKey) && ! empty($secretKey)) {
            try {
                $response = Http::timeout(8)
                    ->withHeaders([
                        'Api-Key' => $apiKey,
                        'Secret-Key' => $secretKey,
                        'Content-Type' => 'application/json',
                    ])
                    ->post('https://portal.steadfast.com.bd/api/v1/create_order', [
                        'invoice' => $order->order_number,
                        'recipient_name' => $name,
                        'recipient_phone' => $phone,
                        'recipient_address' => $address,
                        'cod_amount' => $cod,
                        'note' => $note,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $consignment = $data['consignment'] ?? [];

                    return [
                        'success' => true,
                        'provider' => 'steadfast',
                        'consignment_id' => (string) ($consignment['consignment_id'] ?? $consignment['id'] ?? time()),
                        'tracking_code' => (string) ($consignment['tracking_code'] ?? $data['tracking_code'] ?? 'STF'.time()),
                        'status' => $consignment['status'] ?? 'in_review',
                        'tracking_url' => 'https://steadfast.com.bd/t/'.($consignment['tracking_code'] ?? ''),
                        'is_simulated' => false,
                        'payload' => $data,
                        'message' => 'Parcel booked successfully with Steadfast Courier.',
                    ];
                }

                $err = $response->json()['message'] ?? 'Steadfast Courier API rejected parcel booking.';
                Log::warning("Steadfast booking error: {$err}");

                return [
                    'success' => false,
                    'message' => $err,
                ];
            } catch (\Exception $e) {
                Log::error("Steadfast API exception: {$e->getMessage()}");

                return [
                    'success' => false,
                    'message' => "Steadfast Courier API error: {$e->getMessage()}",
                ];
            }
        }

        // Demo / Fallback simulation when merchant has not yet inputted API credentials
        $simConsignmentId = (string) (100000 + ($order->id * 142) + (crc32($order->order_number) % 8999));
        $simTrackingCode = 'STF'.strtoupper(substr(md5($order->order_number.$order->id), 0, 8));

        return [
            'success' => true,
            'provider' => 'steadfast',
            'consignment_id' => $simConsignmentId,
            'tracking_code' => $simTrackingCode,
            'status' => 'in_review',
            'tracking_url' => "https://steadfast.com.bd/t/{$simTrackingCode}",
            'is_simulated' => true,
            'payload' => [
                'invoice' => $order->order_number,
                'recipient_name' => $name,
                'recipient_phone' => $phone,
                'recipient_address' => $address,
                'cod_amount' => $cod,
                'note' => $note,
                'provider' => 'Steadfast Courier (Demonstration Mode)',
            ],
            'message' => 'Parcel booked with Steadfast Courier (Demo mode active until API key is set in Settings).',
        ];
    }

    /**
     * Dispatch parcel to Pathao Courier.
     * Portal: https://api-hermes.pathao.com
     */
    protected function dispatchToPathao(Order $order, string $name, string $phone, string $address, float $cod, string $note): array
    {
        $clientId = Setting::get('pathao_client_id');
        $clientSecret = Setting::get('pathao_client_secret');
        $token = Setting::get('pathao_access_token');
        $storeId = Setting::get('pathao_store_id');

        if (! empty($token) && ! empty($storeId)) {
            try {
                $response = Http::timeout(8)
                    ->withToken($token)
                    ->post('https://api-hermes.pathao.com/aladdin/api/v1/orders', [
                        'store_id' => (int) $storeId,
                        'merchant_order_id' => $order->order_number,
                        'recipient_name' => $name,
                        'recipient_phone' => $phone,
                        'recipient_address' => $address,
                        'recipient_city' => 1,
                        'recipient_zone' => 1,
                        'amount_to_collect' => (int) $cod,
                        'item_type' => 2, // Parcel
                        'delivery_type' => 48,
                        'item_weight' => '0.5',
                        'special_instruction' => $note,
                    ]);

                if ($response->successful()) {
                    $data = $response->json()['data'] ?? [];

                    return [
                        'success' => true,
                        'provider' => 'pathao',
                        'consignment_id' => (string) ($data['consignment_id'] ?? time()),
                        'tracking_code' => (string) ($data['consignment_id'] ?? 'PTH'.time()),
                        'status' => 'pending',
                        'tracking_url' => 'https://pathao.com/courier/tracking/?consignment_id='.($data['consignment_id'] ?? ''),
                        'is_simulated' => false,
                        'payload' => $data,
                        'message' => 'Parcel booked successfully with Pathao Courier.',
                    ];
                }

                return [
                    'success' => false,
                    'message' => $response->json()['message'] ?? 'Pathao Courier API rejected order.',
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => "Pathao Courier exception: {$e->getMessage()}",
                ];
            }
        }

        // Demo fallback simulation
        $simConsignmentId = 'PTH'.strtoupper(substr(md5($order->order_number), 0, 9));

        return [
            'success' => true,
            'provider' => 'pathao',
            'consignment_id' => $simConsignmentId,
            'tracking_code' => $simConsignmentId,
            'status' => 'pending',
            'tracking_url' => "https://pathao.com/courier/tracking/?consignment_id={$simConsignmentId}",
            'is_simulated' => true,
            'payload' => [
                'merchant_order_id' => $order->order_number,
                'recipient_name' => $name,
                'recipient_phone' => $phone,
                'recipient_address' => $address,
                'amount_to_collect' => $cod,
                'provider' => 'Pathao Courier (Demonstration Mode)',
            ],
            'message' => 'Parcel booked with Pathao Courier (Demo mode active until credentials are configured).',
        ];
    }

    /**
     * Check real-time delivery status of a consignment.
     */
    public function checkStatus(Order $order): array
    {
        if (empty($order->courier_consignment_id)) {
            return [
                'status' => 'unbooked',
                'message' => 'Order has not been dispatched to a courier yet.',
            ];
        }

        $apiKey = Setting::get('steadfast_api_key') ?? Setting::get('fraud_courier_api_key');
        $secretKey = Setting::get('steadfast_secret_key');

        if ($order->courier_provider === 'steadfast' && $apiKey && $secretKey) {
            try {
                $response = Http::timeout(6)->withHeaders([
                    'Api-Key' => $apiKey,
                    'Secret-Key' => $secretKey,
                ])->get("https://portal.steadfast.com.bd/api/v1/status_by_cid/{$order->courier_consignment_id}");

                if ($response->successful()) {
                    $status = $response->json()['delivery_status'] ?? $order->courier_status;
                    $order->update(['courier_status' => $status]);

                    return [
                        'status' => $status,
                        'message' => "Steadfast status: {$status}",
                    ];
                }
            } catch (\Exception $e) {
                Log::warning("Steadfast status check failed: {$e->getMessage()}");
            }
        }

        return [
            'status' => $order->courier_status ?? 'in_review',
            'message' => 'Status checked: '.($order->courier_status ?? 'in_review'),
        ];
    }

    /**
     * Generate customer tracking URL.
     */
    public function getTrackingUrl(Order $order): ?string
    {
        if (empty($order->courier_tracking_code)) {
            return null;
        }

        return match ($order->courier_provider) {
            'steadfast' => "https://steadfast.com.bd/t/{$order->courier_tracking_code}",
            'pathao' => "https://pathao.com/courier/tracking/?consignment_id={$order->courier_tracking_code}",
            'redx' => "https://redx.com.bd/track-order?trackingId={$order->courier_tracking_code}",
            default => "https://steadfast.com.bd/t/{$order->courier_tracking_code}",
        };
    }
}
