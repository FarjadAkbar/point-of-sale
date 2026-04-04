<?php

namespace App\Http\Requests\Products;

use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('sku') === '') {
            $this->merge(['sku' => null]);
        }

        if ($this->input('barcode_type') === '' || $this->input('barcode_type') === null) {
            $this->merge(['barcode_type' => null]);
        }

        foreach (['combo_lines', 'variation_matrix', 'business_location_ids'] as $key) {
            $v = $this->input($key);
            if (is_string($v) && $v !== '') {
                $decoded = json_decode($v, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $this->merge([$key => $decoded]);
                }
            }
        }

        $this->merge([
            'manage_stock' => filter_var($this->input('manage_stock'), FILTER_VALIDATE_BOOLEAN),
            'enable_imei_serial' => filter_var($this->input('enable_imei_serial'), FILTER_VALIDATE_BOOLEAN),
            'not_for_selling' => filter_var($this->input('not_for_selling'), FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Team $team */
        $team = $this->route('current_team');

        $taxIds = collect(\App\Support\ProductOptionLists::taxOptions())->pluck('id')->all();
        $barcodeValues = collect(\App\Support\ProductOptionLists::barcodeTypes())->pluck('value')->all();

        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'sku')->where('team_id', $team->id),
            ],
            'barcode_type' => ['nullable', 'string', Rule::in($barcodeValues)],
            'unit_id' => ['nullable', 'integer', Rule::exists('units', 'id')->where('team_id', $team->id)],
            'brand_id' => ['nullable', 'integer', Rule::exists('brands', 'id')->where('team_id', $team->id)],
            'category_id' => ['nullable', 'integer', Rule::exists('product_categories', 'id')->where('team_id', $team->id)],
            'subcategory_id' => ['nullable', 'integer', Rule::exists('product_categories', 'id')->where('team_id', $team->id)],
            'business_location_ids' => ['nullable', 'array'],
            'business_location_ids.*' => ['string', 'max:64'],
            'manage_stock' => ['boolean'],
            'alert_quantity' => [
                Rule::requiredIf(fn () => $this->boolean('manage_stock')),
                'nullable',
                'numeric',
                'min:0',
            ],
            'description' => ['nullable', 'string', 'max:500000'],
            'product_image' => ['nullable', 'image', 'max:5120'],
            'product_brochure' => ['nullable', 'file', 'mimes:pdf,jpeg,jpg,png', 'max:10240'],
            'enable_imei_serial' => ['boolean'],
            'not_for_selling' => ['boolean'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'preparation_time_minutes' => ['nullable', 'integer', 'min:0', 'max:525600'],
            'application_tax' => ['nullable', 'string', Rule::in($taxIds)],
            'selling_price_tax_type' => ['required', 'string', Rule::in(['inclusive', 'exclusive'])],
            'product_type' => ['required', 'string', Rule::in(['single', 'variation', 'combo'])],

            'single_dpp' => ['required_if:product_type,single', 'nullable', 'numeric', 'min:0'],
            'single_dpp_inc_tax' => ['required_if:product_type,single', 'nullable', 'numeric', 'min:0'],
            'profit_percent' => ['required_if:product_type,single', 'nullable', 'numeric'],
            'single_dsp' => ['required_if:product_type,single', 'nullable', 'numeric', 'min:0'],
            'single_dsp_inc_tax' => ['nullable', 'numeric', 'min:0'],

            'combo_lines' => ['required_if:product_type,combo', 'nullable', 'array'],
            'combo_lines.*.product_id' => ['required_if:product_type,combo', 'integer', Rule::exists('products', 'id')->where('team_id', $team->id)],
            'combo_lines.*.quantity' => ['required_if:product_type,combo', 'numeric', 'min:0.000001'],
            'combo_lines.*.purchase_price_exc_tax' => ['required_if:product_type,combo', 'numeric', 'min:0'],
            'combo_lines.*.line_total_exc_tax' => ['required_if:product_type,combo', 'numeric', 'min:0'],
            'combo_lines.*.product_name' => ['nullable', 'string', 'max:255'],
            'combo_profit_percent' => ['required_if:product_type,combo', 'nullable', 'numeric'],
            'combo_selling_price' => ['required_if:product_type,combo', 'nullable', 'numeric', 'min:0'],
            'combo_selling_price_inc_tax' => ['nullable', 'numeric', 'min:0'],
            'combo_purchase_total_exc_tax' => ['nullable', 'numeric', 'min:0'],
            'combo_purchase_total_inc_tax' => ['nullable', 'numeric', 'min:0'],

            'variation_sku_format' => ['required_if:product_type,variation', 'nullable', 'string', Rule::in(['with_out_variation', 'with_variation'])],
            'variation_matrix' => ['required_if:product_type,variation', 'nullable', 'array'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function productPayload(): array
    {
        /** @var array<string, mixed> $data */
        $data = collect($this->validated())
            ->except(['product_image', 'product_brochure'])
            ->all();

        if (($data['product_type'] ?? '') !== 'single') {
            $data['single_dpp'] = $data['single_dpp'] ?? null;
            $data['single_dpp_inc_tax'] = $data['single_dpp_inc_tax'] ?? null;
            $data['profit_percent'] = $data['profit_percent'] ?? null;
            $data['single_dsp'] = $data['single_dsp'] ?? null;
            $data['single_dsp_inc_tax'] = $data['single_dsp_inc_tax'] ?? null;
        }
        if (($data['product_type'] ?? '') !== 'combo') {
            $data['combo_lines'] = null;
            $data['combo_profit_percent'] = null;
            $data['combo_selling_price'] = null;
            $data['combo_selling_price_inc_tax'] = null;
            $data['combo_purchase_total_exc_tax'] = null;
            $data['combo_purchase_total_inc_tax'] = null;
        }
        if (($data['product_type'] ?? '') !== 'variation') {
            $data['variation_sku_format'] = null;
            $data['variation_matrix'] = null;
        }

        if (empty($data['manage_stock'])) {
            $data['alert_quantity'] = null;
        }

        return $data;
    }
}
