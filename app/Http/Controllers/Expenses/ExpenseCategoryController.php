<?php

namespace App\Http\Controllers\Expenses;

use App\Http\Controllers\Controller;
use App\Http\Requests\Expenses\StoreExpenseCategoryRequest;
use App\Models\ExpenseCategory;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseCategoryController extends Controller
{
    public function index(Team $current_team): Response
    {
        $categories = ExpenseCategory::query()
            ->forTeam($current_team)
            ->with('parent')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (ExpenseCategory $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'code' => $c->code,
                'parent' => $c->parent ? [
                    'id' => $c->parent->id,
                    'name' => $c->parent->name,
                ] : null,
                'created_at' => $c->created_at?->toIso8601String(),
            ]);

        $parentOptions = ExpenseCategory::query()
            ->forTeam($current_team)
            ->roots()
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('expense-categories/Index', [
            'categories' => $categories,
            'parentCategories' => $parentOptions,
        ]);
    }

    public function store(StoreExpenseCategoryRequest $request, Team $current_team): RedirectResponse
    {
        ExpenseCategory::query()->create([
            'team_id' => $current_team->id,
            'parent_id' => $request->boolean('is_subcategory') ? $request->integer('parent_id') : null,
            'name' => $request->string('name')->toString(),
            'code' => $request->string('code')->toString(),
        ]);

        return to_route('expense-categories.index', ['current_team' => $current_team])
            ->with('success', 'Expense category saved.');
    }
}
