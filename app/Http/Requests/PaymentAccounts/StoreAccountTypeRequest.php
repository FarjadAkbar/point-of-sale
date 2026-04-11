<?php

namespace App\Http\Requests\PaymentAccounts;

use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (in_array($this->input('parent_account_type_id'), ['', null, '__none__'], true)) {
            $this->merge(['parent_account_type_id' => null]);
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
            'parent_account_type_id' => [
                'nullable',
                'integer',
                Rule::exists('account_types', 'id')->where(function ($q) use ($team) {
                    $q->where('team_id', $team->id)->whereNull('parent_id');
                }),
            ],
        ];
    }
}
