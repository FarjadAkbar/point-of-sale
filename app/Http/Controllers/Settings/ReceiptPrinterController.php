<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateReceiptPrinterRequest;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ReceiptPrinterController extends Controller
{
    public function edit(Team $current_team): Response
    {
        $settings = $current_team->receipt_printer_settings ?? [];

        return Inertia::render('receipt-printer/Index', [
            'receiptPrinter' => [
                'connection_type' => $settings['connection_type'] ?? 'network',
            ],
        ]);
    }

    public function update(UpdateReceiptPrinterRequest $request, Team $current_team): RedirectResponse
    {
        $validated = $request->validated();
        $merged = array_merge($current_team->receipt_printer_settings ?? [], $validated);

        $current_team->update([
            'receipt_printer_settings' => $merged,
        ]);

        return redirect()->route('receipt-printer.edit', ['current_team' => $current_team]);
    }
}
