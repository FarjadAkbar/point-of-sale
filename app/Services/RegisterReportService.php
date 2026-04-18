<?php

namespace App\Services;

use App\Models\CashRegisterSession;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RegisterReportService
{
    /**
     * @return array{rows: list<array<string, mixed>>, footer: array<string, string>}
     */
    public function build(
        Team $team,
        Carbon $start,
        Carbon $end,
        ?int $userId,
        ?string $status,
    ): array {
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->endOfDay();

        $q = CashRegisterSession::query()
            ->forTeam($team)
            ->whereBetween('opened_at', [$start, $end])
            ->with(['businessLocation', 'user'])
            ->orderByDesc('opened_at');

        if ($userId !== null) {
            $q->where('user_id', $userId);
        }
        if ($status === 'open') {
            $q->where('status', 'open');
        } elseif ($status === 'close') {
            $q->where('status', 'close');
        }

        $sessions = $q->get();

        $rows = $sessions->map(fn (CashRegisterSession $s) => $this->mapRow($team, $s))->all();

        $footer = $this->sumFooter($sessions);

        return [
            'rows' => $rows,
            'footer' => $footer,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapRow(Team $team, CashRegisterSession $s): array
    {
        return [
            'id' => $s->id,
            'open_time' => $s->opened_at?->toIso8601String() ?? '',
            'close_time' => $s->closed_at?->toIso8601String() ?? '',
            'opening_cash' => $this->nf((float) $s->opening_cash),
            'location' => $s->businessLocation?->name ?? '—',
            'user' => $s->user?->name ?? '—',
            'total_card_slips' => $this->nf((float) $s->total_card_slips),
            'total_cheque' => $this->nf((float) $s->total_cheque),
            'total_cash' => $this->nf((float) $s->total_cash),
            'total_bank_transfer' => $this->nf((float) $s->total_bank_transfer),
            'total_advance_payment' => $this->nf((float) $s->total_advance_payment),
            'custom_pay_1' => $this->nf((float) $s->custom_pay_1),
            'custom_pay_2' => $this->nf((float) $s->custom_pay_2),
            'custom_pay_3' => $this->nf((float) $s->custom_pay_3),
            'custom_pay_4' => $this->nf((float) $s->custom_pay_4),
            'custom_pay_5' => $this->nf((float) $s->custom_pay_5),
            'custom_pay_6' => $this->nf((float) $s->custom_pay_6),
            'custom_pay_7' => $this->nf((float) $s->custom_pay_7),
            'other_payments' => $this->nf((float) $s->other_payments),
            'total' => $this->nf((float) $s->total),
            'action_url' => route('pos.index', ['current_team' => $team->slug]),
        ];
    }

    /**
     * @param  Collection<int, CashRegisterSession>  $sessions
     * @return array<string, string>
     */
    protected function sumFooter(Collection $sessions): array
    {
        $sum = static fn (string $col) => $sessions->sum(fn (CashRegisterSession $s) => (float) $s->{$col});

        return [
            'total_card_slips' => $this->nf($sum('total_card_slips')),
            'total_cheque' => $this->nf($sum('total_cheque')),
            'total_cash' => $this->nf($sum('total_cash')),
            'total_bank_transfer' => $this->nf($sum('total_bank_transfer')),
            'total_advance_payment' => $this->nf($sum('total_advance_payment')),
            'custom_pay_1' => $this->nf($sum('custom_pay_1')),
            'custom_pay_2' => $this->nf($sum('custom_pay_2')),
            'custom_pay_3' => $this->nf($sum('custom_pay_3')),
            'custom_pay_4' => $this->nf($sum('custom_pay_4')),
            'custom_pay_5' => $this->nf($sum('custom_pay_5')),
            'custom_pay_6' => $this->nf($sum('custom_pay_6')),
            'custom_pay_7' => $this->nf($sum('custom_pay_7')),
            'other_payments' => $this->nf($sum('other_payments')),
            'total' => $this->nf($sum('total')),
        ];
    }

    protected function nf(float $v): string
    {
        return number_format(round($v, 4), 4, '.', '');
    }
}
