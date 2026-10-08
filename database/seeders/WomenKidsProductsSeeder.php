<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class WomenKidsProductsSeeder extends Seeder
{
    /**
     * Seed realistic Women and Kids products mapped to their subcategories.
     */
    public function run(): void
    {
        $womenCategory = Category::where('slug', 'women')->orWhere('id', 53)->first();
        $kidsCategory = Category::where('slug', 'kids')->orWhere('id', 54)->first();

        if (! $womenCategory || ! $kidsCategory) {
            return;
        }

        // Brands
        $loomora = Brand::firstOrCreate(['slug' => 'loomora'], ['name' => 'Loomora', 'is_active' => true]);
        $dheu = Brand::firstOrCreate(['slug' => 'dheu'], ['name' => 'DHEU', 'is_active' => true]);
        $loomoraWomen = Brand::firstOrCreate(['slug' => 'loomora-women'], ['name' => 'Loomora Women', 'is_active' => true]);
        $loomoraKids = Brand::firstOrCreate(['slug' => 'loomora-kids'], ['name' => 'Loomora Kids', 'is_active' => true]);
        $dheuJunior = Brand::firstOrCreate(['slug' => 'dheu-junior'], ['name' => 'DHEU Junior', 'is_active' => true]);
        $loomoraSig = Brand::firstOrCreate(['slug' => 'loomora-signature'], ['name' => 'Loomora Signature', 'is_active' => true]);

        $brands = [
            'Loomora' => $loomora,
            'DHEU' => $dheu,
            'Loomora Women' => $loomoraWomen,
            'Loomora Kids' => $loomoraKids,
            'DHEU Junior' => $dheuJunior,
            'Loomora Signature' => $loomoraSig,
        ];

        // Women Subcategories map
        $womenSubs = Category::where('parent_id', $womenCategory->id)->get()->keyBy('name');
        $kidsSubs = Category::where('parent_id', $kidsCategory->id)->get()->keyBy('name');

        $womenCatalog = [
            [
                'name' => 'Womens Floral Embroidered Kurti Set - Regular Fit',
                'slug' => 'womens-floral-embroidered-kurti-set-regular-fit',
                'sku' => 'CLOTH-W01',
                'sub_name' => 'Ethnic Set',
                'brand' => 'Loomora',
                'price' => 2490,
                'compare_price' => 3200,
                'image' => '/images/women/women.avif',
                'badge' => 'New',
                'sizes' => ['38', '40', '42'],
                'attributes' => [
                    'subcategory' => 'Womens Kurti',
                    'category' => 'Womens Kurti',
                    'fabric' => 'Cotton',
                    'fit' => 'Regular Fit',
                    'sleeve' => 'Full Sleeve',
                    'neck' => 'Round',
                    'length' => 'Full Length',
                    'addition' => ['Embroidery'],
                    'festive' => ['Puja 2026'],
                    'productType' => 'Kurti',
                    'set' => '2pcs',
                    'color' => '#fda4af',
                    'colorName' => 'Pastel Rose',
                ],
            ],
            [
                'name' => 'Womens Crimson Silk Traditional Handwoven Saree',
                'slug' => 'womens-crimson-silk-traditional-handwoven-saree',
                'sku' => 'CLOTH-W02',
                'sub_name' => 'Saree',
                'brand' => 'Loomora',
                'price' => 1550,
                'compare_price' => 1950,
                'image' => '/images/women/m-1.jpg',
                'badge' => 'New',
                'sizes' => ['FREE SIZE'],
                'attributes' => [
                    'subcategory' => 'Womens Saree',
                    'category' => 'Womens Saree',
                    'fabric' => 'Silk',
                    'fit' => 'Traditional',
                    'festive' => ['Puja 2026'],
                    'productType' => 'Saree',
                    'color' => '#dc2626',
                    'colorName' => 'Crimson Red',
                ],
            ],
            [
                'name' => 'Womens Rose Festive Printed Saree with Zari Border',
                'slug' => 'womens-rose-festive-printed-saree-with-zari-border',
                'sku' => 'CLOTH-W03',
                'sub_name' => 'Saree',
                'brand' => 'DHEU',
                'price' => 1550,
                'compare_price' => 1850,
                'image' => '/images/women/m-2.jpg',
                'badge' => 'New',
                'sizes' => ['FREE SIZE'],
                'attributes' => [
                    'subcategory' => 'Womens Saree',
                    'category' => 'Womens Saree',
                    'fabric' => 'Silk',
                    'addition' => ['Print'],
                    'festive' => ['Puja 2026'],
                    'productType' => 'Saree',
                    'color' => '#ec4899',
                    'colorName' => 'Rose Pink',
                ],
            ],
            [
                'name' => 'Womens Pearl White Classic Festive Saree',
                'slug' => 'womens-pearl-white-classic-festive-saree',
                'sku' => 'CLOTH-W04',
                'sub_name' => 'Saree',
                'brand' => 'Loomora',
                'price' => 1550,
                'compare_price' => 1950,
                'image' => '/images/women/m-3.jpg',
                'badge' => 'New',
                'sizes' => ['FREE SIZE'],
                'attributes' => [
                    'subcategory' => 'Womens Saree',
                    'category' => 'Womens Saree',
                    'fabric' => 'Silk',
                    'festive' => ['Eid 2026', 'Puja 2026'],
                    'productType' => 'Saree',
                    'color' => '#e5e7eb',
                    'colorName' => 'Pearl White',
                ],
            ],
            [
                'name' => 'Womens 2 Pcs Pastel Ensemble Set - Regular Fit',
                'slug' => 'womens-2-pcs-pastel-ensemble-set-regular-fit',
                'sku' => 'CLOTH-W05',
                'sub_name' => 'Ethnic Set',
                'brand' => 'Loomora Women',
                'price' => 2890,
                'compare_price' => 3500,
                'image' => '/images/women/m-4.jpg',
                'badge' => 'New',
                'sizes' => ['38', '40', '42'],
                'attributes' => [
                    'subcategory' => 'Womens 2 Pcs Set',
                    'category' => 'Womens 2 Pcs Set',
                    'fabric' => 'Cotton & Spandex',
                    'fit' => 'Regular Fit',
                    'sleeve' => 'Full Sleeve',
                    'neck' => 'Round',
                    'length' => 'Full Length',
                    'festive' => ['Puja 2026'],
                    'productType' => 'Kurti',
                    'set' => '2pcs',
                    'color' => '#f9a8d4',
                    'colorName' => 'Pastel Pink',
                ],
            ],
            [
                'name' => 'Womens Ethnic Festive Kurti & Trouser 2 Pcs Set',
                'slug' => 'womens-ethnic-festive-kurti-trouser-2-pcs-set',
                'sku' => 'CLOTH-W06',
                'sub_name' => 'Ethnic Set',
                'brand' => 'Loomora',
                'price' => 2690,
                'compare_price' => 3350,
                'image' => '/images/women/m-5.jpg',
                'badge' => 'New',
                'sizes' => ['38', '40', '42'],
                'attributes' => [
                    'subcategory' => 'Womens 2 Pcs Set',
                    'category' => 'Womens 2 Pcs Set',
                    'fabric' => 'Cotton',
                    'fit' => 'Regular Fit',
                    'sleeve' => 'Full Sleeve',
                    'neck' => 'Band Collar',
                    'length' => 'Full Length',
                    'addition' => ['Karchupi'],
                    'festive' => ['Eid 2026', 'Puja 2026'],
                    'productType' => 'Kurti',
                    'set' => '2pcs',
                    'color' => '#dc2626',
                    'colorName' => 'Scarlet Red',
                ],
            ],
            [
                'name' => 'Womens Soft Cashmere Knit Cardigan - Rose',
                'slug' => 'womens-soft-cashmere-knit-cardigan-rose',
                'sku' => 'CLOTH-W07',
                'sub_name' => 'Womens Sweater',
                'brand' => 'Loomora Signature',
                'price' => 23590,
                'compare_price' => 27400,
                'image' => '/images/c-10.avif',
                'badge' => 'New',
                'sizes' => ['FREE SIZE'],
                'attributes' => [
                    'subcategory' => 'Womens Cardigan',
                    'category' => 'Womens Cardigan',
                    'fabric' => 'Cashmere',
                    'fit' => 'Regular Fit',
                    'sleeve' => 'Full Sleeve',
                    'neck' => 'Rib Collar',
                    'length' => 'Long',
                    'productType' => 'Cardigan',
                    'color' => '#fda4af',
                    'colorName' => 'Blush Rose',
                ],
            ],
            [
                'name' => 'Womens Everyday Fleece Minimalist Sweatshirt',
                'slug' => 'womens-everyday-fleece-minimalist-sweatshirt',
                'sku' => 'CLOTH-W08',
                'sub_name' => 'Womens Sweatshirt',
                'brand' => 'Loomora',
                'price' => 6000,
                'compare_price' => 6900,
                'image' => '/images/c-2.avif',
                'badge' => 'New',
                'sizes' => ['S', 'M', 'L', 'XL'],
                'attributes' => [
                    'subcategory' => 'Womens Tops',
                    'category' => 'Womens Tops',
                    'fabric' => 'Fleece Fabric',
                    'fit' => 'Over-sized',
                    'sleeve' => 'Full Sleeve',
                    'neck' => 'Round',
                    'length' => 'Long',
                    'productType' => 'Sweatshirt',
                    'color' => '#f5f5f4',
                    'colorName' => 'Chalk White',
                ],
            ],
            [
                'name' => 'Womens Bohemian Botanical Print Casual Tunic Dress',
                'slug' => 'womens-bohemian-botanical-print-casual-tunic-dress',
                'sku' => 'CLOTH-W09',
                'sub_name' => 'Fashion Tops',
                'brand' => 'DHEU',
                'price' => 2890,
                'compare_price' => 3400,
                'image' => '/images/c-4.avif',
                'badge' => 'New',
                'sizes' => ['S', 'M', 'L'],
                'attributes' => [
                    'subcategory' => 'Womens Tops',
                    'category' => 'Womens Tops',
                    'fabric' => 'Cotton & Modal Print',
                    'fit' => 'Regular Fit',
                    'sleeve' => 'Half Sleeve',
                    'neck' => 'Round',
                    'length' => 'Full Length',
                    'addition' => ['Print'],
                    'festive' => ['Boishak 2026'],
                    'productType' => 'Dress',
                    'color' => '#059669',
                    'colorName' => 'Forest Green',
                ],
            ],
            [
                'name' => 'Womens Warm Cozy Winter Fleece Jacket',
                'slug' => 'womens-warm-cozy-winter-fleece-jacket',
                'sku' => 'CLOTH-W10',
                'sub_name' => 'Womens Jacket',
                'brand' => 'Loomora',
                'price' => 2190,
                'compare_price' => 2650,
                'image' => '/images/c-5.avif',
                'badge' => 'New',
                'sizes' => ['M', 'L', 'XL'],
                'attributes' => [
                    'subcategory' => 'Womens Winter Wear',
                    'category' => 'Womens Winter Wear',
                    'fabric' => 'Fleece',
                    'fit' => 'Regular Fit',
                    'sleeve' => 'Full Sleeve',
                    'neck' => 'Hooded Neck',
                    'length' => 'Full Length',
                    'productType' => 'Jacket',
                    'color' => '#e11d48',
                    'colorName' => 'Wine Berry',
                ],
            ],
        ];

        $kidsCatalog = [
            [
                'name' => 'Boys Cotton Pique Polo Shirt - Regular Fit',
                'slug' => 'boys-cotton-pique-polo-shirt-regular-fit',
                'sku' => 'CLOTH-K01',
                'sub_name' => 'Boys Polo',
                'brand' => 'Loomora Kids',
                'price' => 690,
                'compare_price' => 850,
                'image' => '/images/men/w-1.jpg',
                'badge' => 'New',
                'sizes' => ['4-5Y', '6-7Y', '8-9Y', '10-11Y'],
                'attributes' => [
                    'subcategory' => 'Boys Polo',
                    'category' => 'Boys Polo',
                    'fabric' => 'Cotton & Spandex',
                    'fit' => 'Regular Fit',
                    'sleeve' => 'Half Sleeve',
                    'neck' => 'Rib Collar',
                    'length' => 'Short',
                    'productType' => 'Polo',
                    'color' => '#0f766e',
                    'colorName' => 'Sea Green',
                ],
            ],
            [
                'name' => 'Boys Graphic Print Short Sleeve T-Shirt',
                'slug' => 'boys-graphic-print-short-sleeve-t-shirt',
                'sku' => 'CLOTH-K02',
                'sub_name' => 'Boys T-Shirt',
                'brand' => 'Loomora Kids',
                'price' => 490,
                'compare_price' => 650,
                'image' => '/images/c-7.avif',
                'badge' => 'New',
                'sizes' => ['4-5Y', '6-7Y', '8-9Y', '10-11Y'],
                'attributes' => [
                    'subcategory' => 'Boys T-Shirt',
                    'category' => 'Boys T-Shirt',
                    'fabric' => 'Cotton',
                    'fit' => 'Regular Fit',
                    'sleeve' => 'Half Sleeve',
                    'neck' => 'Round',
                    'length' => 'Short',
                    'productType' => 'T-Shirt',
                    'addition' => ['Print'],
                    'color' => '#71717a',
                    'colorName' => 'Heather Grey',
                ],
            ],
            [
                'name' => 'Girls Floral Festive Printed Frock',
                'slug' => 'girls-floral-festive-printed-frock',
                'sku' => 'CLOTH-K03',
                'sub_name' => 'Girls Frock',
                'brand' => 'DHEU Junior',
                'price' => 1190,
                'compare_price' => 1450,
                'image' => '/images/women/women.avif',
                'badge' => 'New',
                'sizes' => ['4-5Y', '6-7Y', '8-9Y'],
                'attributes' => [
                    'subcategory' => 'Girls Frock',
                    'category' => 'Girls Frock',
                    'fabric' => 'Cotton',
                    'fit' => 'Regular Fit',
                    'sleeve' => 'Half Sleeve',
                    'neck' => 'Round',
                    'length' => 'Full Length',
                    'addition' => ['Print'],
                    'festive' => ['Puja 2026'],
                    'productType' => 'Dress',
                    'color' => '#fda4af',
                    'colorName' => 'Blush Pink',
                ],
            ],
            [
                'name' => 'Girls 2 Pcs Pastel Traditional Kurti & Trouser Set',
                'slug' => 'girls-2-pcs-pastel-traditional-kurti-trouser-set',
                'sku' => 'CLOTH-K04',
                'sub_name' => 'Girls Kurti',
                'brand' => 'Loomora Kids',
                'price' => 1390,
                'compare_price' => 1700,
                'image' => '/images/women/m-4.jpg',
                'badge' => 'New',
                'sizes' => ['4-5Y', '6-7Y', '8-9Y'],
                'attributes' => [
                    'subcategory' => 'Girls Kurti Set',
                    'category' => 'Girls Kurti Set',
                    'fabric' => 'Cotton & Spandex',
                    'fit' => 'Regular Fit',
                    'sleeve' => 'Full Sleeve',
                    'neck' => 'Round',
                    'length' => 'Full Length',
                    'festive' => ['Puja 2026'],
                    'productType' => 'Kurti',
                    'set' => '2pcs',
                    'color' => '#f9a8d4',
                    'colorName' => 'Rose Pink',
                ],
            ],
            [
                'name' => 'Boys Stretch Comfort Denim Pant',
                'slug' => 'boys-stretch-comfort-denim-pant',
                'sku' => 'CLOTH-K05',
                'sub_name' => 'Boys Denim',
                'brand' => 'Loomora Kids',
                'price' => 990,
                'compare_price' => 1250,
                'image' => '/images/men/w-2.jpg',
                'badge' => 'New',
                'sizes' => ['6-7Y', '8-9Y', '10-11Y'],
                'attributes' => [
                    'subcategory' => 'Boys Jeans Pant',
                    'category' => 'Boys Jeans Pant',
                    'fabric' => 'Denim',
                    'fit' => 'Slim Fit',
                    'length' => 'Full Length',
                    'productType' => 'Denim',
                    'color' => '#1e3a8a',
                    'colorName' => 'Navy Blue',
                ],
            ],
            [
                'name' => 'Kids Warm Fleece Hoodie & Jogger Set',
                'slug' => 'kids-warm-fleece-hoodie-jogger-set',
                'sku' => 'CLOTH-K06',
                'sub_name' => 'Kids Winter Wear',
                'brand' => 'Loomora Kids',
                'price' => 950,
                'compare_price' => 1150,
                'image' => '/images/c-8.avif',
                'badge' => 'New',
                'sizes' => ['4-5Y', '6-7Y', '8-9Y'],
                'attributes' => [
                    'subcategory' => 'Kids Winter Wear',
                    'category' => 'Kids Winter Wear',
                    'fabric' => 'Fleece',
                    'fit' => 'Regular Fit',
                    'sleeve' => 'Full Sleeve',
                    'neck' => 'Hooded Neck',
                    'length' => 'Long',
                    'productType' => 'Hoodie',
                    'set' => '2pcs',
                    'color' => '#12509b',
                    'colorName' => 'Royal Navy',
                ],
            ],
            [
                'name' => 'Boys Festive Silk Embroidered Panjabi',
                'slug' => 'boys-festive-silk-embroidered-panjabi',
                'sku' => 'CLOTH-K07',
                'sub_name' => 'Boys Panjabi',
                'brand' => 'Loomora Kids',
                'price' => 1450,
                'compare_price' => 1800,
                'image' => '/images/men/m-4.jpg',
                'badge' => 'New',
                'sizes' => ['4-5Y', '6-7Y', '8-9Y', '10-11Y'],
                'attributes' => [
                    'subcategory' => 'Boys Panjabi',
                    'category' => 'Boys Panjabi',
                    'fabric' => 'Silk Blend',
                    'fit' => 'Regular Fit',
                    'sleeve' => 'Full Sleeve',
                    'neck' => 'Mandarin Collar',
                    'length' => 'Long',
                    'productType' => 'Panjabi',
                    'addition' => ['Embroidery'],
                    'festive' => ['Eid 2026', 'Puja 2026'],
                    'color' => '#1e3a8a',
                    'colorName' => 'Indigo Blue',
                ],
            ],
            [
                'name' => 'Girls Twill Casual Ruffled Top',
                'slug' => 'girls-twill-casual-ruffled-top',
                'sku' => 'CLOTH-K08',
                'sub_name' => 'Girls Tops',
                'brand' => 'DHEU Junior',
                'price' => 650,
                'compare_price' => 850,
                'image' => '/images/women/m-5.jpg',
                'badge' => 'New',
                'sizes' => ['4-5Y', '6-7Y', '8-9Y'],
                'attributes' => [
                    'subcategory' => 'Girls Tops',
                    'category' => 'Girls Tops',
                    'fabric' => 'Fine Cotton Twill',
                    'fit' => 'Regular Fit',
                    'sleeve' => 'Short Sleeve',
                    'neck' => 'Round Neck',
                    'length' => 'Short',
                    'productType' => 'Tops',
                    'color' => '#fbbf24',
                    'colorName' => 'Sunshine Yellow',
                ],
            ],
        ];

        // Seed Women Products
        foreach ($womenCatalog as $item) {
            $brand = $brands[$item['brand']] ?? $loomora;
            $targetSub = $womenSubs[$item['sub_name']] ?? $womenCategory;

            $product = Product::updateOrCreate(
                ['sku' => $item['sku']],
                [
                    'name' => $item['name'],
                    'slug' => $item['slug'],
                    'category_id' => $targetSub->id,
                    'brand_id' => $brand->id,
                    'product_type' => 'apparel',
                    'price' => $item['price'],
                    'compare_price' => $item['compare_price'],
                    'cost_price' => round($item['price'] * 0.55, 2),
                    'stock_quantity' => 100,
                    'low_stock_threshold' => 10,
                    'status' => 'published',
                    'is_featured' => true,
                    'is_new_arrival' => true,
                    'is_hot' => false,
                    'is_trending' => true,
                    'badge_label' => $item['badge'],
                    'primary_image' => $item['image'],
                    'short_description' => "Premium {$item['name']} by {$item['brand']}.",
                    'description' => "Crafted from {$item['attributes']['fabric']}. Designed for exceptional elegance and lasting comfort.",
                    'attributes' => $item['attributes'],
                    'has_variants' => count($item['sizes']) > 0,
                ]
            );

            ProductVariant::where('product_id', $product->id)->delete();
            foreach ($item['sizes'] as $size) {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => "{$item['sku']}-".Str::slug($size),
                    'price' => $item['price'],
                    'stock_quantity' => 25,
                    'option_values' => ['size' => $size],
                ]);
            }
        }

        // Seed Kids Products
        foreach ($kidsCatalog as $item) {
            $brand = $brands[$item['brand']] ?? $loomoraKids;
            $targetSub = $kidsSubs[$item['sub_name']] ?? $kidsCategory;

            $product = Product::updateOrCreate(
                ['sku' => $item['sku']],
                [
                    'name' => $item['name'],
                    'slug' => $item['slug'],
                    'category_id' => $targetSub->id,
                    'brand_id' => $brand->id,
                    'product_type' => 'apparel',
                    'price' => $item['price'],
                    'compare_price' => $item['compare_price'],
                    'cost_price' => round($item['price'] * 0.55, 2),
                    'stock_quantity' => 100,
                    'low_stock_threshold' => 10,
                    'status' => 'published',
                    'is_featured' => true,
                    'is_new_arrival' => true,
                    'is_hot' => false,
                    'is_trending' => true,
                    'badge_label' => $item['badge'],
                    'primary_image' => $item['image'],
                    'short_description' => "Playful {$item['name']} by {$item['brand']}.",
                    'description' => "Crafted from {$item['attributes']['fabric']}. Perfect for everyday comfort and festive occasions.",
                    'attributes' => $item['attributes'],
                    'has_variants' => count($item['sizes']) > 0,
                ]
            );

            ProductVariant::where('product_id', $product->id)->delete();
            foreach ($item['sizes'] as $size) {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => "{$item['sku']}-".Str::slug($size),
                    'price' => $item['price'],
                    'stock_quantity' => 25,
                    'option_values' => ['size' => $size],
                ]);
            }
        }
    }
}
