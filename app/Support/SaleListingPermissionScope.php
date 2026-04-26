<?php

namespace App\Support;

use App\Models\Sale;
use App\Models\SalesCommissionAgent;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

final class SaleListingPermissionScope
{
    private const PAID_SUBQUERY = '(select coalesce(sum(sale_payments.amount), 0) from sale_payments where sale_payments.sale_id = sales.id)';

    /**
     * Restrict a sales query for POS users (list / export patterns).
     *
     * @param  Builder<Sale>  $query
     */
    public static function apply(Builder $query, ?User $user, Team $team, bool $applyPaymentFilters = true): void
    {
        if (! $user || $user->ownsTeam($team)) {
            return;
        }

        self::applyOwnershipScope($query, $user, $team);
        if ($applyPaymentFilters) {
            self::applyPaymentScopeFilters($query, $user, $team);
        }
    }

    public static function canView(?User $user, Team $team, Sale $sale): bool
    {
        if (! $user) {
            return false;
        }

        if ($sale->team_id !== $team->id) {
            return false;
        }

        if ($user->ownsTeam($team)) {
            return true;
        }

        if ($user->hasPosPermission($team, 'direct_sell.view')) {
            return self::saleMatchesPaymentFiltersForStatus($user, $team, $sale);
        }

        if ($user->hasPosPermission($team, 'view_own_sell_only')) {
            if ((int) $sale->created_by !== (int) $user->id) {
                return false;
            }

            return self::saleMatchesPaymentFiltersForStatus($user, $team, $sale);
        }

        if ($user->hasPosPermission($team, 'view_commission_agent_sell')) {
            return self::saleVisibleForCommissionAgent($user, $team, $sale)
                && self::saleMatchesPaymentFiltersForStatus($user, $team, $sale);
        }

        return false;
    }

    /**
     * @param  Builder<Sale>  $query
     */
    public static function applyDraft(Builder $query, ?User $user, Team $team): void
    {
        if (! $user || $user->ownsTeam($team)) {
            return;
        }

        if ($user->hasPosPermission($team, 'draft.view_all')) {
            return;
        }

        if ($user->hasPosPermission($team, 'draft.view_own')) {
            $query->where($query->qualifyColumn('created_by'), $user->id);

            return;
        }

        $query->whereRaw('1 = 0');
    }

    public static function canViewDraft(?User $user, Team $team, Sale $sale): bool
    {
        if (! $user || $sale->team_id !== $team->id || $sale->status !== 'draft') {
            return false;
        }

        if ($user->ownsTeam($team) || $user->hasPosPermission($team, 'draft.view_all')) {
            return true;
        }

        if ($user->hasPosPermission($team, 'draft.view_own')) {
            return (int) $sale->created_by === (int) $user->id;
        }

        return false;
    }

    /**
     * @param  Builder<Sale>  $query
     */
    public static function applyQuotation(Builder $query, ?User $user, Team $team): void
    {
        if (! $user || $user->ownsTeam($team)) {
            return;
        }

        if ($user->hasPosPermission($team, 'quotation.view_all')) {
            return;
        }

        if ($user->hasPosPermission($team, 'quotation.view_own')) {
            $query->where($query->qualifyColumn('created_by'), $user->id);

            return;
        }

        $query->whereRaw('1 = 0');
    }

    public static function canViewQuotation(?User $user, Team $team, Sale $sale): bool
    {
        if (! $user || $sale->team_id !== $team->id || $sale->status !== 'quotation') {
            return false;
        }

        if ($user->ownsTeam($team) || $user->hasPosPermission($team, 'quotation.view_all')) {
            return true;
        }

        if ($user->hasPosPermission($team, 'quotation.view_own')) {
            return (int) $sale->created_by === (int) $user->id;
        }

        return false;
    }

