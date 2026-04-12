<?php

namespace App\Exports;

use App\Models\Team;
use App\Models\Unit;
use App\Services\UnitService;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UnitsExport implements FromQuery, WithHeadings, WithMapping
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        protected Team $team,
        protected array $filters,
        protected UnitService $unitService,
    ) {}

    /**
     * @return Builder<Unit>
     */
    public function query()
    {
        return $this->unitService
            ->filteredQuery($this->team, $this->filters)
            ->with('baseUnit:id,name,short_name');
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Short name',
            'Allow decimal',
            'Multiple of base',
            'Multiplier',
            'Base unit',
            'Created at',
        ];
    }

    /**
     * @param  Unit  $row
     * @return list<int|string|null>
     */
    public function map($row): array
    {
        $baseLabel = $row->baseUnit
            ? $row->baseUnit->name.' ('.$row->baseUnit->short_name.')'
            : null;

        return [
            $row->id,
            $row->name,
            $row->short_name,
            $row->allow_decimal ? 'Yes' : 'No',
            $row->is_multiple_of_base ? 'Yes' : 'No',
            $row->base_unit_multiplier,
            $baseLabel,
            $row->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
