<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MultiIndustryProductSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Categories
        $fashionCat = Category::firstOrCreate(['name' => 'Fashion & Apparel'], ['slug' => 'fashion-apparel', 'is_active' => true]);
        $buildingCat = Category::firstOrCreate(['name' => 'Building & Construction'], ['slug' => 'building-construction', 'is_active' => true]);
        $chemicalCat = Category::firstOrCreate(['name' => 'Liquids & Chemicals'], ['slug' => 'liquids-chemicals', 'is_active' => true]);
        $electronicsCat = Category::firstOrCreate(['name' => 'Electronics & Audio'], ['slug' => 'electronics-audio', 'is_active' => true]);

        // 2. Brands
        $nike = Brand::firstOrCreate(['name' => 'Apex Apparel'], ['slug' => 'apex-apparel', 'is_active' => true]);
        $marazzi = Brand::firstOrCreate(['name' => 'Marazzi Stone & Tile'], ['slug' => 'marazzi-stone-tile', 'is_active' => true]);
        $castrol = Brand::firstOrCreate(['name' => 'PureLube Industrial'], ['slug' => 'purelube-industrial', 'is_active' => true]);
        $sony = Brand::firstOrCreate(['name' => 'AuraSound Tech'], ['slug' => 'aurasound-tech', 'is_active' => true]);

        // Product 1: 👗 Apparel - Heavyweight Organic Cotton Hoodie
        $hoodie = Product::updateOrCreate(
            ['sku' => 'APP-HD-001'],
            [
                'product_type' => 'apparel',
                'name' => 'Heavyweight 400 GSM Organic Boxy Hoodie',
                'slug' => 'heavyweight-400-gsm-organic-boxy-hoodie-'.Str::lower(Str::random(4)),
                'category_id' => $fashionCat->id,
                'brand_id' => $nike->id,
                'price' => 79.00,
                'compare_price' => 95.00,
                'cost_price' => 32.00,
                'unit' => 'piece',
                'min_order_quantity' => 1,
                'quantity_step' => 1,
                'stock_quantity' => 140,
                'low_stock_threshold' => 15,
                'weight' => 0.850,
                'weight_unit' => 'kg',
                'length' => 35,
                'width' => 28,
                'height' => 5,
                'dimension_unit' => 'cm',
                'status' => 'published',
                'is_featured' => true,
                'primary_image' => 'https://images.unsplash.com/photo-1556905055-8f358a7a47b2?w=800',
                'description' => 'Crafted from 100% GOTS certified organic ring-spun combed cotton. Features dropped shoulders, double-layered hood, and ribbed cuffs.',
                'attributes' => [
                    'fabric' => '100% Organic Ring-Spun Cotton (400 GSM French Terry)',
                    'fit_type' => 'Oversized',
                    'gender' => 'Unisex',
                    'care_instructions' => 'Wash cold inside out 30°C, hang dry to preserve fit',
                ],
                'has_variants' => true,
            ]
        );

        $hoodie->variants()->delete();
        $hoodieVariants = [
            ['sku' => 'APP-HD-001-S-BLK', 'price' => 79.00, 'stock' => 25, 'opts' => ['Size' => 'S', 'Color' => 'Washed Black']],
            ['sku' => 'APP-HD-001-M-BLK', 'price' => 79.00, 'stock' => 40, 'opts' => ['Size' => 'M', 'Color' => 'Washed Black']],
            ['sku' => 'APP-HD-001-L-BLK', 'price' => 79.00, 'stock' => 45, 'opts' => ['Size' => 'L', 'Color' => 'Washed Black']],
            ['sku' => 'APP-HD-001-M-OLV', 'price' => 79.00, 'stock' => 30, 'opts' => ['Size' => 'M', 'Color' => 'Olive Green']],
        ];
        foreach ($hoodieVariants as $var) {
            $hoodie->variants()->create([
                'sku' => $var['sku'],
                'price' => $var['price'],
                'stock_quantity' => $var['stock'],
                'option_values' => $var['opts'],
                'is_active' => true,
            ]);
        }

        // Product 2: 🧱 Building Material - Calacatta Marble Glazed Porcelain Floor Tile
        $tile = Product::updateOrCreate(
            ['sku' => 'BLD-TILE-CALA-60'],
            [
                'product_type' => 'building_material',
                'name' => 'Calacatta Gold Polished Porcelain Floor Tile (60x60 cm)',
                'slug' => 'calacatta-gold-polished-porcelain-floor-tile-'.Str::lower(Str::random(4)),
                'category_id' => $buildingCat->id,
                'brand_id' => $marazzi->id,
                'price' => 3.85, // $3.85 per sq ft
                'compare_price' => 4.50,
                'cost_price' => 1.95,
                'unit' => 'sqft',
                'min_order_quantity' => 50, // MOQ 50 sq ft
                'quantity_step' => 15.5, // 1 box = 15.5 sq ft
                'unit_coverage_value' => 15.50,
                'stock_quantity' => 2400,
                'low_stock_threshold' => 300,
                'weight' => 28.5,
                'weight_unit' => 'kg',
                'length' => 60,
                'width' => 60,
                'height' => 0.95,
                'dimension_unit' => 'cm',
                'status' => 'published',
                'is_featured' => true,
                'primary_image' => 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=800',
                'description' => 'High-gloss rectified edge porcelain stoneware tile reproducing luxurious Italian Calacatta Gold marble with realistic veining. Ideal for indoor living rooms, kitchens, and luxury bathrooms.',
                'attributes' => [
                    'grade' => 'Class 4 Commercial & Residential High Traffic',
                    'finish' => 'High-Gloss Polished Nano-Coating',
                    'thickness' => '9.5 mm Rectified',
                    'heavy_freight' => true,
                ],
                'has_variants' => false,
            ]
        );

        // Product 3: 🧱 Building Material - Portland Hydraulic Cement 50kg Sack
        Product::updateOrCreate(
            ['sku' => 'BLD-CEM-PORT-50'],
            [
                'product_type' => 'building_material',
                'name' => 'Type I/II Portland High-Strength Hydraulic Cement (50kg)',
                'slug' => 'portland-hydraulic-cement-50kg-'.Str::lower(Str::random(4)),
                'category_id' => $buildingCat->id,
                'brand_id' => $marazzi->id,
                'price' => 14.50,
                'compare_price' => 16.00,
                'cost_price' => 8.20,
                'unit' => 'bag',
                'min_order_quantity' => 10,
                'quantity_step' => 10,
                'stock_quantity' => 850,
                'low_stock_threshold' => 50,
                'weight' => 50.0,
                'weight_unit' => 'kg',
                'status' => 'published',
                'is_featured' => false,
                'primary_image' => 'https://images.unsplash.com/photo-1504307651254-35680f356dfd?w=800',
                'description' => 'Versatile general-purpose cement engineered for reinforced concrete, pavements, pipe work, precast structures, and foundations.',
                'attributes' => [
                    'grade' => 'ASTM C150 Type I/II 52.5N',
                    'finish' => 'Ultra-fine powder',
                    'heavy_freight' => true,
                ],
                'has_variants' => false,
            ]
        );

        // Product 4: 🧪 Liquids / Chemicals - Fully Synthetic Engine Oil 5W-30 (5L Can)
        $oil = Product::updateOrCreate(
            ['sku' => 'LIQ-OIL-SYN-5L'],
            [
                'product_type' => 'liquid',
                'name' => 'Full Synthetic Advanced Performance Engine Oil 5W-30',
                'slug' => 'full-synthetic-advanced-engine-oil-5w30-'.Str::lower(Str::random(4)),
                'category_id' => $chemicalCat->id,
                'brand_id' => $castrol->id,
                'price' => 38.50,
                'compare_price' => 45.00,
                'cost_price' => 18.00,
                'unit' => 'gallon',
                'min_order_quantity' => 1,
                'quantity_step' => 1,
                'stock_quantity' => 220,
                'low_stock_threshold' => 20,
                'weight' => 4.650,
                'weight_unit' => 'kg',
                'status' => 'published',
                'is_featured' => true,
                'primary_image' => 'https://images.unsplash.com/photo-1486006920555-c77dce18193b?w=800',
                'description' => 'Engineered with titanium technology for maximum film strength and thermal protection under extreme temperatures.',
                'attributes' => [
                    'volume_ml' => '5 Liters (1.32 Gallons)',
                    'packaging_type' => 'Plastic Jug / HDPE',
                    'hazard_class' => 'Non-Hazardous / Food Safe',
                    'storage_temp' => 'Store upright in dry cool area between 10°C - 35°C',
                ],
                'has_variants' => true,
            ]
        );

        $oil->variants()->delete();
        $oilVariants = [
            ['sku' => 'LIQ-OIL-SYN-1L', 'price' => 11.50, 'stock' => 120, 'opts' => ['Volume' => '1 Liter Bottle']],
            ['sku' => 'LIQ-OIL-SYN-5L-CAN', 'price' => 38.50, 'stock' => 100, 'opts' => ['Volume' => '5 Liter Jug']],
            ['sku' => 'LIQ-OIL-SYN-20L-DRUM', 'price' => 135.00, 'stock' => 25, 'opts' => ['Volume' => '20 Liter Pail']],
        ];
        foreach ($oilVariants as $var) {
            $oil->variants()->create([
                'sku' => $var['sku'],
                'price' => $var['price'],
                'stock_quantity' => $var['stock'],
                'option_values' => $var['opts'],
                'is_active' => true,
            ]);
        }

        // Product 5: ⚡ Electronics - ANC Studio Wireless Headphones
        Product::updateOrCreate(
            ['sku' => 'ELEC-ANC-PRO-BLK'],
            [
                'product_type' => 'electronics',
                'name' => 'AuraSound Apex Pro ANC Wireless Studio Headphones',
                'slug' => 'aurasound-apex-pro-anc-wireless-headphones-'.Str::lower(Str::random(4)),
                'category_id' => $electronicsCat->id,
                'brand_id' => $sony->id,
                'price' => 249.00,
                'compare_price' => 299.00,
                'cost_price' => 115.00,
                'unit' => 'piece',
                'min_order_quantity' => 1,
                'quantity_step' => 1,
                'stock_quantity' => 65,
                'low_stock_threshold' => 10,
                'weight' => 0.280,
                'weight_unit' => 'kg',
                'status' => 'published',
                'is_featured' => true,
                'primary_image' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=800',
                'description' => 'Industry-leading Active Noise Cancellation with dual acoustic processors, 40mm custom planar drivers, and 45-hour ultra-long battery life with USB-C fast charging.',
                'attributes' => [
                    'warranty_months' => '24 Months Official Warranty',
                    'power_rating' => 'USB-C 5V/2A Fast Charge (3 hrs playtime on 10 min charge)',
                    'specs' => [
                        ['key' => 'Active Noise Cancellation', 'value' => 'Hybrid Dual-Mic ANC up to -38dB'],
                        ['key' => 'Battery Life', 'value' => '45 Hours (ANC On) / 60 Hours (ANC Off)'],
                        ['key' => 'Driver Size', 'value' => '40mm Titanium Dome Driver'],
                        ['key' => 'Bluetooth', 'value' => 'Bluetooth 5.3 Multipoint & LDAC Hi-Res Audio'],
                    ],
                ],
                'has_variants' => false,
            ]
        );
    }
}
