@extends('layouts.admin_panel')

@section('title', 'Reports')

@section('content')
<div class="space-y-6">
    <!-- Date Range Filter Card -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Filter Reports by Date Range</h3>
        </div>
        <div class="px-6 py-4">
            <form method="GET" action="{{ route('admin.reports.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <!-- Report Type Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Report Type</label>
                        <select name="report_type" id="report_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                            <option value="all" {{ isset($reportType) && $reportType == 'all' ? 'selected' : '' }}>All Reports</option>
                            <option value="reservations" {{ isset($reportType) && $reportType == 'reservations' ? 'selected' : '' }}>Reservations Report</option>
                            <option value="services" {{ isset($reportType) && $reportType == 'services' ? 'selected' : '' }}>Services Report</option>
                            <option value="reasons" {{ isset($reportType) && $reportType == 'reasons' ? 'selected' : '' }}>Reasons Report</option>
                            <option value="peak" {{ isset($reportType) && $reportType == 'peak' ? 'selected' : '' }}>Peak Usage Report</option>
                            <option value="engagement" {{ isset($reportType) && $reportType == 'engagement' ? 'selected' : '' }}>User Engagement Report</option>
                        </select>
                    </div>
                    
                    <!-- Date Range Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
                        <select name="date_range" id="date_range" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                            <option value="all" {{ $dateRange == 'all' ? 'selected' : '' }}>All Time</option>
                            <option value="weekly" {{ $dateRange == 'weekly' ? 'selected' : '' }}>This Week</option>
                            <option value="monthly" {{ $dateRange == 'monthly' ? 'selected' : '' }}>This Month</option>
                            <option value="yearly" {{ $dateRange == 'yearly' ? 'selected' : '' }}>This Year</option>
                            <option value="custom" {{ $dateRange == 'custom' ? 'selected' : '' }}>Custom Range</option>
                        </select>
                    </div>

                    <!-- Start Date (shown when custom is selected) -->
                    <div id="start_date_container" class="{{ $dateRange == 'custom' ? '' : 'hidden' }}">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                        <input type="date" name="start_date" id="start_date" value="{{ $startDate }}" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <!-- End Date (shown when custom is selected) -->
                    <div id="end_date_container" class="{{ $dateRange == 'custom' ? '' : 'hidden' }}">
                        <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                        <input type="date" name="end_date" id="end_date" value="{{ $endDate }}" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <!-- Apply Button -->
                    <div class="flex items-end">
                        <button type="submit" class="w-full px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition font-medium">
                            <i class="fas fa-filter mr-2"></i>Apply Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Export Options -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Export Reports</h3>
        </div>
        <div class="px-6 py-4">
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.reports.export.csv', ['date_range' => $dateRange, 'start_date' => $startDate, 'end_date' => $endDate, 'report_type' => isset($reportType) ? $reportType : 'all']) }}" 
                   class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition font-medium">
                    <i class="fas fa-file-csv mr-2"></i>Export as CSV
                </a>
                <a href="{{ route('admin.reports.export.pdf', ['date_range' => $dateRange, 'start_date' => $startDate, 'end_date' => $endDate, 'report_type' => isset($reportType) ? $reportType : 'all']) }}" 
                   class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition font-medium">
                    <i class="fas fa-file-pdf mr-2"></i>Export as PDF
                </a>
            </div>
        </div>
    </div>

    <!-- Reservations Report Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 {{ (!isset($reportType) || $reportType == 'all' || $reportType == 'reservations') ? '' : 'hidden' }}" id="reservations-report">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Reservations Report</h3>
            <p class="text-sm text-gray-500 mt-1">
                @if($calculatedStartDate && $calculatedEndDate)
                    Period: {{ $calculatedStartDate->format('M d, Y') }} - {{ $calculatedEndDate->format('M d, Y') }}
                @else
                    Period: All Time
                @endif
            </p>
                {{-- Quick summary --}}
            @php
                if (isset($reservationsData)) {
                    $reservationsItems = method_exists($reservationsData, 'items') ? $reservationsData->items() : (is_array($reservationsData) ? $reservationsData : $reservationsData);
                    $reservationsCol = collect($reservationsItems);
                    $totalRecords = method_exists($reservationsData, 'total') ? $reservationsData->total() : $reservationsCol->count();
                    $pending = $reservationsCol->where('status', 'pending')->count();
                    $confirmed = $reservationsCol->where('status', 'confirmed')->count();
                    $completed = $reservationsCol->where('status', 'completed')->count();
                }
            @endphp
            <div class="px-6 pb-2 py-2">
                <div class="text-sm text-gray-700">
                    <p>
                    Summary: <strong>{{ $totalRecords ?? 0 }}</strong> 
                    total — <span class="text-amber-600">Pending: {{ $pending ?? 0 }}</span>,
                     <span class="text-green-600">Confirmed: {{ $confirmed ?? 0 }}</span>, 
                     <span class="text-green-600">Completed: {{ $completed ?? 0 }}</span>
                     </p>
                </div>
            </div>
        </div>
        <div class="px-6 py-4">
            @if(isset($reservationsData) && $reservationsData->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Reference No</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Resident Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Service Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($reservationsData as $reservation)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $reservation->reference_no }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $reservation->user ? $reservation->user->name : 'N/A' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $reservation->service ? $reservation->service->name : 'N/A' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ \Carbon\Carbon::parse($reservation->reservation_date)->format('M d, Y') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ \Carbon\Carbon::parse($reservation->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($reservation->end_time)->format('h:i A') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        @if($reservation->status === 'pending')
                                            <span class="text-amber-600 font-medium">Pending</span>
                                        @elseif($reservation->status === 'confirmed')
                                            <span class="text-green-600 font-medium">Confirmed</span>
                                        @elseif($reservation->status === 'completed')
                                            <span class="text-green-600 font-medium">Completed</span>
                                        @elseif($reservation->status === 'cancelled')
                                            <span class="text-red-600 font-medium">Cancelled</span>
                                        @else
                                            <span class="text-gray-600 font-medium">{{ ucfirst($reservation->status) }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    {{ $reservationsData->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <i class="fas fa-inbox text-gray-400 text-4xl mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Reservations Found</h3>
                    <p class="text-gray-500">There are no reservations in the selected period.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Services Report Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 {{ (!isset($reportType) || $reportType == 'all' || $reportType == 'services') ? '' : 'hidden' }}" id="services-report">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Services Report</h3>
            <p class="text-sm text-gray-500 mt-1">
                @if($calculatedStartDate && $calculatedEndDate)
                    Period: {{ $calculatedStartDate->format('M d, Y') }} - {{ $calculatedEndDate->format('M d, Y') }}
                @else
                    Period: All Time
                @endif
            </p>
            {{-- Quick summary for services --}}
            @php
                if (isset($servicesData)) {
                    $servicesCount = count($servicesData);
                    $totalUsage = 0;
                    $totalUniqueUsers = 0;
                    $topServiceName = null;
                    $topServiceCount = 0;

                    foreach ($servicesData as $s) {
                        $count = $s['usage_count'] ?? 0;
                        $totalUsage += $count;
                        $totalUniqueUsers += ($s['unique_users'] ?? 0);

                        if ($count > $topServiceCount) {
                            $topServiceCount = $count;
                            $topServiceName = $s['service']->name ?? null;
                        }
                    }

                    $avgUsage = $servicesCount > 0 ? round($totalUsage / $servicesCount, 1) : 0;
                }
            @endphp

            <div class="px-6 pb-2 py-2">
                <div class="text-sm text-gray-700">
                    <p>
                        Summary:
                        <strong>{{ $servicesCount ?? 0 }}</strong> services —
                        Total Usage: <strong>{{ number_format($totalUsage ?? 0) }}</strong> —
                        Unique Users: <strong>{{ number_format($totalUniqueUsers ?? 0) }}</strong> —
                        Top Service: <strong>{{ $topServiceName ?? '—' }}</strong> (<strong>{{ $topServiceCount ?? 0 }}</strong>) —
                        Avg Usage/service: <strong>{{ $avgUsage ?? 0 }}</strong>
                    </p>
                </div>
            </div>
        </div>
        <div class="px-6 py-4">
            @if(isset($servicesData) && count($servicesData) > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Service Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Description</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Total Usage</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Unique Users</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Quantity</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($servicesData as $data)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $data['service']->name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $data['service']->description ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-yellow-600">{{ number_format($data['usage_count']) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ number_format($data['unique_users']) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $data['service']->capacity_units }} units</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        @if($data['service']->is_active)
                                            <span class="text-green-600 font-medium">Active</span>
                                        @else
                                            <span class="text-gray-600 font-medium">Inactive</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-12">
                    <i class="fas fa-toolbox text-gray-400 text-4xl mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Services Data Available</h3>
                    <p class="text-gray-500">There are no active services or reservations in the selected period.</p>
                </div>
            @endif
        </div>
      
    </div>

    <!-- Reasons Report Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 {{ (!isset($reportType) || $reportType == 'all' || $reportType == 'reasons') ? '' : 'hidden' }}" id="reasons-report">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Reservation Reasons Analysis</h3>
            <p class="text-sm text-gray-500 mt-1">
                @if($calculatedStartDate && $calculatedEndDate)
                    Period: {{ $calculatedStartDate->format('M d, Y') }} - {{ $calculatedEndDate->format('M d, Y') }}
                @else
                    Period: All Time
                @endif
            </p>
             {{-- Quick summary for reasons --}}
                 @php
                     if (isset($reasonsData)) {
                        $serviceReasonCount = is_array($reasonsData['service_reason_mapping']) ? count($reasonsData['service_reason_mapping']) : $reasonsData['service_reason_mapping']->count();
                            $uniqueReasons = 0;
                              $totalReasonEntries = 0;
                                if (isset($reasonsData['service_reason_mapping'])) {
                                    foreach ($reasonsData['service_reason_mapping'] as $srv => $info) {
                                        $totalReasonEntries += ($info['total_usage'] ?? 0);
                                        $uniqueReasons += is_array($info['reason_breakdown']) ? count($info['reason_breakdown']) : (method_exists($info['reason_breakdown'], 'count') ? $info['reason_breakdown']->count() : 0);
                                        }
                                    }
                                }
                 @endphp
                <div class="px-6 pb-2 py-2">
                    <div class="text-sm text-gray-700">
                        <p>
                        Summary: <strong>{{ $serviceReasonCount ?? 0 }}</strong> 
                        services reported reasons — Unique Reasons: <strong>{{ $uniqueReasons ?? 0 }}</strong> — 
                        Total Reasoned Reservations: <strong>{{ $totalReasonEntries ?? 0 }}</strong>
                        </p>        
                    </div>
                </div>
        </div>
        <div class="px-6 py-4">
            @if(isset($reasonsData) && count($reasonsData['service_reason_mapping']) > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Service</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Total Usage</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Top Reasons</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Other Reasons (Samples)</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($reasonsData['service_reason_mapping'] as $serviceName => $info)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $serviceName }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $info['total_usage'] }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        @foreach($info['reason_breakdown']->take(5) as $reason => $count)
                                            <div>{{ $reason }}: <strong>{{ $count }}</strong></div>
                                        @endforeach
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        @if(count($info['other_reasons']) > 0)
                                            @foreach($info['other_reasons']->take(3) as $o)
                                                <div>{{ $o }}</div>
                                            @endforeach
                                        @else
                                            <div class="text-gray-500">—</div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Additional Tables for Detailed Analysis -->
                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Reason Demographics Table -->
                    <div class="bg-white rounded-lg border border-gray-200">
                        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                            <h4 class="text-sm font-semibold text-gray-800">Reason Demographics</h4>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Reason</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Total Users</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">PWD %</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Gender Split</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($reasonsData['reason_demographics'] as $reason => $data)
                                        <tr class="hover:bg-gray-50 transition">
                                            <td class="px-4 py-2 text-sm text-gray-700">{{ Str::limit($reason, 30) }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-700">{{ $data['total_users'] }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-700">{{ number_format($data['demographics']['pwd_percentage'] ?? 0, 1) }}%</td>
                                            <td class="px-4 py-2 text-sm text-gray-700">
                                                @if(isset($data['demographics']['gender_split']))
                                                    @foreach($data['demographics']['gender_split'] as $g => $c)
                                                        <span class="inline-block mr-2">{{ $g }}: {{ $c }}</span>
                                                    @endforeach
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Emerging Needs Table -->
                    <div class="bg-white rounded-lg border border-gray-200">
                        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                            <h4 class="text-sm font-semibold text-gray-800">Emerging Other Reasons</h4>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">#</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Reason</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Count</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @if(isset($reasonsData['emerging_needs']) && count($reasonsData['emerging_needs']) > 0)
                                        @foreach($reasonsData['emerging_needs'] as $other => $count)
                                            <tr class="hover:bg-gray-50 transition">
                                                <td class="px-4 py-2 text-sm text-gray-700">{{ $loop->iteration }}</td>
                                                <td class="px-4 py-2 text-sm text-gray-700">{{ Str::limit($other, 40) }}</td>
                                                <td class="px-4 py-2 text-sm text-gray-700">{{ $count }}</td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="3" class="px-4 py-4 text-center text-sm text-gray-500">
                                                No emerging other reasons found.
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-12">
                    <i class="fas fa-question-circle text-gray-400 text-4xl mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Reasons Data Available</h3>
                    <p class="text-gray-500">There are no reservation reasons recorded for the selected period.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Peak Usage Report Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 {{ (!isset($reportType) || $reportType == 'all' || $reportType == 'peak') ? '' : 'hidden' }}" id="peak-report">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Peak Usage Analysis</h3>
            <p class="text-sm text-gray-500 mt-1">
                @if($calculatedStartDate && $calculatedEndDate)
                    Period: {{ $calculatedStartDate->format('M d, Y') }} - {{ $calculatedEndDate->format('M d, Y') }}
                @else
                    Period: All Time
                @endif
            </p>
            {{-- Quick summary for peak usage --}}
            @php
                if (isset($peakData)) {
                    $hourly = $peakData['hourly_usage'] ?? null;
                    $daily = $peakData['daily_patterns'] ?? null;
                    $topHour = null;
                    $topHourCount = 0;
                    $totalPeakReservations = 0;
                    if ($hourly) {
                        $hourCol = is_object($hourly) ? $hourly : collect($hourly);
                        $sorted = $hourCol->sortByDesc(function($v) { return $v['total_reservations'] ?? 0; });
                        $topHour = $sorted->keys()->first();
                        $topHourCount = optional($sorted->first())['total_reservations'] ?? 0;
                        $totalPeakReservations = $hourCol->reduce(function($carry, $v) { return $carry + ($v['total_reservations'] ?? 0); }, 0);
                    }
                    $topDay = null;
                    if ($daily) {
                        $dailyCol = is_object($daily) ? $daily : collect($daily);
                        $topDay = $dailyCol->sortByDesc(function($v) { return $v; })->keys()->first();
                    }
                }
            @endphp
            <div class="px-6 pb-4">
                <div class="text-sm text-gray-700">
                    <p>
                        Summary: Top Hour: <strong>{{ $topHour ?? '—' }}</strong> 
                        ({{ $topHourCount ?? 0 }} reservations) — Top Day: <strong>{{ $topDay ?? '—' }}</strong> 
                        — Total Reservations (hourly aggregation): <strong>{{ $totalPeakReservations ?? 0 }}</strong>
                    </p>
                </div>
            </div>
        </div>
        <div class="px-6 py-4">
            @if(isset($peakData))
                <!-- Top Hours Table -->
                <div class="mb-6 bg-white rounded-lg border border-gray-200">
                    <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                        <h4 class="text-sm font-semibold text-gray-800">Top Hours (Hourly Usage)</h4>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">#</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Hour</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Reservations</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Utilization Rate</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @if(isset($peakData['hourly_usage']) && $peakData['hourly_usage']->count() > 0)
                                    @foreach($peakData['hourly_usage']->sortByDesc(function($v) { return $v['total_reservations']; })->take(10) as $hour => $info)
                                        <tr class="hover:bg-gray-50 transition">
                                            <td class="px-4 py-2 text-sm text-gray-700">{{ $loop->iteration }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-700">{{ $hour }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-700">{{ $info['total_reservations'] }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-700">{{ number_format($info['utilization_rate'], 1) }}%</td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="4" class="px-4 py-4 text-center text-sm text-gray-500">
                                            No hourly usage data available.
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <!-- Daily Patterns Table -->
                    <div class="bg-white rounded-lg border border-gray-200">
                        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                            <h4 class="text-sm font-semibold text-gray-800">Daily Patterns</h4>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Day</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Reservations</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @if(isset($peakData['daily_patterns']) && $peakData['daily_patterns']->count() > 0)
                                        @foreach($peakData['daily_patterns'] as $day => $count)
                                            <tr class="hover:bg-gray-50 transition">
                                                <td class="px-4 py-2 text-sm text-gray-700">{{ $day }}</td>
                                                <td class="px-4 py-2 text-sm text-gray-700">{{ $count }}</td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="2" class="px-4 py-4 text-center text-sm text-gray-500">
                                                No daily pattern data available.
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Seasonal Trends Table -->
                    <div class="bg-white rounded-lg border border-gray-200">
                        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                            <h4 class="text-sm font-semibold text-gray-800">Seasonal Trends</h4>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Month</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Reservations</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @if(isset($peakData['seasonal_trends']) && $peakData['seasonal_trends']->count() > 0)
                                        @foreach($peakData['seasonal_trends'] as $ym => $count)
                                            <tr class="hover:bg-gray-50 transition">
                                                <td class="px-4 py-2 text-sm text-gray-700">{{ \Carbon\Carbon::createFromFormat('Y-m', $ym)->format('F Y') }}</td>
                                                <td class="px-4 py-2 text-sm text-gray-700">{{ $count }}</td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="2" class="px-4 py-4 text-center text-sm text-gray-500">
                                                No seasonal trends available.
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Service Time Preferences Table -->
                <div class="bg-white rounded-lg border border-gray-200">
                    <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                        <h4 class="text-sm font-semibold text-gray-800">Service Time Preferences</h4>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Service</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Peak Hours</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Average Duration</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @if(isset($peakData['service_time_preferences']) && $peakData['service_time_preferences']->count() > 0)
                                    @foreach($peakData['service_time_preferences'] as $service => $info)
                                        <tr class="hover:bg-gray-50 transition">
                                            <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $service }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-700">
                                                @foreach($info['peak_hours'] as $h => $c)
                                                    <span class="inline-block mr-2">{{ $h }} ({{ $c }})</span>
                                                @endforeach
                                            </td>
                                            <td class="px-4 py-2 text-sm text-gray-700">{{ round($info['average_duration'],1) }} minutes</td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="3" class="px-4 py-4 text-center text-sm text-gray-500">
                                            No service time preference data available.
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="text-center py-12">
                    <i class="fas fa-chart-line text-gray-400 text-4xl mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Peak Usage Data</h3>
                    <p class="text-gray-500">There is no usage data for the selected period.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- User Engagement Report Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 {{ (!isset($reportType) || $reportType == 'all' || $reportType == 'engagement') ? '' : 'hidden' }}" id="engagement-report">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">User Engagement</h3>
            <p class="text-sm text-gray-500 mt-1">
                @if($calculatedStartDate && $calculatedEndDate)
                    Period: {{ $calculatedStartDate->format('M d, Y') }} - {{ $calculatedEndDate->format('M d, Y') }}
                @else
                    Period: All Time
                @endif
            </p>
        </div>

        <div class="px-6 py-4">
            @if(isset($engagementData))
                @php
                    $overview = $engagementData['engagement_overview'] ?? [];
                    $segments = $engagementData['user_segments'] ?? [];
                    $demographics = $engagementData['demographic_engagement'] ?? [];
                    $topUsers = $engagementData['service_preferences'] ?? collect();
                @endphp

                <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white rounded-lg border border-gray-200 p-4">
                        <div class="text-sm text-gray-600">Total Approved Users</div>
                        <div class="text-2xl font-semibold text-gray-900">{{ number_format($overview['total_users'] ?? 0) }}</div>
                    </div>
                    <div class="bg-white rounded-lg border border-gray-200 p-4">
                        <div class="text-sm text-gray-600">Active Users</div>
                        <div class="text-2xl font-semibold text-gray-900">{{ number_format($overview['active_users'] ?? 0) }}</div>
                    </div>
                    <div class="bg-white rounded-lg border border-gray-200 p-4">
                        <div class="text-sm text-gray-600">Engagement Rate</div>
                        <div class="text-2xl font-semibold text-gray-900">{{ round($overview['engagement_rate'] ?? 0, 2) }}%</div>
                    </div>
                </div>

                <!-- Segments -->
                <div class="mb-6 bg-white rounded-lg border border-gray-200">
                    <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                        <h4 class="text-sm font-semibold text-gray-800">User Segments</h4>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Segment</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Count</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td class="px-4 py-2 text-sm text-gray-700">Super Users (5+)</td>
                                    <td class="px-4 py-2 text-sm text-gray-700">{{ isset($segments['super_users']) ? count($segments['super_users']) : 0 }}</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2 text-sm text-gray-700">Regular Users (2-4)</td>
                                    <td class="px-4 py-2 text-sm text-gray-700">{{ isset($segments['regular_users']) ? count($segments['regular_users']) : 0 }}</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2 text-sm text-gray-700">Occasional (1)</td>
                                    <td class="px-4 py-2 text-sm text-gray-700">{{ isset($segments['occasional_users']) ? count($segments['occasional_users']) : 0 }}</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-2 text-sm text-gray-700">Inactive</td>
                                    <td class="px-4 py-2 text-sm text-gray-700">{{ isset($segments['inactive_users']) ? count($segments['inactive_users']) : 0 }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Demographics Summary -->
                <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-white rounded-lg border border-gray-200 p-4">
                        <h4 class="text-sm font-semibold text-gray-800 mb-2">Engagement by Age</h4>
                        @if(isset($demographics['by_age']))
                            @foreach($demographics['by_age'] as $ageLabel => $data)
                                <div class="text-sm text-gray-700">{{ $ageLabel }} — Active: {{ $data['active_users'] ?? 0 }} / Total: {{ $data['total_users'] ?? 0 }} ({{ round($data['engagement_rate'] ?? 0,1) }}%)</div>
                            @endforeach
                        @else
                            <div class="text-sm text-gray-500">No age data available.</div>
                        @endif
                    </div>

                    <div class="bg-white rounded-lg border border-gray-200 p-4">
                        <h4 class="text-sm font-semibold text-gray-800 mb-2">Engagement by Gender / PWD</h4>
                        @if(isset($demographics['by_gender']))
                            @foreach($demographics['by_gender'] as $gender => $data)
                                <div class="text-sm text-gray-700">{{ $gender }} — Active: {{ $data['active_users'] ?? 0 }} / Total: {{ $data['total_users'] ?? 0 }} ({{ round($data['engagement_rate'] ?? 0,1) }}%)</div>
                            @endforeach
                        @else
                            <div class="text-sm text-gray-500">No gender data available.</div>
                        @endif
                        <div class="mt-3 text-sm text-gray-700">
                            <strong>PWD:</strong> {{ isset($demographics['by_pwd_status']) ? ($demographics['by_pwd_status']['pwd']['active_users'] ?? 0) . '/' . ($demographics['by_pwd_status']['pwd']['total_users'] ?? 0) : 'N/A' }}
                        </div>
                    </div>
                </div>

                <!-- Top Engaged Users -->
                <div class="bg-white rounded-lg border border-gray-200">
                    <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                        <h4 class="text-sm font-semibold text-gray-800">Top Engaged Users</h4>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">#</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">User</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Reservations</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Preferred Service</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Last Activity</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @if($topUsers && count($topUsers) > 0)
                                    @foreach($topUsers->take(20) as $i => $u)
                                        <tr class="hover:bg-gray-50 transition">
                                            <td class="px-4 py-2 text-sm text-gray-700">{{ $loop->iteration }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-700">{{ $u['user_name'] ?? 'Unknown' }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-700">{{ $u['total_reservations'] ?? 0 }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-700">{{ is_array($u['preferred_services']) ? array_keys($u['preferred_services'])[0] ?? 'N/A' : (method_exists($u['preferred_services'], 'keys') ? $u['preferred_services']->keys()->first() : 'N/A') }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-700">{{ $u['last_activity'] ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="5" class="px-4 py-4 text-center text-sm text-gray-500">No engaged users found for this period.</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="text-center py-12">
                    <i class="fas fa-users text-gray-400 text-4xl mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Engagement Data Available</h3>
                    <p class="text-gray-500">There is no engagement data for the selected period.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dateRangeSelect = document.getElementById('date_range');
    const startDateContainer = document.getElementById('start_date_container');
    const endDateContainer = document.getElementById('end_date_container');
    const reportTypeSelect = document.getElementById('report_type');
    const reservationsReport = document.getElementById('reservations-report');
    const reasonsReport = document.getElementById('reasons-report');
    const servicesReport = document.getElementById('services-report');
    const peakReport = document.getElementById('peak-report');
    const engagementReport = document.getElementById('engagement-report');
    
    dateRangeSelect.addEventListener('change', function() {
        if (this.value === 'custom') {
            startDateContainer.classList.remove('hidden');
            endDateContainer.classList.remove('hidden');
        } else {
            startDateContainer.classList.add('hidden');
            endDateContainer.classList.add('hidden');
        }
    });
    
    // Toggle visible report sections when the Apply Filter button is clicked
    const applyFilterButton = document.querySelector('form button[type="submit"]');
    if (applyFilterButton) {
        applyFilterButton.addEventListener('click', function(event) {
            const v = reportTypeSelect.value;

            if (v === 'all') {
                reservationsReport.classList.remove('hidden');
                servicesReport.classList.remove('hidden');
                reasonsReport.classList.remove('hidden');
                peakReport.classList.remove('hidden');
                engagementReport.classList.remove('hidden');
            } else if (v === 'reservations') {
                reservationsReport.classList.remove('hidden');
                servicesReport.classList.add('hidden');
                reasonsReport.classList.add('hidden');
                peakReport.classList.add('hidden');
                engagementReport.classList.add('hidden');
            } else if (v === 'services') {
                reservationsReport.classList.add('hidden');
                servicesReport.classList.remove('hidden');
                reasonsReport.classList.add('hidden');
                peakReport.classList.add('hidden');
                engagementReport.classList.add('hidden');
            } else if (v === 'reasons') {
                reservationsReport.classList.add('hidden');
                servicesReport.classList.add('hidden');
                reasonsReport.classList.remove('hidden');
                peakReport.classList.add('hidden');
                engagementReport.classList.add('hidden');
            } else if (v === 'peak') {
                reservationsReport.classList.add('hidden');
                servicesReport.classList.add('hidden');
                reasonsReport.classList.add('hidden');
                peakReport.classList.remove('hidden');
                engagementReport.classList.add('hidden');
            } else if (v === 'engagement') {
                reservationsReport.classList.add('hidden');
                servicesReport.classList.add('hidden');
                reasonsReport.classList.add('hidden');
                peakReport.classList.add('hidden');
                engagementReport.classList.remove('hidden');
            }
            // let the form submit as normal (page will reload with server-filtered results)
        });
    }
});
</script>
@endsection