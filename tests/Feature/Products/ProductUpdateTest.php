<?php

namespace Tests\Feature\Products;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_variation_product_without_file_uploads(): void
    {
        $user = User::factory()->create();
        $team = $user->fresh()->currentTeam;
        $this->assertNotNull($team);

        $product = Product::factory()->create([
            'team_id' => $team->id,
            'name' => 'Timon Atkins',
            'sku' => 'sad',
            'product_type' => 'variation',
            'variation_sku_format' => 'with_out_variation',
            'variation_matrix' => [
                [
                    'variation_template_id' => null,
                    'variations' => [
                        [
                            'sub_sku' => 'sd',
                            'value' => '332',
                            'default_purchase_price' => '',
                            'dpp_inc_tax' => '',
                            'profit_percent' => '25',
                            'default_sell_price' => '',
                            'sell_price_inc_tax' => '',
                        ],
                    ],
                ],
            ],
        ]);

        $payload = [
            'name' => 'Timon Atkinss',
            'sku' => 'sad',
            'barcode_type' => 'upca',
            'unit_id' => null,
            'brand_id' => null,
            'category_id' => null,
            'subcategory_id' => null,
            'manage_stock' => false,
            'alert_quantity' => null,
            'description' => '<p>asd</p>',
            'enable_imei_serial' => false,
            'not_for_selling' => false,
            'weight' => '23.0000',
            'application_tax' => 'none',
            'selling_price_tax_type' => 'exclusive',
            'product_type' => 'variation',
            'profit_percent' => '25.0000',
            'combo_lines' => [],
            'combo_profit_percent' => '25',
            'variation_sku_format' => 'with_out_variation',
            'business_location_ids' => [],
            'variation_matrix' => [
                [
                    'variation_template_id' => null,
                    'variations' => [
                        [
                            'sub_sku' => 'sd',
                            'value' => '332',
                            'default_purchase_price' => '',
                            'dpp_inc_tax' => '',
                            'profit_percent' => '25',
                            'default_sell_price' => '',
                            'sell_price_inc_tax' => '',
                        ],
                    ],
                ],
            ],
            'opening_stocks' => [],
        ];

        $this->actingAs($user)
            ->put(route('products.update', ['current_team' => $team->slug, 'product' => $product->id]), $payload)
            ->assertRedirect(route('products.index', ['current_team' => $team->slug]));

        $this->assertSame('Timon Atkinss', $product->fresh()->name);
    }
}
