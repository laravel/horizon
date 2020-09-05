<?php

namespace Laravel\Horizon\Tests\Feature;

use Laravel\Horizon\Contracts\TagRepository;
use Laravel\Horizon\Tests\IntegrationTest;

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
        $this->assertSame('49', $results[0]);
        $this->assertSame('25', $results[24]);

        $results = $repo->paginate('tag', last(array_keys($results)) + 1, 25);

        $this->assertCount(25, $results);
        $this->assertSame('24', $results[25]);
        $this->assertSame('0', $results[49]);
    }
}
