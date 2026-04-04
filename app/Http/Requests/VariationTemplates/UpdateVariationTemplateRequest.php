<?php

namespace App\Http\Requests\VariationTemplates;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVariationTemplateRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'values' => ['sometimes', 'array'],
            'values.*' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array{name?: string, values?: list<string>}
     */
    public function validatedData(): array
    {
        /** @var array{name?: string, values?: list<string|null>} $data */
        $data = $this->validated();
        $out = [];
        if (array_key_exists('name', $data)) {
            $out['name'] = $data['name'];
        }
        if (array_key_exists('values', $data)) {
            $out['values'] = array_values(array_filter(
                array_map(fn ($v) => is_string($v) ? trim($v) : '', $data['values'] ?? []),
                fn (string $v) => $v !== ''
            ));
        }

        return $out;
    }
}
