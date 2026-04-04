<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Team;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CustomerService
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
            ->with(['assignedUsers:id,name,email', 'contactPersons', 'customerGroup:id,name'])
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Customer>
     */
    public function filteredQuery(Team $team, array $filters): Builder
    {
        return Customer::query()
            ->forTeam($team)
            ->filter($filters);
    }

    /**
     * @return list<string>
     */
    public function sortableColumns(): array
    {
        return [
            'id',
            'customer_code',
            'party_role',
            'entity_type',
            'mobile',
            'email',
            'city',
            'opening_balance',
            'credit_limit',
            'created_at',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Team $team, array $data): Customer
    {
        return DB::transaction(function () use ($team, $data) {
            $assigned = $data['assigned_to_users'] ?? [];
            $persons = $data['contact_persons'] ?? [];
            unset($data['assigned_to_users'], $data['contact_persons']);

            if (empty($data['customer_code'])) {
                $data['customer_code'] = $this->nextCustomerCode($team);
            }

            $data['team_id'] = $team->id;

            /** @var Customer $customer */
            $customer = Customer::query()->create($data);

            $this->syncAssignedUsers($customer, $assigned);
            $this->syncContactPersons($customer, $persons);

            return $customer->load(['assignedUsers', 'contactPersons', 'customerGroup']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Customer $customer, array $data): Customer
    {
        return DB::transaction(function () use ($customer, $data) {
            $assigned = $data['assigned_to_users'] ?? null;
            $persons = $data['contact_persons'] ?? null;
            unset($data['assigned_to_users'], $data['contact_persons']);

            if (array_key_exists('customer_code', $data) && $data['customer_code'] === '') {
                $data['customer_code'] = $this->nextCustomerCode($customer->team);
            }

            $customer->update($data);

            if (is_array($assigned)) {
                $this->syncAssignedUsers($customer, $assigned);
            }

            if (is_array($persons)) {
                $this->syncContactPersons($customer, $persons);
            }

            return $customer->fresh(['assignedUsers', 'contactPersons', 'customerGroup']);
        });
    }

    public function delete(Customer $customer): void
    {
        $customer->delete();
    }

    public function nextCustomerCode(Team $team): string
    {
        $prefix = 'CUS-';
        $maxId = (int) Customer::query()->forTeam($team)->max('id');

        return $prefix.str_pad((string) ($maxId + 1), 5, '0', STR_PAD_LEFT);
    }

    /**
     * @param  list<int|string>  $userIds
     */
    protected function syncAssignedUsers(Customer $customer, array $userIds): void
    {
        $ids = collect($userIds)
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $customer->assignedUsers()->sync($ids);
    }

    /**
     * @param  list<array<string, mixed>>  $persons
     */
    protected function syncContactPersons(Customer $customer, array $persons): void
    {
        $customer->contactPersons()->delete();

        $team = $customer->team;

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

            $customer->contactPersons()->create($attrs);
        }
    }
}
