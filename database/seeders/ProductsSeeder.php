<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('products')->insert([
            [
                'name' => 'Shirt',
                'price' => 49.00,
                'currency' => '€',
                'image' => 'https://4.imimg.com/data4/OX/VM/MY-35263749/men-s-casual-shirt-500x500.jpg',
                'created_at' => Carbon::now()
            ],
            [
                'name' => 'Shoes',
                'price' => 99.00,
                'currency' => '€',
                'image' => 'https://images.pexels.com/photos/19090/pexels-photo.jpg',
                'created_at' => Carbon::now()
            ],
            [
                'name' => 'Watch',
                'price' => 199.00,
                'currency' => '€',
                'image' => 'https://images.pexels.com/photos/190819/pexels-photo-190819.jpeg?auto=compress&cs=tinysrgb&w=600',
                'created_at' => Carbon::now()
            ],
        ]);
    }
}
