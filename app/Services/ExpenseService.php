<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\TaxRate;
use App\Models\Team;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function store(Team $team, array $data, ?UploadedFile $document, ?int $userId): Expense
    {
        return DB::transaction(function () use ($team, $data, $document, $userId) {
            $documentPath = null;
            if ($document) {
                $documentPath = $document->store('expenses/documents', 'public');
            }

            $finalTotal = round((float) $data['final_total'], 4);
            $taxAmount = $this->computeTaxInclusive(
                $team,
                isset($data['tax_rate_id']) ? (int) $data['tax_rate_id'] : null,
                $finalTotal,
            );

            $refNo = $data['ref_no'] ?? null;
            if ($refNo === null || $refNo === '') {
                $next = Expense::query()->where('team_id', $team->id)->count() + 1;
                $refNo = 'EXP-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
            }

            $expense = Expense::query()->create([
                'team_id' => $team->id,
                'business_location_id' => (int) $data['business_location_id'],
                'expense_category_id' => $data['expense_category_id'] ?? null,
                'ref_no' => $refNo,
                'transaction_date' => $data['transaction_date'],
                'expense_for_user_id' => $data['expense_for_user_id'] ?? null,
                'contact_id' => $data['contact_id'] ?? null,
                'document_path' => $documentPath,
                'tax_rate_id' => $data['tax_rate_id'] ?? null,
                'tax_amount' => $taxAmount,
                'final_total' => $finalTotal,
                'additional_notes' => $data['additional_notes'] ?? null,
                'is_refund' => (bool) ($data['is_refund'] ?? false),
                'is_recurring' => (bool) ($data['is_recurring'] ?? false),
                'recur_interval' => $data['recur_interval'] ?? null,
                'recur_interval_type' => $data['recur_interval_type'] ?? null,
                'recur_repetitions' => $data['recur_repetitions'] ?? null,
                'subscription_repeat_on' => $data['subscription_repeat_on'] ?? null,
                'created_by' => $userId,
            ]);

            foreach ($data['payments'] as $row) {
                $expense->payments()->create([
                    'amount' => round((float) $row['amount'], 4),
                    'paid_on' => $row['paid_on'],
                    'method' => $row['method'],
                    'payment_account_id' => $row['payment_account_id'] ?? null,
                    'note' => $row['note'] ?? null,
                    'bank_account_number' => $row['bank_account_number'] ?? null,
                ]);
            }

            return $expense;
        });
    }

    public function computeTaxInclusive(Team $team, ?int $taxRateId, float $grandTotal): float
    {
        if (! $taxRateId || $grandTotal <= 0) {
            return 0.0;
        }

        $rate = TaxRate::query()->forTeam($team)->whereKey($taxRateId)->first();
        if (! $rate) {
            return 0.0;
        }

        $r = (float) $rate->amount / 100;
        if ($r <= 0) {
            return 0.0;
        }

        $subtotal = $grandTotal / (1 + $r);

        return round($grandTotal - $subtotal, 4);
    }
}
