<?php

namespace App\Exports;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(protected array $filters = []) {}

    public function query(): Builder
    {
        $query = Product::with(['category', 'brand']);

        if (! empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if (! empty($this->filters['product_type'])) {
            $query->where('product_type', $this->filters['product_type']);
        }

        if (! empty($this->filters['category'])) {
            $query->where('category_id', $this->filters['category']);
        }

        if (! empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (! empty($this->filters['low_stock'])) {
            $query->lowStock();
        }

        return $query->latest();
    }

    public function headings(): array
    {
        return [
            'SKU',
            'Product Name',
            'Type',
            'Sales Unit',
            'Category',
            'Brand',
            'Retail Price ($)',
            'Compare Price ($)',
            'Cost Price ($)',
            'Stock Quantity',
            'Low Stock Alert Threshold',
            'Status',
            'Featured',
            'Date Added',
        ];
    }

    /**
     * @param  Product  $row
     * @return array<int, mixed>
     */
    public function map($row): array
    {
        return [
            $row->sku,
            $row->name,
            ucwords(str_replace('_', ' ', $row->product_type ?? 'standard')),
            $row->unit ?? 'piece',
            $row->category?->name ?? 'Uncategorized',
            $row->brand?->name ?? 'None',
            number_format($row->price, 2, '.', ''),
            $row->compare_price ? number_format($row->compare_price, 2, '.', '') : '',
            $row->cost_price ? number_format($row->cost_price, 2, '.', '') : '',
            $row->stock_quantity,
            $row->low_stock_threshold,
            ucfirst($row->status),
            $row->is_featured ? 'Yes' : 'No',
            $row->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
