<?php

namespace App\Http\Controllers\Bookings;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bookings\BookingIndexRequest;
use App\Http\Requests\Bookings\StoreBookingRequest;
use App\Http\Requests\Bookings\UpdateBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\RestaurantTable;
use App\Models\Team;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function __construct(
        protected BookingService $bookingService,
    ) {}

    public function index(BookingIndexRequest $request, Team $current_team): Response
    {
        $filters = $request->filters();
        $paginator = $this->bookingService->paginate($current_team, $filters);
        $paginator->through(fn (Booking $b) => (new BookingResource($b))->resolve());

        $todayRows = $this->bookingService->todaysForTeam($current_team);
        $todayResolved = $todayRows->map(fn (Booking $b) => (new BookingResource($b))->resolve())->values()->all();
        $todayTotal = count($todayResolved);
        $todaysPaginator = new LengthAwarePaginator(
            $todayResolved,
            $todayTotal,
            max(1, $todayTotal),
            1,
            ['path' => $request->url(), 'pageName' => 'today_page']
        );

        $editing = null;
        if ($editId = $request->query('edit')) {
            $editing = $current_team->bookings()->whereKey($editId)->first();
            $editing?->load([
                'customer:id,entity_type,business_name,first_name,middle_name,last_name,customer_code,mobile',
                'businessLocation:id,name',
                'restaurantTable:id,name',
                'correspondent:id,name',
                'serviceStaff:id,name',
            ]);
        }

        $businessLocations = $current_team->businessLocations()
            ->orderBy('name')
            ->get(['id', 'name']);

        $customerOptions = Customer::query()
            ->forTeam($current_team)
            ->whereIn('party_role', ['customer', 'both'])
            ->orderBy('customer_code')
            ->limit(500)
            ->get(['id', 'entity_type', 'business_name', 'first_name', 'middle_name', 'last_name', 'customer_code', 'mobile'])
            ->map(fn (Customer $c) => [
                'id' => $c->id,
                'label' => $c->display_name.($c->customer_code ? ' ('.$c->customer_code.')' : ''),
            ])
            ->values();

        $teamMembers = $current_team->members()
            ->orderBy('users.name')
            ->get(['users.id', 'users.name'])
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])
            ->values();

        $restaurantTables = RestaurantTable::query()
            ->forTeam($current_team)
            ->orderBy('business_location_id')
            ->orderBy('name')
            ->get(['id', 'business_location_id', 'name'])
            ->map(fn (RestaurantTable $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'business_location_id' => $t->business_location_id,
            ])
            ->values();

        return Inertia::render('booking/Index', [
            'bookings' => $paginator,
            'todaysBookings' => $todaysPaginator,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'sort' => $filters['sort'] ?? 'starts_at',
                'direction' => $filters['direction'] ?? 'desc',
                'per_page' => $filters['per_page'] ?? 15,
                'status' => $filters['status'] ?? '',
            ],
            'businessLocations' => $businessLocations,
            'customerOptions' => $customerOptions,
            'teamMembers' => $teamMembers,
            'restaurantTables' => $restaurantTables,
            'editingBooking' => $editing ? (new BookingResource($editing))->resolve() : null,
        ]);
    }

    public function store(StoreBookingRequest $request, Team $current_team): RedirectResponse
    {
        $data = $request->validated();
        $data['status'] = BookingStatus::Booked;

        $this->bookingService->create($current_team, $data);

        return to_route('booking.index', ['current_team' => $current_team])
            ->with('success', 'Booking created.');
    }

    public function update(UpdateBookingRequest $request, Team $current_team, Booking $booking): RedirectResponse
    {
        $this->bookingService->update($booking, $request->validated());

        return to_route('booking.index', ['current_team' => $current_team])
            ->with('success', 'Booking updated.');
    }

    public function destroy(Request $request, Team $current_team, Booking $booking): RedirectResponse
    {
        $this->bookingService->delete($booking);

        return to_route('booking.index', ['current_team' => $current_team])
            ->with('success', 'Booking deleted.');
    }
}
