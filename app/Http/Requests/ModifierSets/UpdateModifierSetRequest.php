<?php

namespace App\Http\Requests\ModifierSets;

use App\Models\ModifierSet;
use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateModifierSetRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Team $team */
        $team = $this->route('current_team');
        /** @var ModifierSet $modifierSet */
        $modifierSet = $this->route('modifier_set');

        return $modifierSet->team_id === $team->id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.name' => ['nullable', 'string', 'max:255'],
            'items.*.price' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $rows = collect($this->input('items', []))
                ->filter(fn ($row) => is_array($row) && trim((string) ($row['name'] ?? '')) !== '');

            if ($rows->isEmpty()) {
                $v->errors()->add('items', 'Add at least one modifier with a name and price.');
            }
        });
    }

    /**
     * @return array{name: string, items: list<array{name: string, price: float|int|string}>}
     */
    public function validatedData(): array
    {
        /** @var array{name: string, items: list<array{name?: string, price?: mixed}>} $data */
        $data = $this->validated();

        $items = [];
        foreach ($data['items'] as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $price = $row['price'] ?? 0;
            if ($price === '' || $price === null) {
                $price = 0;
            }
            $items[] = [
                'name' => $name,
                'price' => $price,
            ];
        }

        return [
            'name' => $data['name'],
            'items' => $items,
        ];
    }
}
