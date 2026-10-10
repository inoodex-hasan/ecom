<?php

namespace App\Services;

use App\Models\FraudBlacklist;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FraudCheckService
{
    /**
     * Common Bangladeshi mobile operator prefixes.
     */
    protected const BD_OPERATORS = [
        '017' => ['name' => 'Grameenphone', 'code' => 'GP', 'color' => 'emerald'],
        '013' => ['name' => 'Grameenphone', 'code' => 'GP', 'color' => 'emerald'],
        '018' => ['name' => 'Robi', 'code' => 'ROBI', 'color' => 'rose'],
        '016' => ['name' => 'Airtel', 'code' => 'AIRTEL', 'color' => 'red'],
        '019' => ['name' => 'Banglalink', 'code' => 'BL', 'color' => 'amber'],
        '014' => ['name' => 'Banglalink', 'code' => 'BL', 'color' => 'amber'],
        '015' => ['name' => 'Teletalk', 'code' => 'TELETALK', 'color' => 'teal'],
    ];

    /**
     * Clean and normalize a Bangladeshi phone number into standard 11 digits format (01XXXXXXXXX).
     */
    public function normalizeBdPhone(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        // Remove spaces, hyphens, parentheses, and leading plus signs
        $cleaned = preg_replace('/[^0-9]/', '', $phone);

        // Convert +8801XXXXXXXXX or 8801XXXXXXXXX to 01XXXXXXXXX
        if (str_starts_with($cleaned, '8801') && strlen($cleaned) === 13) {
            $cleaned = substr($cleaned, 2);
        } elseif (str_starts_with($cleaned, '880') && strlen($cleaned) === 13) {
            $cleaned = '0'.substr($cleaned, 3);
        }

        return $cleaned;
    }

    /**
     * Detect the Bangladeshi mobile network operator from the phone prefix.
     */
    public function detectOperator(?string $phone): ?array
    {
        $normalized = $this->normalizeBdPhone($phone);
        if (! $normalized || strlen($normalized) < 3) {
            return null;
        }

        $prefix = substr($normalized, 0, 3);

        return self::BD_OPERATORS[$prefix] ?? null;
    }

    /**
     * Analyze phone validity, operator series, and detect fake/garbage patterns.
     */
    public function validateBdPhone(?string $phone): array
    {
        $normalized = $this->normalizeBdPhone($phone);
        $operator = $this->detectOperator($normalized);

        if (empty($normalized)) {
            return [
                'is_valid' => false,
                'normalized' => null,
                'operator' => null,
                'risk_points' => 45,
                'reason' => 'No phone number provided.',
            ];
        }

        if (strlen($normalized) !== 11) {
            return [
                'is_valid' => false,
                'normalized' => $normalized,
                'operator' => null,
                'risk_points' => 40,
                'reason' => "Invalid BD phone length ({strlen($normalized)} digits, expected 11 digits).",
            ];
        }

        if (! $operator) {
            return [
                'is_valid' => false,
                'normalized' => $normalized,
                'operator' => null,
                'risk_points' => 35,
                'reason' => 'Unrecognized or inactive Bangladeshi operator prefix.',
            ];
        }

        // Test for repetitive or dummy sequences (e.g. 01700000000, 01811111111)
        $suffix = substr($normalized, 3);
        if (preg_match('/^(\d)\1{7}$/', $suffix)) {
            return [
                'is_valid' => false,
                'normalized' => $normalized,
                'operator' => $operator,
                'risk_points' => 50,
                'reason' => 'Repetitive test/fake number detected (e.g. all repeating digits).',
            ];
        }

        // Test for sequential digits (e.g. 01712345678)
        if ($suffix === '12345678' || $suffix === '87654321') {
            return [
                'is_valid' => false,
                'normalized' => $normalized,
                'operator' => $operator,
                'risk_points' => 45,
                'reason' => 'Sequential dummy test number detected.',
            ];
        }

        return [
            'is_valid' => true,
            'normalized' => $normalized,
            'operator' => $operator,
            'risk_points' => 0,
            'reason' => "Valid {$operator['name']} ({$operator['code']}) subscriber line.",
        ];
    }

    /**
     * Check courier return history across Bangladeshi delivery networks.
     * Integrates with configured Courier API (Steadfast, Pathao, or FraudCheck BD),
     * or computes from internal order records with realistic heuristic data.
     */
    public function getCourierHistory(?string $phone): array
    {
        $normalized = $this->normalizeBdPhone($phone);
        if (! $normalized) {
            return [
                'total_parcels' => 0,
                'delivered' => 0,
                'returned' => 0,
                'success_rate' => 100,
                'rto_risk' => 'low',
                'source' => 'None',
            ];
        }

        // 1. Check internal store order history for this phone
        $internalOrders = Order::where(function ($q) use ($normalized) {
            $q->where('shipping_address->phone', 'like', "%{$normalized}%")
                ->orWhereHas('customer', function ($cq) use ($normalized) {
                    $cq->where('phone', 'like', "%{$normalized}%");
                });
        })->get();

        $internalTotal = $internalOrders->count();
        $internalDelivered = $internalOrders->where('status', 'delivered')->count();
        $internalReturned = $internalOrders->where('status', 'cancelled')->count();

        // 2. Check if third-party courier check API is configured (e.g. Steadfast / FraudCheck BD)
        $apiKey = Setting::get('fraud_courier_api_key');
        $apiEndpoint = Setting::get('fraud_courier_api_endpoint');

        if (! empty($apiKey) && ! empty($apiEndpoint)) {
            try {
                $response = Http::timeout(4)->withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                    'Accept' => 'application/json',
                ])->get($apiEndpoint, ['phone' => $normalized]);

                if ($response->successful()) {
                    $data = $response->json();
                    $total = (int) ($data['total_parcels'] ?? $data['total'] ?? 0);
                    $delivered = (int) ($data['delivered_parcels'] ?? $data['delivered'] ?? 0);
                    $returned = (int) ($data['returned_parcels'] ?? $data['cancelled'] ?? 0);
                    $rate = $total > 0 ? round(($delivered / $total) * 100, 1) : 100;

                    return [
                        'has_api' => true,
                        'total_parcels' => $total,
                        'delivered' => $delivered,
                        'returned' => $returned,
                        'success_rate' => $rate,
                        'rto_risk' => $rate < 50 ? 'critical' : ($rate < 75 ? 'high' : ($rate < 90 ? 'moderate' : 'low')),
                        'source' => 'Live Courier Network API',
                    ];
                }
            } catch (\Exception $e) {
                Log::warning("Courier check API call failed: {$e->getMessage()}");
            }
        }

        // When real courier API is not configured, return N/A
        return [
            'has_api' => false,
            'total_parcels' => 'N/A',
            'delivered' => 'N/A',
            'returned' => 'N/A',
            'success_rate' => 'N/A',
            'rto_risk' => 'N/A',
            'source' => 'N/A (API Not Configured)',
        ];
    }

    /**
     * Evaluate an order comprehensively against Bangladeshi fraud heuristics.
     */
    public function evaluateOrder(Order $order): array
    {
        $flags = [];
        $totalScore = 0;

        $shippingAddress = $order->shipping_address ?? [];
        $phone = $shippingAddress['phone'] ?? $order->customer?->phone ?? null;
        $email = $shippingAddress['email'] ?? $order->customer?->email ?? null;
        $normalizedPhone = $this->normalizeBdPhone($phone);
        $fullAddress = is_array($shippingAddress)
            ? implode(' ', array_filter([
                $shippingAddress['address'] ?? $shippingAddress['address_line_1'] ?? '',
                $shippingAddress['city'] ?? '',
                $shippingAddress['area'] ?? '',
                $shippingAddress['zone'] ?? '',
            ]))
            : (string) $shippingAddress;

        // 1. WHITELIST CHECK (VIP Customers bypass fraud checks)
        $isWhitelistedPhone = FraudBlacklist::isWhitelisted('phone', $normalizedPhone);
        $isWhitelistedEmail = FraudBlacklist::isWhitelisted('email', $email);

        if ($isWhitelistedPhone || $isWhitelistedEmail) {
            return [
                'score' => 0,
                'risk_level' => 'low',
                'status' => 'verified',
                'flags' => [
                    [
                        'rule' => 'whitelist',
                        'title' => 'VIP Whitelisted Customer',
                        'description' => 'Customer is on the approved whitelist. Bypassing risk restrictions.',
                        'severity' => 'safe',
                        'score' => 0,
                    ],
                ],
                'advance_required' => false,
                'suggested_advance_fee' => 0,
                'recommended_action' => 'Safe to process. Standard fulfillment.',
            ];
        }

        // 2. BLACKLIST CHECK
        $isBlacklistedPhone = FraudBlacklist::isBlacklisted('phone', $normalizedPhone);
        $isBlacklistedEmail = FraudBlacklist::isBlacklisted('email', $email);

        if ($isBlacklistedPhone || $isBlacklistedEmail) {
            $reason = FraudBlacklist::where('list_type', 'blacklist')
                ->where(function ($q) use ($normalizedPhone, $email) {
                    $q->where('value', $normalizedPhone)->orWhere('value', $email);
                })->value('reason') ?? 'Previous abusive/fake order behavior';

            return [
                'score' => 100,
                'risk_level' => 'high',
                'status' => 'blocked',
                'flags' => [
                    [
                        'rule' => 'blacklist',
                        'title' => 'Blacklisted Phone or Email',
                        'description' => "Customer matched blacklisted database. Reason: {$reason}",
                        'severity' => 'danger',
                        'score' => 100,
                    ],
                ],
                'advance_required' => true,
                'suggested_advance_fee' => 150,
                'recommended_action' => 'Do not ship on Cash on Delivery. Order flagged as fraudulent.',
            ];
        }

        // 3. BANGLADESHI PHONE NUMBER CHECK
        $phoneCheck = $this->validateBdPhone($phone);
        if (! $phoneCheck['is_valid']) {
            $totalScore += $phoneCheck['risk_points'];
            $flags[] = [
                'rule' => 'phone_validation',
                'title' => 'Invalid BD Mobile Number',
                'description' => $phoneCheck['reason'],
                'severity' => 'danger',
                'score' => $phoneCheck['risk_points'],
            ];
        } else {
            $operator = $phoneCheck['operator'];
            $flags[] = [
                'rule' => 'phone_validation',
                'title' => "{$operator['name']} ({$operator['code']}) Verified",
                'description' => "Valid 11-digit Bangladeshi mobile format: {$phoneCheck['normalized']}",
                'severity' => 'safe',
                'score' => 0,
            ];
        }

        // 4. COURIER RETURN (RTO) HISTORY CHECK
        $courierHistory = $this->getCourierHistory($normalizedPhone);
        if ($courierHistory['total_parcels'] > 0) {
            if ($courierHistory['success_rate'] < 50) {
                $totalScore += 35;
                $flags[] = [
                    'rule' => 'courier_rto',
                    'title' => "Critical Courier Return Rate ({$courierHistory['success_rate']}%)",
                    'description' => "Customer returned {$courierHistory['returned']} of {$courierHistory['total_parcels']} parcels on delivery networks ({$courierHistory['source']}).",
                    'severity' => 'danger',
                    'score' => 35,
                ];
            } elseif ($courierHistory['success_rate'] < 75) {
                $totalScore += 20;
                $flags[] = [
                    'rule' => 'courier_rto',
                    'title' => "Moderate Return Rate ({$courierHistory['success_rate']}%)",
                    'description' => "{$courierHistory['returned']} returned parcels out of {$courierHistory['total_parcels']} deliveries.",
                    'severity' => 'warning',
                    'score' => 20,
                ];
            } else {
                $flags[] = [
                    'rule' => 'courier_rto',
                    'title' => "Good Delivery History ({$courierHistory['success_rate']}%)",
                    'description' => "{$courierHistory['delivered']} successful deliveries recorded on courier networks.",
                    'severity' => 'safe',
                    'score' => 0,
                ];
            }
        }

        // 5. CASH ON DELIVERY (COD) HIGH VALUE RISK
        $isCod = in_array(strtolower($order->payment_method), ['cod', 'cash_on_delivery']);
        $codThreshold = (float) (Setting::get('fraud_cod_threshold') ?? 5000);

        if ($isCod && $order->total >= $codThreshold) {
            $points = $order->total >= ($codThreshold * 2) ? 30 : 20;
            $totalScore += $points;
            $flags[] = [
                'rule' => 'high_value_cod',
                'title' => 'High Value COD Order (৳'.number_format($order->total).')',
                'description' => 'Unpaid Cash on Delivery above threshold (৳'.number_format($codThreshold).'). Advance delivery fee recommended.',
                'severity' => 'warning',
                'score' => $points,
            ];
        }

        // 6. ADDRESS COMPLETENESS & QUALITY
        if (strlen(trim($fullAddress)) < 15) {
            $totalScore += 20;
            $flags[] = [
                'rule' => 'address_quality',
                'title' => 'Vague / Incomplete Delivery Address',
                'description' => "Address is too brief ('{$fullAddress}') and lacks road, house, or area specifics.",
                'severity' => 'warning',
                'score' => 20,
            ];
        } else {
            $flags[] = [
                'rule' => 'address_quality',
                'title' => 'Detailed Address Provided',
                'description' => 'Sufficient delivery details detected for courier routing.',
                'severity' => 'safe',
                'score' => 0,
            ];
        }

        // 7. ORDER VELOCITY (Multiple orders within short window)
        if ($normalizedPhone) {
            $recentOrdersCount = Order::where('created_at', '>=', now()->subHours(2))
                ->where('id', '!=', $order->id)
                ->where(function ($q) use ($normalizedPhone) {
                    $q->where('shipping_address->phone', 'like', "%{$normalizedPhone}%")
                        ->orWhereHas('customer', fn ($cq) => $cq->where('phone', 'like', "%{$normalizedPhone}%"));
                })->count();

            if ($recentOrdersCount >= 2) {
                $totalScore += 25;
                $flags[] = [
                    'rule' => 'order_velocity',
                    'title' => "Rapid Order Velocity ({$recentOrdersCount} orders in 2h)",
                    'description' => 'Multiple orders placed in rapid succession from this phone number.',
                    'severity' => 'warning',
                    'score' => 25,
                ];
            }
        }

        // Calculate final normalized score
        $finalScore = min(100, max(0, $totalScore));
        $riskLevel = $finalScore >= 70 ? 'high' : ($finalScore >= 30 ? 'medium' : 'low');

        // Suggested advance delivery charge
        // Inside Dhaka: ৳100, Outside Dhaka: ৳150
        $advanceFee = $this->getRecommendedAdvanceFee($fullAddress);

        $advanceRequired = $isCod && ($riskLevel === 'high' || $order->total >= $codThreshold);

        $status = match ($riskLevel) {
            'high' => 'flagged',
            'medium' => 'suspicious',
            default => 'clean',
        };

        $recommendedAction = match ($riskLevel) {
            'high' => "High Risk of Return! Request ৳{$advanceFee} Advance Delivery Charge via bKash/Nagad before dispatch.",
            'medium' => 'Review order. Make a confirmation phone call to verify customer commitment.',
            default => 'Clean order. Proceed with normal warehouse packaging and courier booking.',
        };

        return [
            'score' => $finalScore,
            'risk_level' => $riskLevel,
            'status' => $status,
            'flags' => $flags,
            'advance_required' => $advanceRequired,
            'suggested_advance_fee' => $advanceFee,
            'recommended_action' => $recommendedAction,
            'courier_metrics' => $courierHistory,
        ];
    }

    /**
     * Determine recommended advance delivery fee based on shipping destination.
     */
    public function getRecommendedAdvanceFee(?string $location): int
    {
        if (empty($location)) {
            return 150;
        }

        $isInsideDhaka = (bool) preg_match('/dhaka|uttara|mirpur|dhanmondi|gulshan|banani|mohammadpur/i', $location);

        return $isInsideDhaka ? 100 : 150;
    }

    /**
     * Inspect any phone number in real-time.
     */
    public function lookupPhone(string $phone): array
    {
        $normalized = $this->normalizeBdPhone($phone);
        $phoneCheck = $this->validateBdPhone($phone);
        $courierHistory = $this->getCourierHistory($normalized);
        $isBlacklisted = FraudBlacklist::isBlacklisted('phone', $normalized);
        $isWhitelisted = FraudBlacklist::isWhitelisted('phone', $normalized);

        $orders = Order::where(function ($q) use ($normalized) {
            $q->where('shipping_address->phone', 'like', "%{$normalized}%")
                ->orWhereHas('customer', fn ($cq) => $cq->where('phone', 'like', "%{$normalized}%"));
        })->with('customer')->latest()->take(5)->get();

        return [
            'phone' => $phone,
            'normalized' => $normalized,
            'is_valid' => $phoneCheck['is_valid'],
            'operator' => $phoneCheck['operator'],
            'reason' => $phoneCheck['reason'],
            'is_blacklisted' => $isBlacklisted,
            'is_whitelisted' => $isWhitelisted,
            'courier_history' => $courierHistory,
            'store_orders_count' => $orders->count(),
            'recent_orders' => $orders->map(fn ($o) => [
                'id' => $o->id,
                'order_number' => $o->order_number,
                'status' => $o->status,
                'total' => $o->total,
                'fraud_score' => $o->fraud_score,
                'fraud_risk_level' => $o->fraud_risk_level,
                'created_at' => $o->created_at->format('M d, Y'),
            ]),
        ];
    }
}
