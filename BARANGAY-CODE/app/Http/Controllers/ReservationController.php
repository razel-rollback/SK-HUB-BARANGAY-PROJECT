<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Service;
use App\Models\ClosurePeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReservationController extends Controller
{
    public function showTerms()
    {
        return view('resident.reservation_terms');
    }
    
    public function acceptTerms(Request $request)
    {
        $request->validate([
            'accept_terms' => ['required', 'accepted'],
        ]);
        
        // Set session variable to indicate terms were accepted
        session(['terms_accepted' => true]);
        
        return redirect()->route('resident.reservation.add');
    }
    
    public function create()
    {
        // Check if the user has accepted the terms and conditions
        if (!session('terms_accepted')) {
            return redirect()->route('resident.reservation.terms');
        }
        
        $user = Auth::user();
        
        // Check if user is suspended
        if ($user->isSuspended()) {
            $daysRemaining = $user->suspension_days_remaining;
            $message = "Your account is suspended for $daysRemaining days due to 3 no-show or cancellation violations. You cannot make reservations until the suspension period ends.";
            return redirect()->route('resident.dashboard')->withErrors(['suspension' => $message]);
        }
        
        $userId = $user->id;
        [$onCooldown, $cooldownUntil] = $this->cooldownState($userId);
        return view('resident.make_reservation_wizard', compact('onCooldown', 'cooldownUntil'));
    }
    public function residentIndex(Request $request)
    {
        $now = now();

        // Auto-complete: mark past pending reservations as completed (not cancelled)
        Reservation::where('user_id', Auth::id())
            ->where('status', 'pending')
            ->where(function ($q) use ($now) {
                $q->whereDate('reservation_date', '<', $now->toDateString())
                  ->orWhere(function ($qq) use ($now) {
                      $qq->whereDate('reservation_date', $now->toDateString())
                         ->where('end_time', '<', $now->format('H:i:s'));
                  });
            })
            ->update(['status' => 'completed']);

        // Default to latest created bookings first
        $allowedSorts = ['reservation_date', 'reference_no', 'status', 'created_at'];
        $sort = in_array($request->get('sort'), $allowedSorts) ? $request->get('sort') : 'created_at';
        $direction = $request->get('direction') === 'asc' ? 'asc' : 'desc';

        $items = Reservation::with('service')
            ->where('user_id', Auth::id())
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->get('q');
                // Group search conditions to ensure they are scoped under user_id
                $q->where(function ($w) use ($term) {
                    $w->where('reference_no', 'like', "%$term%")
                      ->orWhere('status', 'like', "%$term%")
                      ->orWhereDate('reservation_date', $term)
                      ->orWhere('start_time', 'like', "%$term%")
                      ->orWhere('end_time', 'like', "%$term%")
                      ->orWhereHas('service', fn($sq) => $sq->where('name', 'like', "%$term%"));
                });
            })
            ->when($request->filled('date'), fn($q) => $q->whereDate('reservation_date', $request->date))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->orderBy($sort, $direction)
            // Tiebreaker: for same date/time, show later start times first when sorting desc
            ->orderBy('start_time', $direction === 'asc' ? 'asc' : 'desc')
            ->paginate(6)
            ->withQueryString();

        return view('resident.resident_reservation', compact('items', 'sort', 'direction'));
    }

    public function index(Request $request)
    {
        $requestedSort = $request->get('sort', 'reservation_date');
        $direction = strtolower($request->get('direction', 'desc'));
        $direction = in_array($direction, ['asc', 'desc'], true) ? $direction : 'desc';
        $tab = $request->get('tab', 'all'); // Default to 'all' tab

        $sortable = [
            'id' => 'reservations.id',
            'reference_no' => 'reservations.reference_no',
            'resident' => 'resident_name',
            'service' => 'services.name',
            'reservation_date' => 'reservations.reservation_date',
            'start_time' => 'reservations.start_time',
            'end_time' => 'reservations.end_time',
        ];

        $reservations = Reservation::query()
            ->leftJoin('users', 'users.id', '=', 'reservations.user_id')
            ->leftJoin('services', 'services.id', '=', 'reservations.service_id')
            ->select('reservations.*')
            ->selectRaw("TRIM(CONCAT(COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, ''))) AS resident_name")
            ->with(['user', 'service'])
            ->when($tab === 'today', function($query) {
                // Filter for today's reservations
                return $query->whereDate('reservations.reservation_date', now()->toDateString());
            })
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = trim($request->get('q'));
                $like = "%{$term}%";
                $isDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $term) === 1;
                $q->where(function($w) use ($like, $term, $isDate) {
                    $w->where('reservations.reference_no', 'like', $like)
                      ->orWhere('reservations.status', 'like', $like)
                      ->orWhere('reservations.start_time', 'like', $like)
                      ->orWhere('reservations.end_time', 'like', $like)
                      ->orWhere('services.name', 'like', $like)
                      ->orWhereRaw("TRIM(CONCAT(COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, ''))) LIKE ?", [$like]);

                    if (ctype_digit($term)) {
                        $w->orWhere('reservations.id', (int) $term);
                    }

                    if ($isDate) {
                        $w->orWhereDate('reservations.reservation_date', $term);
                    }
                });
            })
            ->when($request->filled('date'), fn($q) => $q->whereDate('reservations.reservation_date', $request->date))
            ->when($request->filled('status'), fn($q) => $q->where('reservations.status', $request->status));

        if ($requestedSort === 'resident') {
            $reservations->orderBy('resident_name', $direction);
        } elseif (array_key_exists($requestedSort, $sortable)) {
            $reservations->orderBy($sortable[$requestedSort], $direction);
        } else {
            $reservations->orderBy('reservations.reservation_date', 'desc');
        }

        if ($requestedSort !== 'id') {
            $reservations->orderBy('reservations.id', 'desc');
        }

        $reservations = $reservations
            ->paginate(6)
            ->withQueryString();

        // Get count of today's reservations for tab badge
        $todayCount = Reservation::whereDate('reservation_date', now()->toDateString())->count();

        return view('admin.reservation', [
            'reservations' => $reservations,
            'sort' => $requestedSort,
            'direction' => $direction,
            'tab' => $tab,
            'todayCount' => $todayCount,
        ]);
    }

    public function residentAvailable(Request $request)
    {
        $validated = $request->validate([
            'reservation_date' => ['required', 'date'],
        ]);

        $date = $validated['reservation_date'];

        // If date is within any active closure period, return no services
        $hasAnyClosure = \App\Models\ClosurePeriod::active()
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->exists();
        if ($hasAnyClosure) {
            return response()->json(['services' => []]);
        }

        $services = Service::query()->where('is_active', true)->get()
            ->map(function (Service $service) use ($date) {
                $usedUnits = Reservation::query()
                    ->where('service_id', $service->id)
                    ->whereDate('reservation_date', $date)
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->sum('units_reserved');

                $remaining = max(0, $service->capacity_units - $usedUnits);
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'description' => $service->description,
                    'capacity_units' => $service->capacity_units,
                    'remaining_units' => $remaining,
                ];
            })
            ->filter(fn($s) => $s['remaining_units'] > 0)
            ->values();

        // Fallback: if none available (e.g., no time overlap logic, or new day), show active services
        if ($services->isEmpty()) {
            $fallback = Service::query()->where('is_active', true)->orderBy('name')->get()
                ->map(fn(Service $svc) => [
                    'id' => $svc->id,
                    'name' => $svc->name,
                    'description' => $svc->description,
                    'capacity_units' => $svc->capacity_units,
                    'remaining_units' => $svc->capacity_units,
                ]);
            return response()->json(['services' => $fallback]);
        }

        return response()->json(['services' => $services]);
    }

    public function activeServices()
    {
        $services = Service::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id','name','description','capacity_units']);
        return response()->json(['services' => $services]);
    }

    public function fullyBookedDates(Request $request)
    {
        $start = $request->date('start', now()->startOfMonth());
        $end = $request->date('end', now()->addMonths(2)->endOfMonth());

        $services = Service::where('is_active', true)->get(['id', 'capacity_units']);
        if ($services->isEmpty()) {
            return response()->json(['dates' => []]);
        }

        $capacityByService = $services->pluck('capacity_units', 'id');
        $dates = [];

        $current = $start->clone();
        while ($current->lte($end)) {
            $date = $current->toDateString();

            $isFullyBooked = true;
            foreach ($services as $service) {
                $totalUnits = (int) Reservation::where('service_id', $service->id)
                    ->whereDate('reservation_date', $date)
                    ->whereIn('status', ['pending','confirmed'])
                    ->sum('units_reserved');
                if ($totalUnits < $service->capacity_units) {
                    $isFullyBooked = false;
                    break;
                }
            }

            if ($isFullyBooked) {
                $dates[] = $date;
            }

            $current->addDay();
        }

        // Include admin-closed dates (closure periods)
        $closed = ClosurePeriod::active()
            ->whereDate('end_date', '>=', $start->toDateString())
            ->whereDate('start_date', '<=', $end->toDateString())
            ->get(['start_date','end_date']);

        foreach ($closed as $p) {
            $d = \Carbon\Carbon::parse($p->start_date)->max($start->copy());
            $dEnd = \Carbon\Carbon::parse($p->end_date)->min($end->copy());
            while ($d->lte($dEnd)) {
                $day = $d->toDateString();
                if (!in_array($day, $dates, true)) {
                    $dates[] = $day;
                }
                $d->addDay();
            }
        }

        sort($dates);
        return response()->json(['dates' => array_values(array_unique($dates))]);
    }

    public function hasReservationForDate(Request $request)
    {
        $date = $request->query('date');
        if (!$date) {
            return response()->json(['blocked' => false]);
        }
        $exists = Reservation::where('user_id', Auth::id())
            ->whereDate('reservation_date', $date)
            // Block another reservation for the same date even if it was completed
            ->whereIn('status', ['pending','confirmed','completed'])
            ->exists();
        return response()->json([
            'blocked' => $exists,
            'message' => $exists ? 'You already have a reservation for this date.' : null,
        ]);
    }

    public function getUnavailableDates(Request $request)
    {
        $start = $request->query('start', now()->toDateString());
        $end = $request->query('end', now()->addMonths(3)->toDateString());

        $closedDates = [];
        $fullyBookedDates = [];

        // Get all active closure periods
        $closurePeriods = ClosurePeriod::active()->get();

        // Process closure periods and expand date ranges
        foreach ($closurePeriods as $period) {
            $startDate = \Carbon\Carbon::parse($period->start_date);
            $endDate = \Carbon\Carbon::parse($period->end_date);
            
            // Filter by requested date range
            $rangeStart = \Carbon\Carbon::parse($start);
            $rangeEnd = \Carbon\Carbon::parse($end);
            
            $currentDate = $startDate->max($rangeStart);
            $lastDate = $endDate->min($rangeEnd);
            
            while ($currentDate->lte($lastDate)) {
                $dateStr = $currentDate->toDateString();
                if (!in_array($dateStr, $closedDates)) {
                    $closedDates[] = $dateStr;
                }
                $currentDate->addDay();
            }
        }

        // Check for fully booked dates
        $services = Service::where('is_active', true)->get(['id', 'capacity_units']);
        if (!$services->isEmpty()) {
            $current = \Carbon\Carbon::parse($start);
            $endCarbon = \Carbon\Carbon::parse($end);
            
            while ($current->lte($endCarbon)) {
                $dateStr = $current->toDateString();
                
                // Skip if already in closed dates
                if (!in_array($dateStr, $closedDates)) {
                    $isFullyBooked = true;
                    foreach ($services as $service) {
                        $totalUnits = (int) Reservation::where('service_id', $service->id)
                            ->whereDate('reservation_date', $dateStr)
                            ->whereIn('status', ['pending', 'confirmed'])
                            ->sum('units_reserved');
                        
                        if ($totalUnits < $service->capacity_units) {
                            $isFullyBooked = false;
                            break;
                        }
                    }
                    
                    if ($isFullyBooked) {
                        $fullyBookedDates[] = $dateStr;
                    }
                }
                
                $current->addDay();
            }
        }

        // Combine all unavailable dates
        $unavailableDates = array_unique(array_merge($closedDates, $fullyBookedDates));
        sort($unavailableDates);

        return response()->json([
            'closed_dates' => $closedDates,
            'fully_booked_dates' => $fullyBookedDates,
            'unavailable_dates' => $unavailableDates
        ]);
    }

    public function getAvailableTimeSlots(Request $request)
    {
        $date = $request->query('date');
        if (!$date) {
            return response()->json(['error' => 'Date is required'], 400);
        }

        // Check if user already has a reservation for this date
        $hasReservation = Reservation::where('user_id', Auth::id())
            ->whereDate('reservation_date', $date)
            ->whereIn('status', ['pending','confirmed','completed'])
            ->exists();

        if ($hasReservation) {
            return response()->json([
                'error' => 'You already have a reservation for this date.',
                'time_slots' => [],
                'services' => []
            ]);
        }

        // Check for closure periods
        $isClosed = ClosurePeriod::active()
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->exists();

        if ($isClosed) {
            return response()->json([
                'error' => 'The selected date is closed.',
                'time_slots' => [],
                'services' => []
            ]);
        }

        // Generate available time slots (30-minute intervals, max 2 hours)
        $timeSlots = $this->generateTimeSlots($date);

        // Get all active services with their availability for each time slot
        $services = Service::where('is_active', true)->get();
        $serviceAvailability = [];

        foreach ($services as $service) {
            $availability = [];
            foreach ($timeSlots as $slot) {
                $availableUnits = $this->calculateAvailableUnits($service, $date, $slot['start_time'], $slot['end_time']);
                $availability[] = [
                    'time_slot' => $slot,
                    'available_units' => $availableUnits,
                    'is_available' => $availableUnits > 0
                ];
            }
            $serviceAvailability[] = [
                'service' => $service,
                'availability' => $availability
            ];
        }

        return response()->json([
            'time_slots' => $timeSlots,
            'services' => $serviceAvailability,
            'date' => $date
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        
        // Check if user is suspended
        if ($user->isSuspended()) {
            $daysRemaining = $user->suspension_days_remaining;
            $message = "Your account is suspended for $daysRemaining days due to 3 no-show or cancellation violations. You cannot make reservations until the suspension period ends.";
            return redirect()->route('resident.dashboard')->withErrors(['suspension' => $message]);
        }
        
        // Enforce per-day cooldown (resets at midnight): only one booking can be created per calendar day
        $this->assertCooldown($user->id);
        $validated = $request->validate([
            'service_id' => ['required', Rule::exists('services', 'id')->where('is_active', true)],
            'reservation_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'preferences' => ['nullable', 'string', 'max:2000'],
            'reservation_reason' => ['required', 'string', 'in:Surfing,Reading,Making Activity,Others'],
            'other_reason' => ['nullable', 'required_if:reservation_reason,Others', 'string', 'max:20'],
        ]);

        $startTime = $validated['start_time'];
        $endTime = $validated['end_time'];
        
        if ($startTime >= $endTime) {
            return back()->withErrors(['end_time' => 'End time must be after start time.'])->withInput();
        }
        
        // Prevent past time selection for today's reservations
        try {
            $this->assertNotPastTime($validated['reservation_date'], $startTime);
        } catch (\Exception $e) {
            return back()->withErrors(['start_time' => $e->getMessage()])->withInput();
        }
        
        // Enforce 2-hour maximum limit
        try {
            $this->validateTimeLimit($startTime, $endTime);
        } catch (\Exception $e) {
            return back()->withErrors(['end_time' => $e->getMessage()])->withInput();
        }
        
        // Validate business hours
        try {
            $this->validateBusinessHours($startTime, $endTime);
        } catch (\Exception $e) {
            return back()->withErrors(['start_time' => $e->getMessage()])->withInput();
        }
        
        // Same day cutoff
        try {
            $this->assertSameDayCutoff($validated['reservation_date']);
        } catch (\Exception $e) {
            return back()->withErrors(['reservation_date' => $e->getMessage()])->withInput();
        }
        
        // Check for closures
        try {
            $this->assertNotClosed($validated['reservation_date'], $startTime, $endTime);
        } catch (\Exception $e) {
            return back()->withErrors(['reservation_date' => $e->getMessage()])->withInput();
        }
        
        // Check once per day
        try {
            $this->assertOncePerDay(Auth::id(), $validated['reservation_date']);
        } catch (\Exception $e) {
            return back()->withErrors(['reservation_date' => $e->getMessage()])->withInput();
        }
        
        // Check capacity
        try {
            $this->assertCapacity($validated['service_id'], $validated['reservation_date'], $startTime, $endTime, 1);
        } catch (\Exception $e) {
            return back()->withErrors(['service_id' => $e->getMessage()])->withInput();
        }

        $reservation = Reservation::create([
            'user_id' => Auth::id(),
            'service_id' => $validated['service_id'],
            'reference_no' => $this->generateReference(),
            'reservation_date' => $validated['reservation_date'],
            'start_time' => $startTime,
            'end_time' => $endTime,
            'units_reserved' => 1,
            'preferences' => $request->get('preferences'),
            'reservation_reason' => $validated['reservation_reason'],
            'other_reason' => $validated['reservation_reason'] === 'Others' ? $validated['other_reason'] : null,
            'status' => 'pending',
        ]);

        return redirect()->route('resident.reservation.ticket', $reservation->id)
            ->with('status', 'Reservation submitted. Reference: '.$reservation->reference_no);
    }

    public function destroy($id)
    {
        $reservation = Reservation::where('user_id', Auth::id())->findOrFail($id);
        
        // Check if cancellation is allowed
        if (in_array($reservation->status, ['cancelled', 'completed'])) {
            return redirect()->route('resident.reservation')
                ->withErrors(['error' => 'This reservation cannot be cancelled.']);
        }

        $minutesSinceCreation = $reservation->created_at->diffInMinutes(now());
        if ($minutesSinceCreation > 10) {
            return redirect()->route('resident.reservation')
                ->withErrors(['error' => 'Cancellation period has expired. You can only cancel within 10 minutes after booking.']);
        }
        
        $reservation->update([
            'status' => 'cancelled',
            'cancelled_by' => Auth::id(),
            'cancelled_at' => now(),
        ]);
        return redirect()->route('resident.reservation')->with('status', 'Reservation cancelled successfully.');
    }

    public function ticket($id)
    {
        $reservation = Reservation::with('service')->where('user_id', Auth::id())->findOrFail($id);
        return view('resident.ticket', compact('reservation'));
    }

    public function history()
    {
        $reservations = Reservation::with('service')
            ->where('user_id', Auth::id())
            ->orderByDesc('reservation_date')
            ->orderByDesc('start_time')
            ->get();

        return view('resident.booking_history', compact('reservations'));
    }

    public function setActualTimes(Request $request, $id)
    {
        $reservation = Reservation::with('user','service')->findOrFail($id);
        if (in_array($reservation->status, ['cancelled','completed'])) {
            abort(422, 'Cannot edit times for this reservation.');
        }
        $validated = $request->validate([
            'actual_time_in' => ['nullable','date_format:H:i'],
            'actual_time_out' => ['nullable','date_format:H:i'],
            'action' => ['required','in:save,submit'],
        ]);

        $updates = [];
        if (array_key_exists('actual_time_in', $validated)) {
            $updates['actual_time_in'] = $validated['actual_time_in'];
        }
        if (array_key_exists('actual_time_out', $validated)) {
            $updates['actual_time_out'] = $validated['actual_time_out'];
        }

        if ($validated['action'] === 'submit') {
            if (empty($updates['actual_time_out'])) {
                return back()->withErrors(['actual_time_out' => 'Time Out is required to submit.'])->withInput();
            }
            $updates['status'] = 'completed';
        }

        if (!empty($updates)) {
            $reservation->update($updates);
        }

        return back()->with('status', $validated['action'] === 'submit' ? 'Reservation completed' : 'Draft saved');
    }

    public function adminCancel(Request $request, $id)
    {
        $reservation = Reservation::with('user')->findOrFail($id);
        
        // Check if reservation can be cancelled (not already cancelled or completed)
        if ($reservation->status === 'cancelled') {
            return back()->withErrors(['error' => 'This reservation is already cancelled.']);
        }
        
        if ($reservation->status === 'completed') {
            return back()->withErrors(['error' => 'Cannot cancel a completed reservation.']);
        }
        
        // Validate the cancellation reason
        $validated = $request->validate([
            'cancellation_reason' => ['required', 'string', 'min:3', 'max:255'],
            'apply_suspension' => ['nullable', 'boolean'],
        ]);
        
        $applySuspension = $request->has('apply_suspension');
        
        // Use the model method to cancel with reason and optional suspension
        $reservation->cancelWithReason(
            $validated['cancellation_reason'],
            $applySuspension,
            Auth::id()
        );
        
        $message = "Reservation #{$reservation->reference_no} has been cancelled.";
        
        // Add suspension info to the message if applicable
        if ($applySuspension && $reservation->user) {
            if ($reservation->user->is_suspended) {
                $message .= " The resident has been suspended for 7 days due to multiple violations.";
            } else {
                $message .= " A suspension warning has been applied to the resident's account.";
            }
        }
        
        return redirect()->route('reservation.dashboard')
            ->with('status', $message);
    }

    private function validateBusinessHours(string $start, string $end): void
    {
        if ($start < '08:00' || $end > '17:00') {
            abort(422, 'Reservations are allowed only between 08:00 and 17:00.');
        }
    }

    private function validateTimeLimit(string $start, string $end): void
    {
        $startTime = \Carbon\Carbon::createFromFormat('H:i', $start);
        $endTime = \Carbon\Carbon::createFromFormat('H:i', $end);
        $duration = $startTime->diffInHours($endTime);
        
        if ($duration > 2) {
            abort(422, 'Reservation duration cannot exceed 2 hours.');
        }
    }

    private function generateTimeSlots(string $date): array
    {
        $timeSlots = [];
        $startHour = 8; // 8:00 AM
        $endHour = 16;  // 4:00 PM (latest start time for 30-min slot ending at 4:30 PM)
        
        // Check if the selected date is today
        $isToday = $date === now()->toDateString();
        $currentTime = now();
        
        // Valid durations in hours: 0.5 (30 mins), 1, 1.5, 2
        $durations = [0.5, 1, 1.5, 2];

        for ($hour = $startHour; $hour <= $endHour; $hour++) {
            for ($minute = 0; $minute < 60; $minute += 30) {
                $startTime = sprintf('%02d:%02d', $hour, $minute);
                
                foreach ($durations as $durationHours) {
                    // Calculate end time based on duration
                    $startDateTime = \Carbon\Carbon::createFromFormat('H:i', $startTime);
                    $endDateTime = $startDateTime->copy()->addHours($durationHours);
                    $endTime = $endDateTime->format('H:i');
                    
                    // Skip if end time exceeds business hours
                    if ($endTime > '17:00') {
                        continue;
                    }
                    
                    // If booking for today, skip past time slots
                    if ($isToday) {
                        // Create a datetime for the start time today
                        $slotStartDateTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $startTime);
                        
                        // Skip if this time slot is in the past
                        if ($slotStartDateTime->lte($currentTime)) {
                            continue;
                        }
                    }
                    
                    // Calculate duration in minutes for display and sorting
                    $durationMinutes = (int)($durationHours * 60);
                    
                    // Format duration display
                    $durationDisplay = $durationMinutes == 30 ? '30 mins' : 
                                      ($durationMinutes == 60 ? '1 hour' : 
                                      ($durationMinutes == 90 ? '1.5 hours' : '2 hours'));
                    
                    $timeSlots[] = [
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'duration_hours' => $durationHours,
                        'duration_minutes' => $durationMinutes,
                        'duration_display' => $durationDisplay,
                        'display' => date('g:i A', strtotime($startTime)) . ' - ' . date('g:i A', strtotime($endTime)) . ' (' . $durationDisplay . ')'
                    ];
                }
            }
        }
        
        return $timeSlots;
    }

    private function calculateAvailableUnits(Service $service, string $date, string $startTime, string $endTime): int
    {
        $usedUnits = Reservation::where('service_id', $service->id)
            ->whereDate('reservation_date', $date)
            ->where(function ($q) use ($startTime, $endTime) {
                // Two time ranges overlap if: start_time < selected_end_time AND end_time > selected_start_time
                $q->where('start_time', '<', $endTime)
                  ->where('end_time', '>', $startTime);
            })
            ->whereIn('status', ['pending', 'confirmed'])
            ->sum('units_reserved');

        return max(0, $service->capacity_units - $usedUnits);
    }

    private function assertOncePerDay(int $userId, string $date): void
    {
        $exists = Reservation::where('user_id', $userId)
            ->whereDate('reservation_date', $date)
            // Completed reservations still count for the once-per-day rule
            ->whereIn('status', ['pending', 'confirmed', 'completed'])
            ->exists();
        if ($exists) {
            abort(422, 'You already have a reservation for this date.');
        }
    }

    private function assertCooldown(int $userId): void
    {
        // Cooldown resets at 12:00 AM local time. If the user already created a booking today, block another regardless of chosen date.
        $existsToday = Reservation::where('user_id', $userId)
            ->whereDate('created_at', now()->toDateString())
            // Count completed reservations as well for the same-day cooldown
            ->whereIn('status', ['pending', 'confirmed', 'completed'])
            ->exists();
        if ($existsToday) {
            throw ValidationException::withMessages([
                'reservation_date' => 'You can only make one reservation per day. Please try again after 12:00 AM.'
            ]);
        }
    }

    private function cooldownState(int $userId): array
    {
        $existsToday = Reservation::where('user_id', $userId)
            ->whereDate('created_at', now()->toDateString())
            // Show cooldown when a reservation was made today in any non-cancelled state
            ->whereIn('status', ['pending', 'confirmed', 'completed'])
            ->exists();
        if (!$existsToday) {
            return [false, null];
        }
        $until = now()->startOfDay()->addDay(); // next midnight
        return [true, $until];
    }

    private function assertNotPastTime(string $reservationDate, string $startTime): void
    {
        // If booking for today, prevent past time selection
        if ($reservationDate === now()->toDateString()) {
            $slotStartDateTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $reservationDate . ' ' . $startTime);
            $currentTime = now();
            
            if ($slotStartDateTime->lte($currentTime)) {
                abort(422, 'Cannot select past time slots. Please choose a future time.');
            }
        }
    }

    private function assertSameDayCutoff(string $reservationDate): void
    {
        // If booking for today, only allow until 15:00 (3:00 PM)
        if ($reservationDate === now()->toDateString()) {
            if (now()->format('H:i') >= '15:00') {
                abort(422, 'Same-day reservations are allowed only until 3:00 PM.');
            }
        }
    }

    // Removed 24-hour cooldown logic per requirements

    private function assertCapacity(int $serviceId, string $date, string $start, string $end, int $requestedUnits, ?int $ignoreReservationId = null): void
    {
        $service = Service::findOrFail($serviceId);

        $usedUnitsQuery = Reservation::query()
            ->where('service_id', $serviceId)
            ->whereDate('reservation_date', $date)
            ->where(function ($q) use ($start, $end) {
                // Two time ranges overlap if: start_time < selected_end_time AND end_time > selected_start_time
                $q->where('start_time', '<', $end)
                  ->where('end_time', '>', $start);
            })
            ->whereIn('status', ['pending', 'confirmed']);

        if ($ignoreReservationId) {
            $usedUnitsQuery->where('id', '!=', $ignoreReservationId);
        }

        $usedUnits = (int) $usedUnitsQuery->sum('units_reserved');

        if (($usedUnits + $requestedUnits) > $service->capacity_units) {
            abort(422, 'Not enough capacity for the selected time.');
        }
    }

    private function assertCancellable(Reservation $reservation): void
    {
        if (in_array($reservation->status, ['cancelled', 'completed'])) {
            abort(422, 'This reservation cannot be cancelled.');
        }

        $minutesSinceCreation = $reservation->created_at->diffInMinutes(now());
        if ($minutesSinceCreation > 10) {
            abort(422, 'Cancellation period has expired. You can only cancel within 10 minutes after booking.');
        }
    }

    private function generateReference(): string
    {
        do {
            $ref = 'RSV-'.now()->format('Ymd').'-'.strtoupper(bin2hex(random_bytes(3)));
        } while (Reservation::where('reference_no', $ref)->exists());

        return $ref;
    }

    private function assertNotClosed(string $date, string $start, string $end): void
    {
        $periods = ClosurePeriod::active()
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->get();

        if ($periods->isNotEmpty()) {
            abort(422, 'The selected date is closed.');
        }
    }

    /**
     * Get today's reservations for 5-minute warning notifications
     */
    public function getTodayWarnings()
    {
        $today = now()->toDateString();
        
        $reservations = Reservation::query()
            ->leftJoin('users', 'users.id', '=', 'reservations.user_id')
            ->leftJoin('services', 'services.id', '=', 'reservations.service_id')
            ->select(
                'reservations.id',
                'reservations.end_time',
                'users.first_name',
                'users.last_name',
                'services.name as service_name'
            )
            ->whereDate('reservations.reservation_date', $today)
            ->whereIn('reservations.status', ['pending', 'confirmed'])
            ->get()
            ->map(function ($reservation) {
                return [
                    'id' => $reservation->id,
                    'end_time' => substr($reservation->end_time, 0, 5), // HH:MM format
                    'resident_name' => trim($reservation->first_name . ' ' . $reservation->last_name),
                    'service_name' => $reservation->service_name,
                ];
            });

        return response()->json([
            'reservations' => $reservations,
        ]);
    }
}
