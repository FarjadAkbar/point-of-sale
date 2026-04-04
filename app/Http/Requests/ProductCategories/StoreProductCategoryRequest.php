<?php

namespace App\Http\Requests\ProductCategories;

use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => $this->code === '' ? null : $this->code,
        ]);

        if (! filter_var($this->input('is_sub_taxonomy'), FILTER_VALIDATE_BOOLEAN)) {
            $this->merge(['parent_id' => null, 'is_sub_taxonomy' => false]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Team $team */
        $team = $this->route('current_team');

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('product_categories', 'code')->where('team_id', $team->id),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_sub_taxonomy' => ['nullable', 'boolean'],
            'parent_id' => [
                Rule::requiredIf(fn () => filter_var($this->input('is_sub_taxonomy'), FILTER_VALIDATE_BOOLEAN)),
                'nullable',
                'integer',
                Rule::exists('product_categories', 'id')->where(function ($q) use ($team) {
                    $q->where('team_id', $team->id)
                        ->where('is_sub_taxonomy', false);
                }),
            ],
        ];
    }
}
