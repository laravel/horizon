<?php

namespace Laravel\Horizon\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Laravel\Horizon\Enums\JobReferenceType;

class HorizonJobReference extends Model
{
    use Prunable;

    public int $prunableChunkSize = 1000;

    protected $guarded = [];

    protected $casts = [
        'type' => JobReferenceType::class,
        'score' => 'integer',
    ];

    public function prunable()
    {
        $retentions = [
            JobReferenceType::Recent->value => (int) config('horizon.trim.recent', 60),
            JobReferenceType::Pending->value => (int) config('horizon.trim.pending', 60),
            JobReferenceType::Completed->value => (int) config('horizon.trim.completed', 60),
            JobReferenceType::Silenced->value => (int) config('horizon.trim.completed', 60),
            JobReferenceType::Failed->value => (int) config('horizon.trim.failed', 10080),
            JobReferenceType::RecentFailed->value => (int) config('horizon.trim.recent_failed', (int) config('horizon.trim.failed', 10080)),
            JobReferenceType::Monitored->value => (int) config('horizon.trim.monitored', 10080),
        ];

        return static::query()->where(function ($query) use ($retentions) {
            foreach ($retentions as $type => $minutes) {
                $query->orWhere(function ($q) use ($type, $minutes) {
                    $q->where('type', $type)
                        ->where('score', '<', CarbonImmutable::now()->subMinutes($minutes)->getTimestamp() * 1_000_000);
                });
            }
        });
    }
}
