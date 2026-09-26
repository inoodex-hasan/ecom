<?php

namespace App\Exports;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CustomersExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(protected array $filters = []) {}

    public function query(): Builder
    {
        $query = Customer::query();

        if (! empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (! empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        return $query->latest();
    }

    public function headings(): array
    {
        return [
            'Customer Name',
            'Email',
            'Phone',
            'City',
            'State',
            'Postal Code',
            'Country',
            'Total Orders',
            'Lifetime Value ($)',
            'Status',
            'Registered Date',
        ];
    }

    /**
     * @param  Customer  $row
     * @return array<int, mixed>
     */
    public function map($row): array
    {
        return [
            $row->name,
            $row->email,
            $row->phone,
            $row->city,
            $row->state,
            $row->postal_code,
            $row->country,
            $row->total_orders,
            number_format($row->total_spent, 2, '.', ''),
            ucfirst($row->status),
            $row->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
