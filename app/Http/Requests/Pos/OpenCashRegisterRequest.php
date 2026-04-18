<?php

namespace App\Http\Requests\Pos;

use App\Models\CashRegisterSession;
use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OpenCashRegisterRequest extends FormRequest
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
            'business_location_id' => [
                'required',
                'integer',
                Rule::exists('business_locations', 'id')->where('team_id', $team->id),
            ],
            'amount' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            /** @var Team $team */
            $team = $this->route('current_team');
            $userId = (int) $this->user()->id;

            $existing = CashRegisterSession::query()
                ->forTeam($team)
                ->open()
                ->where('user_id', $userId)
                ->first();

            if (! $existing) {
                return;
            }

            $locId = (int) $this->input('business_location_id');
            if ((int) $existing->business_location_id !== $locId) {
                $v->errors()->add(
                    'business_location_id',
                    'You already have an open register at another location. Close it before opening a new one.',
                );
            }
        });
    }
}