    public static function canAccessSellReturn(?User $user, Team $team, Sale $sale): bool
    {
        if (! $user || $sale->team_id !== $team->id) {
            return false;
        }

        if ($user->ownsTeam($team)) {
            return true;
        }

        if ($user->hasPosPermission($team, 'access_sell_return')) {
            return self::canView($user, $team, $sale);
        }

        if ($user->hasPosPermission($team, 'access_own_sell_return')) {
            return (int) $sale->created_by === (int) $user->id;
        }

        return false;
    }

    /**
     * @param  Builder<Sale>  $query
     */
    private static function applyOwnershipScope(Builder $query, User $user, Team $team): void
    {
        if ($user->hasPosPermission($team, 'direct_sell.view')) {
            return;
        }

        if ($user->hasPosPermission($team, 'view_own_sell_only')) {
            $query->where($query->qualifyColumn('created_by'), $user->id);

            return;
        }

        if ($user->hasPosPermission($team, 'view_commission_agent_sell')) {
            $agentIds = SalesCommissionAgent::query()
                ->forTeam($team)
                ->where('email', $user->email)
                ->pluck('id');

            if ($agentIds->isEmpty()) {
                $query->whereRaw('1 = 0');

                return;
            }

            $query->whereIn($query->qualifyColumn('sales_commission_agent_id'), $agentIds);
        }
    }

    /**
     * @param  Builder<Sale>  $query
     */
    private static function applyPaymentScopeFilters(Builder $query, User $user, Team $team): void
    {
        $conds = self::paymentFilterSqlClauses($user, $team);
        if ($conds === []) {
            return;
        }

        $query->whereRaw('('.implode(' or ', $conds).')');
    }

    /**
     * @return list<string>
     */
    private static function paymentFilterSqlClauses(User $user, Team $team): array
    {
        $p = self::PAID_SUBQUERY;
        $conds = [];

        if ($user->hasPosPermission($team, 'view_paid_sells_only')) {
            $conds[] = "({$p} >= sales.final_total)";
        }

        if ($user->hasPosPermission($team, 'view_due_sells_only')) {
            $conds[] = "({$p} = 0)";
        }

        if ($user->hasPosPermission($team, 'view_partial_sells_only')) {
            $conds[] = "({$p} > 0 and {$p} < sales.final_total)";
        }

        if ($user->hasPosPermission($team, 'view_overdue_sells_only')) {
            $conds[] = self::overdueSqlClause($p);
        }

        return $conds;
    }

    private static function overdueSqlClause(string $paidSubquery): string
    {
        $base = "({$paidSubquery} < sales.final_total and sales.pay_term_number is not null and sales.pay_term_type is not null and (";

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return $base.
                "(sales.pay_term_type = 'days' and datetime(sales.transaction_date, '+' || sales.pay_term_number || ' days') < datetime('now')) or ".
                "(sales.pay_term_type = 'months' and datetime(sales.transaction_date, '+' || sales.pay_term_number || ' months') < datetime('now'))".
                '))';
        }

        return $base.
            "(sales.pay_term_type = 'days' and date_add(sales.transaction_date, interval sales.pay_term_number day) < now()) or ".
            "(sales.pay_term_type = 'months' and date_add(sales.transaction_date, interval sales.pay_term_number month) < now())".
            '))';
    }

    private static function saleMatchesPaymentFiltersForStatus(?User $user, Team $team, Sale $sale): bool
    {
        if (! $user || $user->ownsTeam($team)) {
            return true;
        }

        if (in_array($sale->status, ['draft', 'quotation'], true)) {
            return true;
        }

        $conds = self::paymentFilterSqlClauses($user, $team);
        if ($conds === []) {
            return true;
        }

        return Sale::query()
            ->whereKey($sale->id)
            ->whereRaw('('.implode(' or ', $conds).')')
            ->exists();
    }

    private static function saleVisibleForCommissionAgent(User $user, Team $team, Sale $sale): bool
    {
        if (! $sale->sales_commission_agent_id) {
            return false;
        }

        return SalesCommissionAgent::query()
            ->forTeam($team)
            ->whereKey($sale->sales_commission_agent_id)
            ->where('email', $user->email)
            ->exists();
    }
}
