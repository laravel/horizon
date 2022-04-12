<?php

namespace Laravel\Horizon\Tests\Feature;

use Laravel\Horizon\Tags;
use Laravel\Horizon\Tests\Feature\Fakes\User;
use Laravel\Horizon\Tests\Feature\Jobs\TaggedJob;
use Laravel\Horizon\Tests\IntegrationTest;

class TagEnrichmentTest extends IntegrationTest
{
    /**
     * @requires PHP 8.0
     */
    public function test_can_collect_additional_tags()
    {
        $model = User::make([
            'id' => 1,
            'name' => 'John Doe',
        ]);

        $job = new TaggedJob(
            'bar',
            $model,
            ['create' => true],
            collect(['count' => 10])
        );

        $tags = Tags::for($job);

        $this->assertEquals([
            'Laravel\Horizon\Tests\Feature\Fakes\User:1',
            'foo:bar',
            'user:john-doe',
            'data:true',
            'posts:10',
        ], $tags);
    }
}
