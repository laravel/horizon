<?php

namespace Laravel\Horizon\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Laravel\Horizon\Enums\MetricKind;

class HorizonMetricSnapshot extends Model
{
    use Prunable;

    public int $prunableChunkSize = 1000;

    protected $guarded = [];

    protected $casts = [
        'kind' => MetricKind::class,
        'throughput' => 'integer',
        'runtime' => 'float',
        'wait' => 'float',
        'recorded_at' => 'datetime',
    ];

    public function prunable()
    {
        $limits = [
            MetricKind::Job->value => (int) config('horizon.metrics.trim_snapshots.job', 24),
            MetricKind::Queue->value => (int) config('horizon.metrics.trim_snapshots.queue', 24),
        ];

        $cutoffs = [];

        static::query()
            ->select('key', 'kind')
            ->groupBy('key', 'kind')
            ->get()
            ->each(function ($row) use ($limits, &$cutoffs) {
                $kindValue = $row->kind instanceof MetricKind ? $row->kind->value : $row->kind;
                $limit = $limits[$kindValue] ?? 0;

                $cutoff = static::query()
                    ->where('key', $row->key)
                    ->orderBy('recorded_at', 'desc')
                    ->orderBy('id', 'desc')
                    ->skip(max($limit, 0))
                    ->take(1)
                    ->value('id');

                if ($cutoff !== null) {
                    $cutoffs[] = ['key' => $row->key, 'cutoff' => $cutoff];
                }
            });

        if (empty($cutoffs)) {
            return static::query()->whereRaw('1 = 0');
        }

        return static::query()->where(function ($query) use ($cutoffs) {
            foreach ($cutoffs as $entry) {
                $query->orWhere(function ($q) use ($entry) {
                    $q->where('key', $entry['key'])->where('id', '<=', $entry['cutoff']);
                });
            }
        });
    }
}
