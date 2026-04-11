<?php

namespace App\Http\Requests\Expenses;

use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreExpenseCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_subcategory' => $this->boolean('is_subcategory'),
        ]);

        if (! $this->boolean('is_subcategory')) {
            $this->merge(['parent_id' => null]);
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
                'required',
                'string',
                'max:64',
                Rule::unique('expense_categories', 'code')->where('team_id', $team->id),
            ],
            'is_subcategory' => ['sometimes', 'boolean'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('expense_categories', 'id')->where(function ($q) use ($team) {
                    $q->where('team_id', $team->id)->whereNull('parent_id');
                }),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ($this->boolean('is_subcategory') && ! $this->filled('parent_id')) {
                $v->errors()->add('parent_id', 'Select a parent category for a subcategory.');
            }
        });
    }
}
