<?php

namespace App\Http\Controllers\PaymentAccounts;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentAccounts\StorePaymentAccountRequest;
use App\Http\Requests\PaymentAccounts\UpdatePaymentAccountRequest;
use App\Models\AccountType;
use App\Models\PaymentAccount;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentAccountController extends Controller
{
    public function index(Request $request, Team $current_team): Response
    {
        $status = $request->query('status', 'active');
        $status = $status === 'closed' ? 'closed' : 'active';

        $accountsQuery = PaymentAccount::query()
            ->forTeam($current_team)
            ->with(['accountType.parent', 'creator'])
            ->orderByDesc('id');

        if ($status === 'closed') {
            $accountsQuery->where('is_active', false);
        } else {
            $accountsQuery->where('is_active', true);
        }

        $paginator = $accountsQuery
            ->paginate(20)
            ->withQueryString()
            ->through(fn (PaymentAccount $a) => $this->accountRow($a));

        $accountTypes = AccountType::query()
            ->forTeam($current_team)
            ->with('parent')
            ->orderBy('name')
            ->get();

        $typeRows = $accountTypes->map(fn (AccountType $t) => [
            'id' => $t->id,
            'name' => $t->name,
            'parent' => $t->parent ? [
                'id' => $t->parent->id,
                'name' => $t->parent->name,
            ] : null,
        ]);

        $parentTypeOptions = AccountType::query()
            ->forTeam($current_team)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get(['id', 'name']);

        $ledgerTypeOptions = AccountType::query()
            ->forTeam($current_team)
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);

        return Inertia::render('payment-accounts/Index', [
            'accounts' => $paginator,
            'accountTypes' => $typeRows,
            'parentTypeOptions' => $parentTypeOptions,
            'ledgerTypeOptions' => $ledgerTypeOptions,
            'filters' => [
                'status' => $status,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function accountRow(PaymentAccount $a): array
    {
        $type = $a->accountType;
        $typeName = '—';
        $subName = '—';
        if ($type) {
            if ($type->parent) {
                $typeName = $type->parent->name;
                $subName = $type->name;
            } else {
                $typeName = $type->name;
            }
        }

        $detailsPreview = '—';
        if (is_array($a->account_details) && $a->account_details !== []) {
            $parts = [];
            foreach (array_slice($a->account_details, 0, 2) as $pair) {
                $l = (string) ($pair['label'] ?? '');
                $v = (string) ($pair['value'] ?? '');
                if ($l !== '' || $v !== '') {
                    $parts[] = trim($l.': '.$v, ': ');
                }
            }
            $detailsPreview = $parts !== [] ? implode(' · ', $parts) : '—';
        }

        return [
            'id' => $a->id,
            'name' => $a->name,
            'payment_method' => $a->payment_method,
            'account_type' => $typeName,
            'account_sub_type' => $subName,
            'account_number' => $a->account_number,
            'notes' => $a->notes,
            'opening_balance' => (string) $a->opening_balance,
            'account_details_preview' => $detailsPreview,
            'is_active' => $a->is_active,
            'created_by' => $a->creator?->name,
            'account_type_id' => $a->account_type_id,
            'account_details' => $a->account_details ?? [],
        ];
    }

    public function store(StorePaymentAccountRequest $request, Team $current_team): RedirectResponse
    {
        $data = $request->validated();
        $redirectTo = $data['redirect_to'];
        unset($data['redirect_to']);

        $isLedger = $request->boolean('is_ledger');
        unset($data['is_ledger']);

        $data['team_id'] = $current_team->id;
        $data['is_active'] = $data['is_active'] ?? true;

        if ($isLedger) {
            $data['payment_method'] = 'ledger';
            $data['bank_name'] = null;
            $data['opening_balance'] = round((float) ($data['opening_balance'] ?? 0), 4);
            if (empty($data['account_details'])) {
                $data['account_details'] = null;
            }
            $data['created_by'] = $request->user()?->id;
        } else {
            unset($data['account_type_id'], $data['opening_balance'], $data['account_details'], $data['created_by']);
        }

        PaymentAccount::query()->create($data);

        return $redirectTo === 'list'
            ? to_route('payment-accounts.index', ['current_team' => $current_team])->with('success', 'Account created.')
            : to_route('payment-settings.edit', ['current_team' => $current_team])->with('success', 'Payment account created.');
    }

    public function update(UpdatePaymentAccountRequest $request, Team $current_team, PaymentAccount $payment_account): RedirectResponse
    {
        $data = $request->validated();
        $redirectTo = $data['redirect_to'];
        unset($data['redirect_to']);

        if ($payment_account->payment_method === 'ledger') {
            $data['opening_balance'] = round((float) ($data['opening_balance'] ?? 0), 4);
            if (empty($data['account_details'])) {
                $data['account_details'] = null;
            }
            unset($data['payment_method'], $data['bank_name']);
        } else {
            unset($data['account_type_id'], $data['opening_balance'], $data['account_details']);
        }

        $payment_account->update($data);

        return $redirectTo === 'list'
            ? to_route('payment-accounts.index', ['current_team' => $current_team])->with('success', 'Account updated.')
            : to_route('payment-settings.edit', ['current_team' => $current_team])->with('success', 'Payment account updated.');
    }

    public function destroy(Request $request, Team $current_team, PaymentAccount $payment_account): RedirectResponse
    {
        $payment_account->delete();

        if ($request->query('redirect') === 'list') {
            return to_route('payment-accounts.index', ['current_team' => $current_team])
                ->with('success', 'Account deleted.');
        }

        return to_route('payment-settings.edit', ['current_team' => $current_team])
            ->with('success', 'Payment account deleted.');
    }
}
