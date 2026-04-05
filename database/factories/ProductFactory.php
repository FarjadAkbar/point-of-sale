<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'name' => fake()->words(3, true),
            'sku' => null,
            'barcode_type' => null,
            'unit_id' => null,
            'brand_id' => null,
            'category_id' => null,
            'subcategory_id' => null,
            'business_location_ids' => null,
            'manage_stock' => false,
            'alert_quantity' => null,
            'description' => null,
            'image_path' => null,
            'brochure_path' => null,
            'enable_imei_serial' => false,
            'not_for_selling' => false,
            'weight' => null,
            'preparation_time_minutes' => null,
            'application_tax' => 'none',
            'selling_price_tax_type' => 'exclusive',
            'product_type' => 'single',
            'single_dpp' => 10,
            'single_dpp_inc_tax' => 11,
            'profit_percent' => 25,
            'single_dsp' => 12.5,
            'single_dsp_inc_tax' => null,
            'combo_profit_percent' => null,
            'combo_selling_price' => null,
            'combo_selling_price_inc_tax' => null,
            'combo_lines' => null,
            'combo_purchase_total_exc_tax' => null,
            'combo_purchase_total_inc_tax' => null,
            'variation_sku_format' => null,
            'variation_matrix' => null,
        ];
    }
}
