<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class WomenKidsCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $women = Category::where('slug', 'women')->orWhere('id', 53)->first();
        if ($women) {
            $womenSubs = [
                'Single Ethnic', 'Ethnic Set', 'Fashion Tops', 'Women Shirt', 'Womens Tees & Tank',
                'Scarf', 'Saree', 'Maternity Wear', 'Poncho', 'Womens Jacket', 'Womens Hoodie',
                'Womens Biker Jacket', 'Womens Sweatshirt', 'Womens Sweater', 'Womens Blazer',
                'Womens Overcoat', 'Womens Denim', 'Womens Pant', 'Womens Chino Pant', 'Womens Jeans Pant',
                'Womens Formal Pant', 'Womens Cargo Pant', 'Womens Joggers', 'Womens Skirt',
                'Womens Palazzo', 'Womens Trouser', 'Midi Dress', 'Western Gown', 'Womens Shrug',
                'Womens Party Wear', 'CO-ORD', 'Sleepwear',
            ];

            foreach ($womenSubs as $i => $name) {
                Category::firstOrCreate(
                    [
                        'name' => $name,
                        'parent_id' => $women->id,
                    ],
                    [
                        'slug' => Str::slug($name),
                        'sort_order' => $i + 1,
                        'is_active' => true,
                    ]
                );
            }
        }

        $kids = Category::where('slug', 'kids')->orWhere('id', 54)->first();
        if ($kids) {
            $kidsSubs = [
                'Boys Panjabi', 'Boys Shirt', 'Boys T-Shirt', 'Boys Polo', 'Boys Denim', 'Boys Pant',
                'Girls Frock', 'Girls Tops', 'Girls Kurti', 'Girls Leggings', 'Girls Skirt',
                'Kids Winter Wear', 'Kids Jacket', 'Kids Hoodie', 'Kids Nightwear',
            ];

            foreach ($kidsSubs as $i => $name) {
                Category::firstOrCreate(
                    [
                        'name' => $name,
                        'parent_id' => $kids->id,
                    ],
                    [
                        'slug' => Str::slug($name),
                        'sort_order' => $i + 1,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
