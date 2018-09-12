<?php

namespace Laravel\Horizon\Tests\Feature\Jobs;

use Illuminate\Queue\InteractsWithQueue;

class ConditionallyFailingJob
{
    use InteractsWithQueue;

    /**
     * @var array
     */
    protected $tags;

    /**
     * @param array $tags
     */
    public function __construct(array $tags = ['first'])
    {
        $this->tags = $tags;
    }

    public function handle()
    {
        if (isset($_SERVER['horizon.fail'])) {
            return $this->fail();
        }
    }

    public function tags()
    {
        return $this->tags;
    }
}
