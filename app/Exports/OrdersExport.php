<?php

namespace App\Exports;

use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OrdersExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(protected array $filters = []) {}

    public function query(): Builder
    {
        $query = Order::with('customer')->withCount('items');

        if (! empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if (! empty($this->filters['status']) && $this->filters['status'] !== 'all') {
            $query->where('status', $this->filters['status']);
        }

        if (! empty($this->filters['payment_status'])) {
            $query->where('payment_status', $this->filters['payment_status']);
        }

        return $query->latest();
    }

    public function headings(): array
    {
        return [
            'Order #',
            'Customer Name',
            'Customer Email',
            'Customer Phone',
            'Status',
            'Payment Status',
            'Payment Method',
            'Items Count',
            'Subtotal (৳)',
            'Tax (৳)',
            'Shipping (৳)',
            'Total (৳)',
            'Date Created',
        ];
    }

    /**
     * @param  Order  $row
     * @return array<int, mixed>
     */
    public function map($row): array
    {
        return [
            $row->order_number,
            $row->customer?->name ?? 'Guest',
            $row->customer?->email ?? '',
            $row->customer?->phone ?? '',
            ucfirst($row->status),
            ucfirst($row->payment_status),
            str_replace('_', ' ', ucfirst($row->payment_method)),
            $row->items_count,
            number_format($row->subtotal, 2, '.', ''),
            number_format($row->tax, 2, '.', ''),
            number_format($row->shipping_cost, 2, '.', ''),
            number_format($row->total, 2, '.', ''),
            $row->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
