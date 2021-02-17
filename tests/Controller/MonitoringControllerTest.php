<?php

namespace Laravel\Horizon\Tests\Controller;

use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\TagRepository;
use Laravel\Horizon\JobPayload;
use Mockery;

class MonitoringControllerTest extends AbstractControllerTest
{
    public function test_monitored_tags_and_job_counts_are_returned()
    {
        $tags = Mockery::mock(TagRepository::class);

        $tags->shouldReceive('monitoring')->andReturn(['first', 'second']);
        $tags->shouldReceive('count')->with('first')->andReturn(1);
        $tags->shouldReceive('count')->with('failed:first')->andReturn(1);
        $tags->shouldReceive('count')->with('second')->andReturn(2);
        $tags->shouldReceive('count')->with('failed:second')->andReturn(2);

        $this->app->instance(TagRepository::class, $tags);

        $response = $this->actingAs(new Fakes\User)
                    ->get('/horizon/api/monitoring');

        $response->assertJson([
            ['tag' => 'first', 'count' => 2],
            ['tag' => 'second', 'count' => 4],
        ]);
    }

    public function test_monitored_jobs_can_be_paginated_by_tag()
    {
        $tags = resolve(TagRepository::class);
        $jobs = resolve(JobRepository::class);

        // Add monitored jobs...
        for ($i = 0; $i < 50; $i++) {
            $tags->add((string) $i, ['tag']);

            $jobs->remember('redis', 'default', new JobPayload(
                json_encode(['id' => $i, 'displayName' => 'foo'])
            ));
        }

        // Paginate first set...
        $response = $this->actingAs(new Fakes\User)
                    ->get('/horizon/api/monitoring/tag');

        $results = $response->original['jobs'];

        $this->assertCount(25, $results);
        $this->assertSame('49', $results[0]->id);
        $this->assertSame('25', $results[24]->id);

        // Paginate second set...
        $response = $this->actingAs(new Fakes\User)
                    ->get('/horizon/api/monitoring/tag?starting_at=25');

        $results = $response->original['jobs'];

        $this->assertCount(25, $results);
        $this->assertSame('24', $results[0]->id);
        $this->assertSame('0', $results[24]->id);
        $this->assertSame('25', $results[0]->index);
        $this->assertSame(49, $results[24]->index);
    }

    public function test_can_paginate_where_jobs_dont_exist()
    {
        $tags = resolve(TagRepository::class);

        for ($i = 0; $i < 50; $i++) {
            $tags->add((string) $i, ['tag']);
        }

        $response = $this->actingAs(new Fakes\User)
                    ->get('/horizon/api/monitoring/tag?starting_at=1000');

        $this->assertCount(0, $response->original['jobs']);
    }

    public function test_can_start_monitoring_tags()
    {
        $tags = resolve(TagRepository::class);

        $this->actingAs(new Fakes\User)
             ->post('/horizon/api/monitoring', ['tag' => 'taylor']);

        $this->assertEquals(['taylor'], $tags->monitoring());
    }

    public function test_can_stop_monitoring_tags()
    {
        $tags = resolve(TagRepository::class);
        $jobs = resolve(JobRepository::class);

        // Add monitored jobs...
        for ($i = 0; $i < 50; $i++) {
            $tags->add((string) $i, ['tag']);

            $jobs->remember('redis', 'default', new JobPayload(
                json_encode(['id' => $i, 'displayName' => 'foo'])
            ));
        }

        $this->actingAs(new Fakes\User)
             ->delete('/horizon/api/monitoring/tag');

        // Ensure monitored jobs were deleted...
        $response = $this->actingAs(new Fakes\User)
                    ->get('/horizon/api/monitoring/tag');

        $results = $response->original['jobs'];

        $this->assertCount(0, $results);
    }
}
