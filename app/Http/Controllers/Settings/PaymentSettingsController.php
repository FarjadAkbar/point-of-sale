<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdatePaymentSettingsRequest;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PaymentSettingsController extends Controller
{
    public function edit(Team $current_team): Response
    {
        $accounts = $current_team->paymentAccounts()
            ->orderBy('payment_method')
            ->orderBy('name')
            ->get();

        return Inertia::render('payment-settings/Index', [
            'paymentSettings' => $current_team->resolvedPaymentSettings(),
            'paymentAccounts' => $accounts->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'payment_method' => $a->payment_method,
                'bank_name' => $a->bank_name,
                'account_number' => $a->account_number,
                'notes' => $a->notes,
                'is_active' => $a->is_active,
            ]),
        ]);
    }

    public function update(UpdatePaymentSettingsRequest $request, Team $current_team): RedirectResponse
    {
        $current_team->update([
            'payment_settings' => $request->paymentSettingsPayload(),
        ]);

        return back()->with('success', 'Payment settings updated.');
    }
}
