<?php

namespace App\Console\Commands;

use App\Models\TripSchedule;
use App\Services\TripScheduleGenerationService;
use Illuminate\Console\Command;

class GenerateTripsFromSchedulesCommand extends Command
{
    protected $signature = 'trips:generate-from-schedules
                            {--days=14 : Số ngày tính từ hôm nay để sinh chuyến}
                            {--schedule_id= : Chỉ sinh cho một lịch cụ thể}
                            {--dry-run : Chỉ xem trước, không ghi database}';

    protected $description = 'Sinh trips từ các lịch chạy mẫu (trip_schedules) đang active';

    public function handle(TripScheduleGenerationService $service): int
    {
        $days = (int) $this->option('days');
        $scheduleId = $this->option('schedule_id');
        $dryRun = (bool) $this->option('dry-run');

        if ($days < 1 || $days > 60) {
            $this->error('Option --days phải từ 1 đến 60.');

            return self::FAILURE;
        }

        $query = TripSchedule::query()
            ->with(['route', 'bus'])
            ->where('is_active', true);

        if ($scheduleId) {
            $query->where('id', $scheduleId);
        }

        $schedules = $query->get();

        if ($schedules->isEmpty()) {
            $this->warn('Không có lịch chạy mẫu active nào để xử lý.');

            return self::SUCCESS;
        }

        $today = now()->startOfDay();
        $toDate = $today->copy()->addDays($days);

        if ($dryRun) {
            $this->info('*** DRY-RUN: không ghi database ***');
        }

        $totalGenerated = 0;
        $totalSkipped = 0;

        foreach ($schedules as $schedule) {
            $this->line('');
            $this->info("Schedule #{$schedule->id} — {$schedule->displayName()} [{$schedule->frequency}]");

            $report = $service->generateForSchedule(
                schedule: $schedule,
                fromDate: $today,
                toDate: $toDate,
                dryRun: $dryRun
            );

            $totalGenerated += $report['generated_count'];
            $totalSkipped += $report['skipped_count'];

            $this->line("  Generated: {$report['generated_count']}");
            $this->line("  Skipped:   {$report['skipped_count']}");

            foreach ($report['skipped'] as $skip) {
                $date = $skip['date'] ?? '—';
                $this->warn("    [skip] {$date} — {$skip['reason']}: {$skip['message']}");
            }

            foreach ($report['generated'] as $item) {
                $code = $item['code'] ?? ('trip #'.($item['trip_id'] ?? '?'));
                $dep = $item['departure_time'] ?? '';
                $this->line("    [ok] {$code} @ {$dep}");
            }
        }

        $this->line('');
        $this->info("Tổng: generated={$totalGenerated}, skipped={$totalSkipped}".($dryRun ? ' (dry-run)' : ''));

        return self::SUCCESS;
    }
}
