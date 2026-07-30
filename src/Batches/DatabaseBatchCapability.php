<?php

declare(strict_types=1);

namespace Laravel\Horizon\Batches;

use Illuminate\Bus\BatchRepository;
use Illuminate\Bus\DatabaseBatchRepository;
use Throwable;

/**
 * Whether Laravel job-batch storage is usable for Horizon listing and navigation.
 *
 * Non-database repositories remain available. Database repositories require their
 * configured source table (queue.batching.table, default job_batches).
 */
final readonly class DatabaseBatchCapability
{
    public function __construct(private BatchRepository $repository)
    {
    }

    /**
     * Whether batch storage can be queried for dashboard and navigation counts.
     */
    public function available(): bool
    {
        return once(function (): bool {
            if (! $this->repository instanceof DatabaseBatchRepository) {
                return true;
            }

            try {
                return $this->repository
                    ->getConnection()
                    ->getSchemaBuilder()
                    ->hasTable($this->sourceTable());
            } catch (Throwable) {
                return false;
            }
        });
    }

    /**
     * Configured Laravel batch table name.
     */
    public function sourceTable(): string
    {
        try {
            $table = config('queue.batching.table', 'job_batches');
        } catch (Throwable) {
            return 'job_batches';
        }

        return is_string($table) && $table !== '' ? $table : 'job_batches';
    }
}
