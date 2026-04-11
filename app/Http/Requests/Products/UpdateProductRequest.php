<?php

namespace App\Http\Requests\Products;

use App\Models\Product;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateProductRequest extends StoreProductRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Product $product */
        $product = $this->route('product');

        $rules = parent::rules();
        $team = $this->route('current_team');
        $rules['sku'] = [
            'nullable',
            'string',
            'max:100',
            Rule::unique('products', 'sku')
                ->where('team_id', $team->id)
                ->ignore($product->id),
        ];

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        parent::withValidator($validator);

        $validator->after(function (Validator $v) {
            /** @var Product $product */
            $product = $this->route('product');
            if ($this->input('product_type') !== 'combo') {
                return;
            }

            $lines = $this->input('combo_lines', []);
            if (! is_array($lines)) {
                return;
            }

            foreach ($lines as $i => $line) {
                if (! is_array($line)) {
                    continue;
                }
                if ((int) ($line['product_id'] ?? 0) === $product->id) {
                    $v->errors()->add(
                        "combo_lines.$i.product_id",
                        'A combo cannot include itself.',
                    );
                }
            }
        });
    }
}
