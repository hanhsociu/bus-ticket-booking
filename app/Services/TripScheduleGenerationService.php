<?php

namespace App\Services;

use App\Models\Trip;
use App\Models\TripSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TripScheduleGenerationService
{
    public function __construct(
        private readonly TripSeatGenerationService $tripSeatGenerationService
    ) {}

    /**
     * Sinh trips từ một lịch chạy mẫu.
     *
     * @return array{
     *     schedule_id: int,
     *     generated_count: int,
     *     skipped_count: int,
     *     generated: array<int, array<string, mixed>>,
     *     skipped: array<int, array<string, mixed>>,
     *     dry_run: bool
     * }
     */
    public function generateForSchedule(
        TripSchedule $schedule,
        ?Carbon $fromDate = null,
        ?Carbon $toDate = null,
        bool $dryRun = false
    ): array {
        $schedule->load(['route', 'bus']);

        $report = [
            'schedule_id' => $schedule->id,
            'generated_count' => 0,
            'skipped_count' => 0,
            'generated' => [],
            'skipped' => [],
            'dry_run' => $dryRun,
        ];

        if (! $schedule->is_active) {
            $report['skipped'][] = [
                'reason' => 'schedule_inactive',
                'message' => 'Lịch chạy đang tắt (is_active = false).',
            ];
            $report['skipped_count'] = 1;

            return $report;
        }

        $today = now()->startOfDay();
        $from = $fromDate?->copy()->startOfDay()
            ?? ($schedule->start_date->gt($today) ? $schedule->start_date->copy() : $today->copy());

        $to = $toDate?->copy()->startOfDay()
            ?? $today->copy()->addDays($schedule->generate_days_ahead);

        if ($schedule->end_date && $to->gt($schedule->end_date)) {
            $to = $schedule->end_date->copy();
        }

        if ($from->gt($to)) {
            $report['skipped'][] = [
                'reason' => 'invalid_date_range',
                'message' => 'Khoảng ngày generate không hợp lệ.',
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ];
            $report['skipped_count'] = 1;

            return $report;
        }

        $route = $schedule->route;
        $bus = $schedule->bus;

        if (! $route || ! $route->is_active) {
            $this->addSkip($report, null, 'route_inactive', 'Tuyến xe không tồn tại hoặc đã bị khóa.');

            return $report;
        }

        if (! $bus || ! $bus->is_active) {
            $this->addSkip($report, null, 'bus_inactive', 'Xe không tồn tại hoặc đã bị khóa.');

            return $report;
        }

        $maxGeneratedDate = $from->copy()->subDay();

        DB::transaction(function () use ($schedule, $from, $to, $dryRun, $route, &$report, &$maxGeneratedDate) {
            for ($date = $from->copy(); $date->lte($to); $date->addDay()) {
                if ($date->lt($schedule->start_date)) {
                    $this->addSkip($report, $date, 'before_start_date', 'Trước ngày bắt đầu lịch.');

                    continue;
                }

                if (! $this->matchesFrequency($schedule, $date)) {
                    continue;
                }

                [$departureAt, $arrivalAt] = $this->buildDateTimes($schedule, $date);

                if ($departureAt->lte(now())) {
                    $this->addSkip($report, $date, 'departure_in_past', 'Giờ khởi hành đã qua.');

                    continue;
                }

                if ($this->tripAlreadyExists($schedule, $departureAt)) {
                    $this->addSkip($report, $date, 'duplicate', 'Đã có chuyến cùng lịch và giờ khởi hành.', $departureAt);

                    continue;
                }

                if ($this->busHasOverlap($schedule->bus_id, $departureAt, $arrivalAt)) {
                    $this->addSkip($report, $date, 'bus_overlap', 'Xe bị trùng lịch với chuyến scheduled/departed khác.', $departureAt);

                    continue;
                }

                if ($dryRun) {
                    $report['generated'][] = [
                        'dry_run' => true,
                        'date' => $date->toDateString(),
                        'departure_time' => $departureAt->toDateTimeString(),
                        'arrival_time' => $arrivalAt->toDateTimeString(),
                        'code' => $this->previewTripCode($route->id, $departureAt),
                    ];
                    $report['generated_count']++;
                    $maxGeneratedDate = $date->copy();

                    continue;
                }

                $trip = Trip::create([
                    'route_id' => $schedule->route_id,
                    'bus_id' => $schedule->bus_id,
                    'trip_schedule_id' => $schedule->id,
                    'code' => $this->generateTripCode($route->id, $departureAt),
                    'departure_time' => $departureAt,
                    'arrival_time' => $arrivalAt,
                    'base_price' => $schedule->base_price,
                    'status' => 'scheduled',
                    'trip_type' => 'routine',
                ]);

                $seatCount = $this->tripSeatGenerationService->generateForTrip($trip);

                $report['generated'][] = [
                    'trip_id' => $trip->id,
                    'code' => $trip->code,
                    'departure_time' => $trip->departure_time->toDateTimeString(),
                    'arrival_time' => $trip->arrival_time->toDateTimeString(),
                    'generated_seat_count' => $seatCount,
                ];
                $report['generated_count']++;
                $maxGeneratedDate = $date->copy();
            }

            if (! $dryRun && $report['generated_count'] > 0) {
                $schedule->update([
                    'last_generated_until' => $maxGeneratedDate->toDateString(),
                ]);
            }
        });

        return $report;
    }

    private function matchesFrequency(TripSchedule $schedule, Carbon $date): bool
    {
        if ($schedule->frequency === 'daily') {
            return true;
        }

        if ($schedule->frequency === 'weekly') {
            $days = $schedule->days_of_week ?? [];

            return in_array($date->isoWeekday(), $days, true);
        }

        return false;
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function buildDateTimes(TripSchedule $schedule, Carbon $date): array
    {
        $departureTime = $this->normalizeTimeString($schedule->departure_time);
        $arrivalTime = $this->normalizeTimeString($schedule->arrival_time);

        $departureAt = Carbon::parse($date->toDateString().' '.$departureTime);
        $arrivalAt = Carbon::parse($date->toDateString().' '.$arrivalTime);

        if ($arrivalAt->lte($departureAt)) {
            $arrivalAt->addDay();
        }

        return [$departureAt, $arrivalAt];
    }

    private function normalizeTimeString(mixed $time): string
    {
        if ($time instanceof Carbon) {
            return $time->format('H:i:s');
        }

        $str = (string) $time;

        return strlen($str) === 5 ? $str.':00' : $str;
    }

    private function tripAlreadyExists(TripSchedule $schedule, Carbon $departureAt): bool
    {
        return Trip::query()
            ->where('trip_schedule_id', $schedule->id)
            ->where('departure_time', $departureAt->toDateTimeString())
            ->exists();
    }

    /**
     * Cùng rule overlap như AdminTripController::ensureBusIsAvailable.
     */
    private function busHasOverlap(int $busId, Carbon $departureAt, Carbon $arrivalAt): bool
    {
        return Trip::query()
            ->where('bus_id', $busId)
            ->whereIn('status', ['scheduled', 'departed'])
            ->where(function ($query) use ($departureAt, $arrivalAt) {
                $query
                    ->where('departure_time', '<', $arrivalAt)
                    ->where('arrival_time', '>', $departureAt);
            })
            ->exists();
    }

    private function generateTripCode(int $routeId, Carbon $departureAt): string
    {
        $prefix = 'AUTO-'.$departureAt->format('Ymd').'-R'.$routeId;

        do {
            $code = $prefix.'-'.strtoupper(Str::random(5));
        } while (Trip::where('code', $code)->exists());

        return $code;
    }

    private function previewTripCode(int $routeId, Carbon $departureAt): string
    {
        return 'AUTO-'.$departureAt->format('Ymd').'-R'.$routeId.'-XXXXX';
    }

    private function addSkip(
        array &$report,
        ?Carbon $date,
        string $reason,
        string $message,
        ?Carbon $departureAt = null
    ): void {
        $item = [
            'reason' => $reason,
            'message' => $message,
        ];

        if ($date) {
            $item['date'] = $date->toDateString();
        }

        if ($departureAt) {
            $item['departure_time'] = $departureAt->toDateTimeString();
        }

        $report['skipped'][] = $item;
        $report['skipped_count']++;
    }
}
