<?php

namespace Laravel\Horizon\Tests\Feature;

use Laravel\Horizon\Tests\IntegrationTest;
use Laravel\Horizon\Contracts\TagRepository;

class TagRepositoryTest extends IntegrationTest
{
    public function test_pagination_of_job_ids_can_be_accomplished()
    {
        $repo = resolve(TagRepository::class);

        for ($i = 0; $i < 50; $i++) {
            $repo->add((string) $i, ['tag']);
        }

        $results = $repo->paginate('tag', 0, 25);

        $this->assertCount(25, $results);
        $this->assertEquals(49, $results[0]);
        $this->assertEquals(25, $results[24]);

        $results = $repo->paginate('tag', last(array_keys($results)) + 1, 25);

        $this->assertCount(25, $results);
        $this->assertEquals(24, $results[25]);
        $this->assertEquals(0, $results[49]);
    }
}
