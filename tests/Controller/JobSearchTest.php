<?php

namespace Laravel\Horizon\Tests\Controller;

use Illuminate\Support\Facades\Queue;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Tests\ControllerTest;
use Laravel\Horizon\Tests\Feature\Jobs\BasicJob;
use Laravel\Horizon\Tests\Feature\Jobs\FailingJob;
use Laravel\Horizon\Tests\Feature\Jobs\SearchableJob;

class JobSearchTest extends ControllerTest
{
    public function test_pending_jobs_can_be_searched_by_payload()
    {
        $match = Queue::push(BasicJob::class, ['order' => 'ORDER-123']);
        Queue::push(BasicJob::class, ['order' => 'ORDER-456']);

        $response = $this->actingAs(new Fakes\User)
            ->getJson('/horizon/api/jobs/pending?search=order-123')
            ->assertOk();

        $this->assertSame([$match], array_column($response->json('jobs'), 'id'));
        $this->assertNull($response->json('next'));
    }

    public function test_jobs_can_be_searched_by_name_and_queue()
    {
        Queue::push(BasicJob::class);
        $onOtherQueue = Queue::push(FailingJob::class, '', 'emails');

        $this->actingAs(new Fakes\User)
            ->getJson('/horizon/api/jobs/pending?search=emails')
            ->assertJsonCount(1, 'jobs')
            ->assertJsonPath('jobs.0.id', $onOtherQueue);

        $this->actingAs(new Fakes\User)
            ->getJson('/horizon/api/jobs/pending?search=basicjob')
            ->assertJsonCount(1, 'jobs')
            ->assertJsonPath('jobs.0.name', BasicJob::class);
    }

    public function test_completed_jobs_can_be_searched()
    {
        $match = Queue::push(new SearchableJob('customer-taylor'));
        Queue::push(new SearchableJob('customer-abigail'));

        $this->work(2);

        $this->actingAs(new Fakes\User)
            ->getJson('/horizon/api/jobs/completed?search=TAYLOR')
            ->assertJsonCount(1, 'jobs')
            ->assertJsonPath('jobs.0.id', $match)
            ->assertJsonPath('jobs.0.status', 'completed');
    }

    public function test_failed_jobs_can_be_searched_by_exception()
    {
        $failed = Queue::push(new SearchableJob('first', fails: true));
        Queue::push(new FailingJob);

        $this->work(2);

        $this->actingAs(new Fakes\User)
            ->getJson('/horizon/api/jobs/failed?search=gateway+unavailable')
            ->assertJsonCount(1, 'jobs')
            ->assertJsonPath('jobs.0.id', $failed);
    }

    public function test_search_results_are_paginated_with_a_cursor()
    {
        $ids = collect(range(1, 60))->map(fn ($i) => Queue::push(BasicJob::class, ['reference' => 'needle-'.$i]));

        $first = $this->actingAs(new Fakes\User)
            ->getJson('/horizon/api/jobs/pending?search=needle')
            ->assertJsonCount(50, 'jobs');

        $this->assertNotNull($first->json('next'));

        $second = $this->actingAs(new Fakes\User)
            ->getJson('/horizon/api/jobs/pending?search=needle&starting_at='.$first->json('next'))
            ->assertJsonCount(10, 'jobs');

        $this->assertNull($second->json('next'));

        $this->assertEqualsCanonicalizing(
            $ids->all(),
            array_merge(array_column($first->json('jobs'), 'id'), array_column($second->json('jobs'), 'id'))
        );
    }

    public function test_search_stops_after_scanning_the_limit()
    {
        $repository = app(JobRepository::class);
        $repository->searchScanLimit = 20;

        collect(range(1, 30))->each(fn () => Queue::push(BasicJob::class));

        $response = $this->actingAs(new Fakes\User)
            ->getJson('/horizon/api/jobs/pending?search=no-such-thing')
            ->assertJsonCount(0, 'jobs');

        $this->assertSame(19, $response->json('next'));

        $this->actingAs(new Fakes\User)
            ->getJson('/horizon/api/jobs/pending?search=no-such-thing&starting_at=19')
            ->assertJsonCount(0, 'jobs')
            ->assertJsonPath('next', null);
    }

    public function test_serialized_property_names_are_not_matched()
    {
        Queue::push(new SearchableJob('abc-123'));

        $this->actingAs(new Fakes\User)
            ->getJson('/horizon/api/jobs/pending?search=reference')
            ->assertJsonCount(0, 'jobs');

        $this->actingAs(new Fakes\User)
            ->getJson('/horizon/api/jobs/pending?search=fails')
            ->assertJsonCount(0, 'jobs');

        $this->actingAs(new Fakes\User)
            ->getJson('/horizon/api/jobs/pending?search=abc-123')
            ->assertJsonCount(1, 'jobs');
    }

    public function test_jobs_can_be_searched_by_tag()
    {
        $tagged = Queue::push(new BasicJob);
        Queue::push(new SearchableJob('untagged'));

        $this->actingAs(new Fakes\User)
            ->getJson('/horizon/api/jobs/pending?search=second')
            ->assertJsonCount(1, 'jobs')
            ->assertJsonPath('jobs.0.id', $tagged);
    }

    public function test_listing_without_search_is_unchanged()
    {
        Queue::push(BasicJob::class);
        Queue::push(BasicJob::class);

        $this->actingAs(new Fakes\User)
            ->getJson('/horizon/api/jobs/pending')
            ->assertJsonCount(2, 'jobs')
            ->assertJsonPath('total', 2)
            ->assertJsonMissingPath('next');
    }
}
