<?php

namespace Laravel\Horizon\Tests\Feature\Jobs;

use Illuminate\Support\Collection;
use Laravel\Horizon\Attributes\Tag;
use Laravel\Horizon\Tests\Feature\Fakes\User;

class TaggedJob
{
    public function __construct(
        #[Tag]
        public $foo,

        #[Tag('name')]
        public User $user,

        #[Tag('create')]
        public array $data,

        #[Tag('count')]
        public Collection $posts,
    )
    {
        //
    }

    public function handle()
    {
        //
    }
}
