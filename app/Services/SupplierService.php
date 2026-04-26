<?php

namespace App\Services;

use App\Models\Supplier;
use App\Models\Team;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class SupplierService
{
    public function __construct(
        protected ContactPersonUserService $contactPersonUserService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(Team $team, array $filters): LengthAwarePaginator
    {
        $query = $this->filteredQuery($team, $filters);

        $sort = $filters['sort'] ?? 'created_at';
        $direction = strtolower((string) ($filters['direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        if ($sort === 'display_name') {
            $query->orderBy('business_name', $direction)
                ->orderBy('first_name', $direction)
                ->orderBy('last_name', $direction);
        } else {
            $query->orderBy(
                in_array($sort, $this->sortableColumns(), true) ? $sort : 'created_at',
                $direction
            );
        }

        $perPage = (int) ($filters['per_page'] ?? 15);
        $perPage = min(100, max(10, $perPage));

        return $query
            ->with(['assignedUsers:id,name,email', 'contactPersons'])
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Supplier>
     */
    public function filteredQuery(Team $team, array $filters): Builder
    {
        return Supplier::query()
            ->forTeam($team)
            ->when(
                isset($filters['assigned_user_id']) && is_numeric($filters['assigned_user_id']),
                fn (Builder $query) => $query->whereHas(
                    'assignedUsers',
                    fn (Builder $userQuery) => $userQuery->where('users.id', (int) $filters['assigned_user_id'])
                )
            )
            ->filter($filters);
    }

    /**
     * @return list<string>
     */
    public function sortableColumns(): array
    {
        return [
            'id',
            'supplier_code',
            'mobile',
            'email',
            'city',
            'opening_balance',
            'created_at',
            'contact_type',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Team $team, array $data): Supplier
    {
        return DB::transaction(function () use ($team, $data) {
            $assigned = $data['assigned_to_users'] ?? [];
            $persons = $data['contact_persons'] ?? [];
            unset($data['assigned_to_users'], $data['contact_persons']);

            if (empty($data['supplier_code'])) {
                $data['supplier_code'] = $this->nextSupplierCode($team);
            }

            $data['team_id'] = $team->id;

            /** @var Supplier $supplier */
            $supplier = Supplier::query()->create($data);

            $this->syncAssignedUsers($supplier, $assigned);
            $this->syncContactPersons($supplier, $persons);

            return $supplier->load(['assignedUsers', 'contactPersons']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Supplier $supplier, array $data): Supplier
    {
        return DB::transaction(function () use ($supplier, $data) {
            $assigned = $data['assigned_to_users'] ?? null;
            $persons = $data['contact_persons'] ?? null;
            unset($data['assigned_to_users'], $data['contact_persons']);

            if (array_key_exists('supplier_code', $data) && $data['supplier_code'] === '') {
                $data['supplier_code'] = $this->nextSupplierCode($supplier->team);
            }

            $supplier->update($data);

            if (is_array($assigned)) {
                $this->syncAssignedUsers($supplier, $assigned);
            }

            if (is_array($persons)) {
                $this->syncContactPersons($supplier, $persons);
            }

            return $supplier->fresh(['assignedUsers', 'contactPersons']);
        });
    }

    public function delete(Supplier $supplier): void
    {
        $supplier->delete();
    }

    public function nextSupplierCode(Team $team): string
    {
        $prefix = 'SUP-';
        $maxId = (int) Supplier::query()->forTeam($team)->max('id');

        return $prefix.str_pad((string) ($maxId + 1), 5, '0', STR_PAD_LEFT);
    }

    /**
     * @param  list<int|string>  $userIds
     */
    protected function syncAssignedUsers(Supplier $supplier, array $userIds): void
    {
        $ids = collect($userIds)
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $supplier->assignedUsers()->sync($ids);
    }

    /**
     * @param  list<array<string, mixed>>  $persons
     */
    protected function syncContactPersons(Supplier $supplier, array $persons): void
    {
        $supplier->contactPersons()->delete();

        $team = $supplier->team;

        foreach (array_values($persons) as $index => $row) {
            if (empty($row['first_name'])) {
                continue;
            }

            $userId = $this->contactPersonUserService->syncLinkedUser($team, $row);

            $attrs = [
                'user_id' => $userId,
                'surname' => $row['surname'] ?? null,
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'] ?? null,
                'email' => $row['email'] ?? null,
                'contact_no' => $row['contact_no'] ?? null,
                'alt_number' => $row['alt_number'] ?? null,
                'family_number' => $row['family_number'] ?? null,
                'crm_department' => $row['crm_department'] ?? null,
                'crm_designation' => $row['crm_designation'] ?? null,
                'cmmsn_percent' => isset($row['cmmsn_percent']) && $row['cmmsn_percent'] !== ''
                    ? (float) $row['cmmsn_percent']
                    : null,
                'allow_login' => filter_var($row['allow_login'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'username' => $row['username'] ?? null,
                'password' => null,
                'is_active' => filter_var($row['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'position' => $index,
            ];

            $supplier->contactPersons()->create($attrs);
        }
    }
}
