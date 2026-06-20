<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'National',
            'Health',
            'Religion',
            'Entertainment',
            'Tech',
            'Politics',
            'Sports',
            'Business',
            'Exclusive',
            'Lifestyle',
            'Cultural',
            'Crime',
        ];

        foreach ($categories as $name) {
            Category::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name],
            );
        }
    }
}
