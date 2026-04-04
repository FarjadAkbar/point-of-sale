<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBarcodeSettingsRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        foreach (['paper_height_inches', 'stickers_in_one_row', 'stickers_per_sheet'] as $key) {
            $v = $this->input($key);
            if ($v === '' || $v === null) {
                $this->merge([$key => null]);
            }
        }

        $this->merge([
            'continuous_feed_or_rolls' => $this->boolean('continuous_feed_or_rolls'),
            'set_as_default' => $this->boolean('set_as_default'),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'sticker_sheet_name' => ['required', 'string', 'max:255'],
            'sticker_sheet_description' => ['nullable', 'string'],
            'continuous_feed_or_rolls' => ['boolean'],
            'additional_top_margin_inches' => ['required', 'numeric', 'min:0'],
            'additional_left_margin_inches' => ['required', 'numeric', 'min:0'],
            'width_of_sticker_inches' => ['required', 'numeric', 'min:0'],
            'height_of_sticker_inches' => ['required', 'numeric', 'min:0'],
            'paper_width_inches' => ['required', 'numeric', 'min:0'],
            'paper_height_inches' => [
                Rule::requiredIf(fn () => $this->boolean('continuous_feed_or_rolls')),
                'nullable',
                'numeric',
                'min:0',
            ],
            'stickers_in_one_row' => [
                Rule::requiredIf(fn () => $this->boolean('continuous_feed_or_rolls')),
                'nullable',
                'integer',
                'min:1',
            ],
            'distance_between_two_rows_inches' => ['required', 'numeric', 'min:0'],
            'distance_between_two_columns_inches' => ['required', 'numeric', 'min:0'],
            'stickers_per_sheet' => [
                Rule::requiredIf(fn () => $this->boolean('continuous_feed_or_rolls')),
                'nullable',
                'integer',
                'min:1',
            ],
            'set_as_default' => ['boolean'],
        ];
    }
}
