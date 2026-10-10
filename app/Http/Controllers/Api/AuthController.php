<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Customer registration from storefront.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150|unique:users,email',
            'phone' => 'nullable|string|max:30',
            'password' => 'required|string|min:6|confirmed',
        ]);

        return DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => 'customer',
            ]);

            $nameParts = explode(' ', trim($validated['name']), 2);

            $customer = Customer::create([
                'user_id' => $user->id,
                'first_name' => $nameParts[0],
                'last_name' => $nameParts[1] ?? '',
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'status' => 'active',
            ]);

            $token = $user->createToken('storefront_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Registration successful! Welcome to Loomora.',
                'token' => $token,
                'data' => [
                    'id' => $customer->id,
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $customer->phone,
                ],
            ], 201);
        });
    }

    /**
     * Customer login via email or phone number.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'identifier' => 'required|string',
            'password' => 'required|string',
        ]);

        $identifier = trim($validated['identifier']);
        $user = null;

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $user = User::where('email', $identifier)->first();
        } else {
            // Find by customer phone number
            $cleanPhone = preg_replace('/\D/', '', $identifier);
            $customer = Customer::where(function ($q) use ($identifier, $cleanPhone) {
                $q->where('phone', $identifier)
                    ->orWhereRaw("REPLACE(REPLACE(phone, '-', ''), ' ', '') LIKE ?", ["%{$cleanPhone}%"]);
            })->whereNotNull('user_id')->first();

            if ($customer && $customer->user) {
                $user = $customer->user;
            }
        }

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'identifier' => ['Invalid email/phone or password.'],
            ]);
        }

        $customer = $user->customer ?: Customer::where('email', $user->email)->first();

        // Ensure customer profile is linked
        if (! $customer) {
            $nameParts = explode(' ', trim($user->name), 2);
            $customer = Customer::create([
                'user_id' => $user->id,
                'first_name' => $nameParts[0],
                'last_name' => $nameParts[1] ?? '',
                'email' => $user->email,
                'status' => 'active',
            ]);
        } elseif (! $customer->user_id) {
            $customer->update(['user_id' => $user->id]);
        }

        $token = $user->createToken('storefront_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Signed in successfully.',
            'token' => $token,
            'data' => [
                'id' => $customer->id,
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $customer->phone,
                'address' => $customer->full_address,
            ],
        ]);
    }

    /**
     * Get currently authenticated customer profile.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $customer = $user->customer ?: Customer::where('email', $user->email)->first();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $customer?->id,
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $customer?->phone,
                'address_line_1' => $customer?->address_line_1,
                'city' => $customer?->city,
                'total_spent' => (float) ($customer?->total_spent ?? 0),
                'total_orders' => (int) ($customer?->total_orders ?? 0),
            ],
        ]);
    }

    /**
     * Get order history for the authenticated customer.
     */
    public function orders(Request $request): JsonResponse
    {
        $user = $request->user();
        $customer = $user->customer ?: Customer::where('email', $user->email)->first();

        if (! $customer) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $orders = Order::where(function ($q) use ($customer, $user) {
            $q->where('customer_id', $customer->id)
                ->orWhere('shipping_address->phone', $customer->phone)
                ->orWhere('shipping_address->email', $user->email);
        })
            ->with(['items'])
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $orders->items(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    /**
     * Logout customer and revoke token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Signed out successfully.',
        ]);
    }

    /**
     * Get wishlist items for the authenticated customer.
     */
    public function getWishlist(Request $request): JsonResponse
    {
        $user = $request->user();
        $customer = $user->customer ?: Customer::where('email', $user->email)->first();

        if (! $customer) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $productIds = Wishlist::where('customer_id', $customer->id)
            ->pluck('product_id')
            ->map(fn ($id) => (string) $id)
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => $productIds,
        ]);
    }

    /**
     * Toggle product in authenticated customer's cloud wishlist.
     */
    public function toggleWishlist(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required',
        ]);

        $user = $request->user();
        $customer = $user->customer ?: Customer::where('email', $user->email)->first();

        if (! $customer) {
            return response()->json(['success' => false, 'message' => 'Customer profile missing.'], 404);
        }

        $prodId = (int) $validated['product_id'];

        $existing = Wishlist::where('customer_id', $customer->id)
            ->where('product_id', $prodId)
            ->first();

        if ($existing) {
            $existing->delete();
            $inWishlist = false;
        } else {
            Wishlist::create([
                'customer_id' => $customer->id,
                'product_id' => $prodId,
            ]);
            $inWishlist = true;
        }

        return response()->json([
            'success' => true,
            'in_wishlist' => $inWishlist,
            'message' => $inWishlist ? 'Added to wishlist' : 'Removed from wishlist',
        ]);
    }

    /**
     * Sync local guest wishlist into customer's account upon login.
     */
    public function syncWishlist(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'required',
        ]);

        $user = $request->user();
        $customer = $user->customer ?: Customer::where('email', $user->email)->first();

        if (! $customer) {
            return response()->json(['success' => false], 404);
        }

        foreach ($validated['product_ids'] as $pid) {
            if (is_numeric($pid)) {
                Wishlist::firstOrCreate([
                    'customer_id' => $customer->id,
                    'product_id' => (int) $pid,
                ]);
            }
        }

        $allIds = Wishlist::where('customer_id', $customer->id)
            ->pluck('product_id')
            ->map(fn ($id) => (string) $id)
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => $allIds,
        ]);
    }
}
