<?php

namespace App\Http\Requests\Sales;

use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSaleRequest extends FormRequest
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
        /** @var Team $team */
        $team = $this->route('current_team');

        return [
            'invoice_no' => ['nullable', 'string', 'max:255'],
            'sale_note' => ['nullable', 'string', 'max:65535'],
            'transaction_date' => ['required', 'date'],
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('customers', 'id')->where('team_id', $team->id),
            ],
        ];
    }
}
