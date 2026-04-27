<?php

namespace Laravel\Horizon\Tests\Database;

use Carbon\CarbonImmutable;
use Laravel\Horizon\Contracts\TagRepository;

class DatabaseTagRepositoryTest extends DatabaseTestCase
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

    public function test_tags_can_be_added_and_counted()
    {
        $repo = resolve(TagRepository::class);

        $repo->add('1', ['email', 'newsletter']);
        $repo->add('2', ['email']);

        $this->assertSame(2, $repo->count('email'));
        $this->assertSame(1, $repo->count('newsletter'));
    }

    public function test_duplicate_tags_are_deduplicated_when_adding()
    {
        $repo = resolve(TagRepository::class);

        $repo->add('1', ['email', 'email', 'newsletter', 'newsletter']);

        $this->assertSame(1, $repo->count('email'));
        $this->assertSame(1, $repo->count('newsletter'));
    }

    public function test_tags_can_be_monitored_and_unmonitored()
    {
        $repo = resolve(TagRepository::class);

        $repo->monitor('email');
        $repo->monitor('newsletter');

        $this->assertSame(['email'], $repo->monitored(['email', 'sms']));
        $this->assertEqualsCanonicalizing(['email', 'newsletter'], $repo->monitoring());

        $repo->stopMonitoring('email');

        $this->assertSame(['newsletter'], $repo->monitoring());
    }

    public function test_jobs_can_be_forgotten_for_a_tag()
    {
        $repo = resolve(TagRepository::class);

        $repo->add('1', ['email']);
        $repo->add('2', ['email']);
        $repo->add('3', ['email']);

        $repo->forgetJobs('email', ['1', '2']);

        $this->assertSame(['3'], $repo->jobs('email'));
    }

    public function test_a_tag_can_be_forgotten_entirely()
    {
        $repo = resolve(TagRepository::class);

        $repo->add('1', ['email']);
        $repo->add('2', ['email']);

        $repo->forget('email');

        $this->assertSame(0, $repo->count('email'));
    }

    public function test_expired_tags_can_be_trimmed()
    {
        $repo = resolve(TagRepository::class);

        $repo->addTemporary(1, '1', ['email']);
        $repo->add('2', ['email']);

        CarbonImmutable::setTestNow(CarbonImmutable::now()->addMinutes(2));

        $repo->trimExpired();

        $this->assertSame(['2'], $repo->jobs('email'));

        CarbonImmutable::setTestNow();
    }
}
