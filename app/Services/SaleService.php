<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleActivity;
use App\Models\TaxRate;
use App\Models\Team;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function __construct(
        protected ProductStockService $productStockService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Team $team, array $data, ?UploadedFile $document): Sale
    {
        return DB::transaction(function () use ($team, $data, $document) {
            $documentPath = null;
            if ($document) {
                $documentPath = $document->store('sales/documents', 'public');
            }

            $linesComputed = $this->computeLines($data['lines'] ?? []);
            $linesTotal = 0.0;
            foreach ($linesComputed as $line) {
                $linesTotal += $line['line_total'];
            }
            $linesTotal = round($linesTotal, 4);

            $afterDiscount = $this->applyHeaderDiscount(
                $linesTotal,
                (string) ($data['discount_type'] ?? 'none'),
                (float) ($data['discount_amount'] ?? 0),
            );

            $saleTax = $this->computeSaleTax(
                $team,
                isset($data['tax_rate_id']) ? (int) $data['tax_rate_id'] : null,
                $afterDiscount,
            );

            $shipping = round((float) ($data['shipping_charges'] ?? 0), 4);
            $additional = $this->sumAdditionalExpenses($data['additional_expenses'] ?? []);
            $finalTotal = round($afterDiscount + $saleTax + $shipping + $additional, 4);

            $sale = Sale::query()->create([
                'team_id' => $team->id,
                'created_by' => $this->resolveSaleCreatedBy($team, $data),
                'customer_id' => (int) $data['customer_id'],
                'business_location_id' => (int) $data['business_location_id'],
                'selling_price_group_id' => $data['selling_price_group_id'] ?? null,
                'restaurant_table_id' => $data['restaurant_table_id'] ?? null,
                'invoice_no' => $data['invoice_no'] ?? null,
                'transaction_date' => $data['transaction_date'],
                'status' => $data['status'],
                'pay_term_number' => $data['pay_term_number'] ?? null,
                'pay_term_type' => $data['pay_term_type'] ?? null,
                'discount_type' => $data['discount_type'] ?? 'none',
                'discount_amount' => round((float) ($data['discount_amount'] ?? 0), 4),
                'tax_rate_id' => $data['tax_rate_id'] ?? null,
                'sale_tax_amount' => $saleTax,
                'shipping_details' => $data['shipping_details'] ?? null,
                'shipping_charges' => $shipping,
                'shipping_address' => $data['shipping_address'] ?? null,
                'additional_expenses' => $data['additional_expenses'] ?? null,
                'sale_note' => $data['sale_note'] ?? null,
                'document_path' => $documentPath,
                'lines_total' => $linesTotal,
                'final_total' => $finalTotal,
            ]);

            foreach ($linesComputed as $line) {
                $sale->lines()->create($line);
            }

            if (($data['status'] ?? '') !== 'quotation' && isset($data['payment']) && is_array($data['payment'])) {
                $sale->payments()->create([
                    'amount' => round((float) $data['payment']['amount'], 4),
                    'paid_on' => $data['payment']['paid_on'],
                    'method' => $data['payment']['method'],
                    'payment_account_id' => $data['payment']['payment_account_id'] ?? null,
                    'note' => $data['payment']['note'] ?? null,
                    'bank_account_number' => $data['payment']['bank_account_number'] ?? null,
                ]);
            }

            $sale->load(['customer', 'businessLocation', 'lines.product']);
            $this->productStockService->applySaleFinal($sale);

            return $sale;
        });
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     * @return list<array<string, mixed>>
     */
    public function computeLines(array $lines): array
    {
        $out = [];

        foreach ($lines as $row) {
            $qty = (float) $row['quantity'];
            $u0 = (float) $row['unit_price_before_discount'];
            $dp = min(100, max(0, (float) ($row['discount_percent'] ?? 0)));
            $uExc = round($u0 * (1 - $dp / 100), 4);
            $pt = max(0, (float) ($row['product_tax_percent'] ?? 0));
            $lineSubExc = round($qty * $uExc, 4);
            $lineTax = round($lineSubExc * ($pt / 100), 4);
            $lineTotal = round($lineSubExc + $lineTax, 4);

            $out[] = [
                'product_id' => (int) $row['product_id'],
                'quantity' => $qty,
                'unit_price_before_discount' => $u0,
                'discount_percent' => $dp,
                'unit_price_exc_tax' => $uExc,
                'product_tax_percent' => $pt,
                'line_subtotal_exc_tax' => $lineSubExc,
                'line_tax_amount' => $lineTax,
                'line_total' => $lineTotal,
            ];
        }

        return $out;
    }

    public function applyHeaderDiscount(float $linesTotal, string $discountType, float $discountAmount): float
    {
        if ($discountType === 'fixed') {
            return max(0, round($linesTotal - $discountAmount, 4));
        }

        if ($discountType === 'percentage') {
            $pct = min(100, max(0, $discountAmount));

            return round($linesTotal * (1 - $pct / 100), 4);
        }

        return round($linesTotal, 4);
    }

    public function computeSaleTax(Team $team, ?int $taxRateId, float $baseAmount): float
    {
        if (! $taxRateId || $baseAmount <= 0) {
            return 0.0;
        }

        $rate = TaxRate::query()->forTeam($team)->whereKey($taxRateId)->first();
        if (! $rate) {
            return 0.0;
        }

        return round($baseAmount * ((float) $rate->amount / 100), 4);
    }

    /**
     * @param  list<array{name?: string, amount?: float|int|string}>|null  $rows
     */
    public function sumAdditionalExpenses(?array $rows): float
    {
        if (! $rows) {
            return 0.0;
        }

        $sum = 0.0;
        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $sum += (float) ($row['amount'] ?? 0);
        }

        return round($sum, 4);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function resolveSaleCreatedBy(Team $team, array $data): int
    {
        $staffId = isset($data['service_staff_id']) ? (int) $data['service_staff_id'] : 0;
        if ($staffId > 0 && $team->members()->where('users.id', $staffId)->exists()) {
            return $staffId;
        }

        return (int) auth()->id();
    }

    public function deleteSale(Team $team, Sale $sale): void
    {
        if ($sale->team_id !== $team->id) {
            abort(403);
        }

        if ($sale->saleReturns()->exists()) {
            throw ValidationException::withMessages([
                'sale' => ['This sale has recorded returns and cannot be deleted.'],
            ]);
        }

        DB::transaction(function () use ($sale) {
            if ($sale->status === 'final') {
                $this->productStockService->revertSaleFinal($sale);
            }

            $sale->payments()->delete();
            $sale->lines()->delete();
            $sale->activities()->delete();

            if ($sale->document_path) {
                Storage::disk('public')->delete($sale->document_path);
            }

            if ($sale->shipping_document_path) {
                Storage::disk('public')->delete($sale->shipping_document_path);
            }

            $sale->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateShipping(Team $team, Sale $sale, array $data, ?UploadedFile $shippingDocument): void
    {
        if ($sale->team_id !== $team->id) {
            abort(403);
        }

        $path = $sale->shipping_document_path;
        if ($shippingDocument) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            $path = $shippingDocument->store('sales/shipping', 'public');
        }

        $sale->fill([
            'shipping_details' => $data['shipping_details'] ?? $sale->shipping_details,
            'shipping_charges' => isset($data['shipping_charges']) ? round((float) $data['shipping_charges'], 4) : $sale->shipping_charges,
            'shipping_address' => $data['shipping_address'] ?? $sale->shipping_address,
            'shipping_status' => $data['shipping_status'] ?? $sale->shipping_status,
            'delivered_to' => $data['delivered_to'] ?? $sale->delivered_to,
            'delivery_person' => $data['delivery_person'] ?? $sale->delivery_person,
            'shipping_customer_note' => $data['shipping_customer_note'] ?? $sale->shipping_customer_note,
        ]);

        if ($shippingDocument) {
            $sale->shipping_document_path = $path;
        }

        $sale->save();

        SaleActivity::query()->create([
            'sale_id' => $sale->id,
            'user_id' => auth()->id(),
            'action' => 'shipping_updated',
            'note' => null,
        ]);
    }
}
