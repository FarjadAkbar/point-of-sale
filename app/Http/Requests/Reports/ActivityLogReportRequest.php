<?php

namespace App\Http\Requests\Reports;

use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActivityLogReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'start_date' => $this->input('start_date', now()->startOfMonth()->toDateString()),
            'end_date' => $this->input('end_date', now()->toDateString()),
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
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists('team_members', 'user_id')->where('team_id', $team->id),
            ],
            'subject_type' => [
                'nullable',
                'string',
                Rule::in([
                    '',
                    'contact',
                    'user',
                    'sell',
                    'purchase',
                    'sales_order',
                    'purchase_order',
                    'sell_return',
                    'purchase_return',
                    'sell_transfer',
                    'stock_adjustment',
                    'expense',
                ]),
            ],
        ];
    }
}
