<?php

namespace App\Http\Controllers\PaymentAccounts;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentAccounts\StoreAccountTypeRequest;
use App\Models\AccountType;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;

class AccountTypeController extends Controller
{
    public function store(StoreAccountTypeRequest $request, Team $current_team): RedirectResponse
    {
        $data = $request->validated();

        AccountType::query()->create([
            'team_id' => $current_team->id,
            'name' => $data['name'],
            'parent_id' => $data['parent_account_type_id'] ?? null,
        ]);

        return back()->with('success', 'Account type saved.');
    }
}
