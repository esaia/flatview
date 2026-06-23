<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class MenuItemSeeder extends Seeder
{
    public function run(): void
    {
        if (MenuItem::count() > 0) {
            return;
        }

        $items = [
            ['label' => 'Home',     'href' => '/',         'sort_order' => 1],
            ['label' => 'Work',     'href' => '/work',     'sort_order' => 2],
            ['label' => 'Services', 'href' => '/services', 'sort_order' => 3],
            ['label' => 'About',    'href' => '/about',    'sort_order' => 4],
            ['label' => 'Contact',  'href' => '/contact',  'sort_order' => 5],
        ];

        foreach ($items as $item) {
            MenuItem::create($item);
        }
    }
}
