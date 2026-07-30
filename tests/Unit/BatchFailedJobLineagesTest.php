<?php

namespace Laravel\Horizon\Tests\Unit;

use Laravel\Horizon\Batches\BatchFailedJobLineages;
use Laravel\Horizon\Tests\UnitTest;

class BatchFailedJobLineagesTest extends UnitTest
{
    public function test_it_collapses_original_and_retry_failures_into_one_lineage_row()
    {
        $lineages = new BatchFailedJobLineages;

        $summary = $lineages->summarize([
            (object) [
                'id' => 'original-failed',
                'name' => 'App\\Jobs\\DemoFailingJob',
                'payload' => json_encode([
                    'displayName' => 'App\\Jobs\\DemoFailingJob',
                    'attempts' => 1,
                ]),
                'retried_by' => json_encode([
                    ['id' => 'retry-failed', 'status' => 'failed', 'retried_at' => 200],
                ]),
                'failed_at' => 100,
                'reserved_at' => 90,
                'index' => 0,
            ],
            (object) [
                'id' => 'retry-failed',
                'name' => 'App\\Jobs\\DemoFailingJob',
                'payload' => json_encode([
                    'displayName' => 'App\\Jobs\\DemoFailingJob',
                    'attempts' => 1,
                    'retry_of' => 'original-failed',
                ]),
                'retried_by' => null,
                'failed_at' => 200,
                'reserved_at' => 190,
                'index' => 1,
            ],
        ], ['original-failed', 'retry-failed']);

        $this->assertTrue($summary['complete']);
        $this->assertCount(1, $summary['rows']);
        $this->assertSame('retry-failed', $summary['rows'][0]['id']);
        $this->assertSame(2, $summary['rows'][0]['attempts']);
        $this->assertTrue($summary['rows'][0]['attemptsComplete']);
    }

    public function test_it_keeps_distinct_lineages_separate()
    {
        $lineages = new BatchFailedJobLineages;

        $summary = $lineages->summarize([
            (object) [
                'id' => 'failed-a',
                'name' => 'App\\Jobs\\A',
                'payload' => json_encode(['displayName' => 'App\\Jobs\\A', 'attempts' => 1]),
                'retried_by' => null,
                'failed_at' => 100,
                'index' => 0,
            ],
            (object) [
                'id' => 'failed-b',
                'name' => 'App\\Jobs\\B',
                'payload' => json_encode(['displayName' => 'App\\Jobs\\B', 'attempts' => 1]),
                'retried_by' => null,
                'failed_at' => 110,
                'index' => 1,
            ],
        ], ['failed-a', 'failed-b']);

        $this->assertTrue($summary['complete']);
        $this->assertCount(2, $summary['rows']);
        $this->assertSame(['failed-b', 'failed-a'], array_column($summary['rows'], 'id'));
    }

    public function test_it_omits_lineages_that_have_a_completed_retry()
    {
        $lineages = new BatchFailedJobLineages;

        $summary = $lineages->summarize([
            (object) [
                'id' => 'original-failed',
                'name' => 'App\\Jobs\\DemoFailingJob',
                'payload' => json_encode([
                    'displayName' => 'App\\Jobs\\DemoFailingJob',
                    'attempts' => 1,
                ]),
                'retried_by' => json_encode([
                    ['id' => 'retry-completed', 'status' => 'completed', 'retried_at' => 200],
                ]),
                'failed_at' => 100,
                'index' => 0,
            ],
        ], ['original-failed']);

        $this->assertTrue($summary['complete']);
        $this->assertSame([], $summary['rows']);
    }
}
