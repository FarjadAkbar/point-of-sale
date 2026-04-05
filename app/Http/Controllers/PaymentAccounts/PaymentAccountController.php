<?php

namespace App\Http\Controllers\PaymentAccounts;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentAccounts\StorePaymentAccountRequest;
use App\Http\Requests\PaymentAccounts\UpdatePaymentAccountRequest;
use App\Models\PaymentAccount;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentAccountController extends Controller
{
    public function store(StorePaymentAccountRequest $request, Team $current_team): RedirectResponse
    {
        $data = $request->validated();
        $data['team_id'] = $current_team->id;
        $data['is_active'] = $data['is_active'] ?? true;

        PaymentAccount::query()->create($data);

        return to_route('payment-settings.edit', ['current_team' => $current_team])
            ->with('success', 'Payment account created.');
    }

    public function update(UpdatePaymentAccountRequest $request, Team $current_team, PaymentAccount $payment_account): RedirectResponse
    {
        $payment_account->update($request->validated());

        return to_route('payment-settings.edit', ['current_team' => $current_team])
            ->with('success', 'Payment account updated.');
    }

    public function destroy(Request $request, Team $current_team, PaymentAccount $payment_account): RedirectResponse
    {
        $payment_account->delete();

        return to_route('payment-settings.edit', ['current_team' => $current_team])
            ->with('success', 'Payment account deleted.');
    }
}
