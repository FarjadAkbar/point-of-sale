<?php

namespace App\Http\Controllers\SalesCommissionAgents;

use App\Exports\SalesCommissionAgentsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\SalesCommissionAgents\SalesCommissionAgentIndexRequest;
use App\Http\Requests\SalesCommissionAgents\StoreSalesCommissionAgentRequest;
use App\Http\Requests\SalesCommissionAgents\UpdateSalesCommissionAgentRequest;
use App\Http\Resources\SalesCommissionAgentResource;
use App\Models\SalesCommissionAgent;
use App\Models\Team;
use App\Services\SalesCommissionAgentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SalesCommissionAgentController extends Controller
{
    public function __construct(
        protected SalesCommissionAgentService $salesCommissionAgentService,
    ) {}

    public function index(SalesCommissionAgentIndexRequest $request, Team $current_team): Response
    {
        $filters = $request->filters();
        $paginator = $this->salesCommissionAgentService->paginate($current_team, $filters);
        $paginator->through(fn (SalesCommissionAgent $a) => (new SalesCommissionAgentResource($a))->resolve());

        $editing = null;
        if ($editId = $request->query('edit')) {
            $editing = $current_team->salesCommissionAgents()->whereKey($editId)->first();
        }

        return Inertia::render('sales-commission-agents/Index', [
            'salesCommissionAgents' => $paginator,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'sort' => $filters['sort'] ?? 'created_at',
                'direction' => $filters['direction'] ?? 'desc',
                'per_page' => $filters['per_page'] ?? 15,
            ],
            'editingSalesCommissionAgent' => $editing
                ? (new SalesCommissionAgentResource($editing))->resolve()
                : null,
        ]);
    }

    public function store(StoreSalesCommissionAgentRequest $request, Team $current_team): RedirectResponse
    {
        $this->salesCommissionAgentService->create($current_team, $request->validated());

        return to_route('sales-commission-agents.index', ['current_team' => $current_team])
            ->with('success', 'Sales commission agent created.');
    }

    public function update(
        UpdateSalesCommissionAgentRequest $request,
        Team $current_team,
        SalesCommissionAgent $sales_commission_agent,
    ): RedirectResponse {
        $this->salesCommissionAgentService->update($sales_commission_agent, $request->validated());

        return to_route('sales-commission-agents.index', ['current_team' => $current_team])
            ->with('success', 'Sales commission agent updated.');
    }

    public function destroy(Request $request, Team $current_team, SalesCommissionAgent $sales_commission_agent): RedirectResponse
    {
        $this->salesCommissionAgentService->delete($sales_commission_agent);

        return to_route('sales-commission-agents.index', ['current_team' => $current_team])
            ->with('success', 'Sales commission agent deleted.');
    }

    public function exportFile(
        SalesCommissionAgentIndexRequest $request,
        Team $current_team,
        string $format,
    ): BinaryFileResponse|\Illuminate\Http\Response {
        $format = strtolower($format);
        abort_unless(in_array($format, ['csv', 'xlsx', 'pdf'], true), 404);

        $filters = $request->filters();
        $export = new SalesCommissionAgentsExport($current_team, $filters, $this->salesCommissionAgentService);

        $filename = 'sales-commission-agents-'.now()->format('Y-m-d-His');

        if ($format === 'csv') {
            return Excel::download($export, $filename.'.csv', \Maatwebsite\Excel\Excel::CSV);
        }

        if ($format === 'xlsx') {
            return Excel::download($export, $filename.'.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        }

        $rows = $this->salesCommissionAgentService->filteredQuery($current_team, $filters)
            ->orderBy('created_at', 'desc')
            ->get();

        $pdf = Pdf::loadView('exports.sales-commission-agents-pdf', [
            'team' => $current_team,
            'agents' => $rows,
            'generatedAt' => now()->toDateTimeString(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download($filename.'.pdf');
    }
}
