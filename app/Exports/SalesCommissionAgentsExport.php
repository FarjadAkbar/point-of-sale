<?php

namespace App\Exports;

use App\Models\SalesCommissionAgent;
use App\Models\Team;
use App\Services\SalesCommissionAgentService;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SalesCommissionAgentsExport implements FromQuery, WithHeadings, WithMapping
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        protected Team $team,
        protected array $filters,
        protected SalesCommissionAgentService $salesCommissionAgentService,
    ) {}

    /**
     * @return \Illuminate\Database\Eloquent\Builder<SalesCommissionAgent>
     */
    public function query()
    {
        return $this->salesCommissionAgentService->filteredQuery($this->team, $this->filters);
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'ID',
            'Prefix',
            'First name',
            'Last name',
            'Email',
            'Contact',
            'Address',
            'Commission %',
            'Created at',
        ];
    }

    /**
     * @param  SalesCommissionAgent  $row
     * @return list<int|string|null>
     */
    public function map($row): array
    {
        return [
            $row->id,
            $row->prefix,
            $row->first_name,
            $row->last_name,
            $row->email,
            $row->contact_no,
            $row->address,
            (string) $row->cmmsn_percent,
            $row->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
