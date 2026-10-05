<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\FraudBlacklist;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Services\FraudCheckService;
use Illuminate\Database\Seeder;

class FraudSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first();

        // 1. Default Fraud Configuration Settings
        $settings = [
            'fraud_check_enabled' => '1',
            'fraud_cod_threshold' => '5000',
            'fraud_advance_fee_inside_dhaka' => '100',
            'fraud_advance_fee_outside_dhaka' => '150',
            'fraud_auto_flag_high_risk' => '1',
            'fraud_courier_provider' => 'steadfast',
            'fraud_courier_api_endpoint' => 'https://api.steadfast.com.bd/v1/courier-check',
            'fraud_courier_api_key' => '',
        ];

        foreach ($settings as $key => $val) {
            Setting::firstOrCreate(
                ['key' => $key],
                ['value' => $val, 'type' => 'string', 'group' => 'fraud']
            );
        }

        // 2. Sample Blacklist and Whitelist Entries
        FraudBlacklist::firstOrCreate(
            ['type' => 'phone', 'value' => '01711000999', 'list_type' => 'blacklist'],
            [
                'reason' => 'Refused delivery 4 times on Steadfast COD across delivery networks.',
                'created_by' => $admin?->id,
            ]
        );

        FraudBlacklist::firstOrCreate(
            ['type' => 'phone', 'value' => '01822334455', 'list_type' => 'blacklist'],
            [
                'reason' => 'Fake address, invalid phone line & abusive courier disputes.',
                'created_by' => $admin?->id,
            ]
        );

        FraudBlacklist::firstOrCreate(
            ['type' => 'email', 'value' => 'fake_buyer@tempmail.com', 'list_type' => 'blacklist'],
            [
                'reason' => 'Disposable temporary email address creating bulk ghost orders.',
                'created_by' => $admin?->id,
            ]
        );

        FraudBlacklist::firstOrCreate(
            ['type' => 'phone', 'value' => '01712998877', 'list_type' => 'whitelist'],
            [
                'reason' => 'VIP Corporate customer - 100% advance paid delivery history.',
                'created_by' => $admin?->id,
            ]
        );

        // 3. Update Existing Demo Orders with Bangladeshi Context and Evaluate Fraud Score
        $bdSampleData = [
            [
                'phone' => '01712998877',
                'address' => 'House 42, Road 11, Block D, Banani, Dhaka',
                'city' => 'Dhaka',
                'payment_method' => 'bkash',
                'subtotal' => 3800,
                'total' => 3895,
            ],
            [
                'phone' => '01819234567',
                'address' => 'GEC Circle, Nasirabad, Chattogram',
                'city' => 'Chattogram',
                'payment_method' => 'cod',
                'subtotal' => 700,
                'total' => 756,
            ],
            [
                'phone' => '01912345678', // Sequential dummy test number -> High Risk
                'address' => 'Dhaka', // Vague address
                'city' => 'Dhaka',
                'payment_method' => 'cod',
                'subtotal' => 1350,
                'total' => 1407,
            ],
            [
                'phone' => '01711000999', // Matches Blacklist -> High Risk (Blocked)
                'address' => 'Station Road, Sylhet Sadar, Sylhet',
                'city' => 'Sylhet',
                'payment_method' => 'cod',
                'subtotal' => 1020,
                'total' => 1081,
            ],
            [
                'phone' => '01678123456',
                'address' => 'House 14, Sector 7, Uttara, Dhaka',
                'city' => 'Dhaka',
                'payment_method' => 'nagad',
                'subtotal' => 850,
                'total' => 919,
            ],
            [
                'phone' => '01755667788',
                'address' => 'Holding 55, College Road, Bogura Sadar, Bogura',
                'city' => 'Bogura',
                'payment_method' => 'cod',
                'subtotal' => 7200, // High Value COD
                'total' => 7280,
            ],
        ];

        $service = app(FraudCheckService::class);
        $orders = Order::with('customer')->get();

        foreach ($orders as $index => $order) {
            $sample = $bdSampleData[$index % count($bdSampleData)];

            // Update customer phone if exists
            if ($order->customer) {
                $order->customer->update([
                    'phone' => $sample['phone'],
                    'city' => $sample['city'],
                    'country' => 'Bangladesh',
                ]);
            }

            // Update order address & payment details
            $order->update([
                'payment_method' => $sample['payment_method'],
                'total' => $sample['total'],
                'subtotal' => $sample['subtotal'],
                'shipping_address' => [
                    'name' => $order->customer?->name ?? 'Customer',
                    'phone' => $sample['phone'],
                    'address' => $sample['address'],
                    'city' => $sample['city'],
                    'country' => 'Bangladesh',
                ],
            ]);

            // Evaluate fraud score
            $assessment = $service->evaluateOrder($order);

            $order->update([
                'fraud_score' => $assessment['score'],
                'fraud_risk_level' => $assessment['risk_level'],
                'fraud_status' => $assessment['status'],
                'fraud_flags' => $assessment['flags'],
                'advance_delivery_charge' => $assessment['advance_required'] ? $assessment['suggested_advance_fee'] : null,
                'advance_payment_status' => $assessment['advance_required'] ? 'pending' : 'none',
                'fraud_checked_at' => now(),
                'fraud_notes' => $assessment['recommended_action'],
            ]);
        }
    }
}
