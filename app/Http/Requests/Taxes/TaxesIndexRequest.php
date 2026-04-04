<?php

namespace App\Http\Requests\Taxes;

use Illuminate\Foundation\Http\FormRequest;

class TaxesIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rate_search' => ['nullable', 'string', 'max:255'],
            'rate_sort' => ['nullable', 'string', 'in:id,name,amount,for_tax_group,created_at'],
            'rate_direction' => ['nullable', 'string', 'in:asc,desc'],
            'rate_per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
            'rate_page' => ['nullable', 'integer', 'min:1'],
            'group_search' => ['nullable', 'string', 'max:255'],
            'group_sort' => ['nullable', 'string', 'in:id,name,created_at'],
            'group_direction' => ['nullable', 'string', 'in:asc,desc'],
            'group_per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
            'group_page' => ['nullable', 'integer', 'min:1'],
            'edit_rate' => ['nullable', 'integer', 'min:1'],
            'edit_group' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rateFilters(): array
    {
        $v = $this->validated();

        return [
            'search' => $v['rate_search'] ?? '',
            'sort' => $v['rate_sort'] ?? 'created_at',
            'direction' => $v['rate_direction'] ?? 'desc',
            'per_page' => $v['rate_per_page'] ?? 15,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function groupFilters(): array
    {
        $v = $this->validated();

        return [
            'search' => $v['group_search'] ?? '',
            'sort' => $v['group_sort'] ?? 'created_at',
            'direction' => $v['group_direction'] ?? 'desc',
            'per_page' => $v['group_per_page'] ?? 15,
        ];
    }
}
