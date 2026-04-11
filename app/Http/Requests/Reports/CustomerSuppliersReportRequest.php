<?php

namespace App\Http\Requests\Reports;

use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerSuppliersReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $contactKey = $this->input('contact_key');
        $this->merge([
            'start_date' => $this->input('start_date', now()->startOfMonth()->toDateString()),
            'end_date' => $this->input('end_date', now()->toDateString()),
            'contact_type' => $this->input('contact_type', ''),
            'contact_key' => ($contactKey === null || $contactKey === '') ? null : $contactKey,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Team $team */
        $team = $this->route('current_team');

        return [
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'business_location_id' => [
                'nullable',
                'integer',
                Rule::exists('business_locations', 'id')->where('team_id', $team->id),
            ],
            'customer_group_id' => [
                'nullable',
                'integer',
                Rule::exists('customer_groups', 'id')->where('team_id', $team->id),
            ],
            'contact_type' => ['nullable', 'string', Rule::in(['', 'all', 'customer', 'supplier'])],
            'contact_key' => [
                'nullable',
                'string',
                'max:64',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null || $value === '') {
                        return;
                    }
                    if (! is_string($value) || ! preg_match('/^(c|s)-\d+$/', $value)) {
                        $fail('The contact selection is invalid.');

                        return;
                    }
                    $type = (string) $this->input('contact_type', '');
                    if ($type === 'customer' && ! str_starts_with($value, 'c-')) {
                        $fail('The contact must be a customer for this type filter.');
                    }
                    if ($type === 'supplier' && ! str_starts_with($value, 's-')) {
                        $fail('The contact must be a supplier for this type filter.');
                    }
                },
            ],
        ];
    }
}
