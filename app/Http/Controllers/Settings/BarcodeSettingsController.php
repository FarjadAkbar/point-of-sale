<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateBarcodeSettingsRequest;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BarcodeSettingsController extends Controller
{
    /**
     * @return array<string, mixed>
     */
    private function defaults(): array
    {
        return [
            'sticker_sheet_name' => '',
            'sticker_sheet_description' => '',
            'continuous_feed_or_rolls' => false,
            'additional_top_margin_inches' => '',
            'additional_left_margin_inches' => '',
            'width_of_sticker_inches' => '',
            'height_of_sticker_inches' => '',
            'paper_width_inches' => '',
            'paper_height_inches' => '',
            'stickers_in_one_row' => '',
            'distance_between_two_rows_inches' => '',
            'distance_between_two_columns_inches' => '',
            'stickers_per_sheet' => '',
            'set_as_default' => false,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeForFrontend(array $data): array
    {
        $data['continuous_feed_or_rolls'] = (bool) ($data['continuous_feed_or_rolls'] ?? false);
        $data['set_as_default'] = (bool) ($data['set_as_default'] ?? false);

        foreach ([
            'additional_top_margin_inches',
            'additional_left_margin_inches',
            'width_of_sticker_inches',
            'height_of_sticker_inches',
            'paper_width_inches',
            'paper_height_inches',
            'distance_between_two_rows_inches',
            'distance_between_two_columns_inches',
        ] as $key) {
            if (array_key_exists($key, $data) && is_numeric($data[$key])) {
                $data[$key] = (string) $data[$key];
            }
        }

        foreach (['stickers_in_one_row', 'stickers_per_sheet'] as $key) {
            if (array_key_exists($key, $data) && is_numeric($data[$key])) {
                $data[$key] = (string) (int) $data[$key];
            }
        }

        return $data;
    }

    public function edit(Team $current_team): Response
    {
        $stored = $current_team->barcode_settings ?? [];
        $merged = array_merge($this->defaults(), $stored);

        return Inertia::render('barcode-settings/Index', [
            'barcodeSettings' => $this->normalizeForFrontend($merged),
        ]);
    }

    public function update(UpdateBarcodeSettingsRequest $request, Team $current_team): RedirectResponse
    {
        $validated = $request->validated();
        $merged = array_merge($current_team->barcode_settings ?? [], $validated);

        $current_team->update([
            'barcode_settings' => $merged,
        ]);

        return redirect()->route('barcode-settings.edit', ['current_team' => $current_team]);
    }
}
