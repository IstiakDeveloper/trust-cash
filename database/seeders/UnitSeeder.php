<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            // Standard Count / Quantity
            ['name' => 'Piece', 'short_name' => 'pcs', 'status' => true],
            ['name' => 'Pair', 'short_name' => 'pair', 'status' => true],
            ['name' => 'Dozen', 'short_name' => 'dozen', 'status' => true],
            ['name' => 'Set', 'short_name' => 'set', 'status' => true],

            // Weight / ভর ও ওজন
            ['name' => 'Kilogram', 'short_name' => 'kg', 'status' => true],
            ['name' => 'Gram', 'short_name' => 'gm', 'status' => true],
            ['name' => 'Milligram', 'short_name' => 'mg', 'status' => true],
            ['name' => 'Ton', 'short_name' => 'ton', 'status' => true],

            // Volume & Liquid / তরল ও আয়তন
            ['name' => 'Liter', 'short_name' => 'ltr', 'status' => true],
            ['name' => 'Milliliter', 'short_name' => 'ml', 'status' => true],

            // Packaging & Containers / প্যাকেট ও কন্টেইনার
            ['name' => 'Packet', 'short_name' => 'pkt', 'status' => true],
            ['name' => 'Pack', 'short_name' => 'pack', 'status' => true],
            ['name' => 'Box', 'short_name' => 'box', 'status' => true],
            ['name' => 'Carton', 'short_name' => 'carton', 'status' => true],
            ['name' => 'Bag', 'short_name' => 'bag', 'status' => true],
            ['name' => 'Sack', 'short_name' => 'sack', 'status' => true],
            ['name' => 'Bottle', 'short_name' => 'bottle', 'status' => true],
            ['name' => 'Can', 'short_name' => 'can', 'status' => true],
            ['name' => 'Tube', 'short_name' => 'tube', 'status' => true],
            ['name' => 'Bundle', 'short_name' => 'bundle', 'status' => true],
            ['name' => 'Roll', 'short_name' => 'roll', 'status' => true],

            // Length & Area / দৈর্ঘ্য ও পরিমাপ
            ['name' => 'Meter', 'short_name' => 'm', 'status' => true],
            ['name' => 'Centimeter', 'short_name' => 'cm', 'status' => true],
            ['name' => 'Foot', 'short_name' => 'ft', 'status' => true],
            ['name' => 'Inch', 'short_name' => 'in', 'status' => true],
            ['name' => 'Square Feet', 'short_name' => 'sqft', 'status' => true],
            ['name' => 'Square Meter', 'short_name' => 'sqm', 'status' => true],

            // Stationery, Pharma & Miscellaneous / স্টেশনারি ও ফার্মেসি
            ['name' => 'Strip', 'short_name' => 'strip', 'status' => true],
            ['name' => 'Sheet', 'short_name' => 'sheet', 'status' => true],
            ['name' => 'Ream', 'short_name' => 'ream', 'status' => true],
            ['name' => 'Pad', 'short_name' => 'pad', 'status' => true],
        ];

        foreach ($units as $unit) {
            Unit::firstOrCreate(
                ['short_name' => $unit['short_name']],
                [
                    'name' => $unit['name'],
                    'status' => $unit['status'],
                ]
            );
        }
    }
}
