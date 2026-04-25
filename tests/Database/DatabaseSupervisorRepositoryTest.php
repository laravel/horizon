<?php

namespace Laravel\Horizon\Tests\Database;

use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\Tests\Database\Fakes\SupervisorWithFakePool as Supervisor;
use Laravel\Horizon\SupervisorOptions;

class DatabaseSupervisorRepositoryTest extends DatabaseTestCase
{
    public function test_supervisor_information_can_be_persisted_and_retrieved()
    {
        $repository = resolve(SupervisorRepository::class);

        $repository->update(new Supervisor($this->makeOptions('foo:supervisor-1')));

        $record = $repository->find('foo:supervisor-1');

        $this->assertSame('foo:supervisor-1', $record->name);
        $this->assertSame('foo', $record->master);
        $this->assertSame('running', $record->status);
        $this->assertSame(['redis:default' => 0], $record->processes);
        $this->assertSame('redis', $record->options['connection']);
    }

    public function test_paused_supervisors_are_persisted_with_paused_status()
    {
        $repository = resolve(SupervisorRepository::class);

        $supervisor = new Supervisor($this->makeOptions('foo:supervisor-1'));
        $supervisor->working = false;

        $repository->update($supervisor);

        $this->assertSame('paused', $repository->find('foo:supervisor-1')->status);
    }

    public function test_names_returns_active_supervisors_only()
    {
        $repository = resolve(SupervisorRepository::class);

        $repository->update(new Supervisor($this->makeOptions('foo:supervisor-1')));
        $repository->update(new Supervisor($this->makeOptions('foo:supervisor-2')));

        $this->assertEqualsCanonicalizing(
            ['foo:supervisor-1', 'foo:supervisor-2'],
            $repository->names()
        );
    }

    public function test_supervisors_can_be_forgotten()
    {
        $repository = resolve(SupervisorRepository::class);

        $repository->update(new Supervisor($this->makeOptions('foo:supervisor-1')));
        $repository->update(new Supervisor($this->makeOptions('foo:supervisor-2')));

        $repository->forget('foo:supervisor-1');

        $this->assertNull($repository->find('foo:supervisor-1'));
        $this->assertNotNull($repository->find('foo:supervisor-2'));
    }

    public function test_longest_active_timeout_returns_largest_timeout_across_supervisors()
    {
        $repository = resolve(SupervisorRepository::class);

        $first = $this->makeOptions('foo:supervisor-1');
        $first->timeout = 60;

        $second = $this->makeOptions('foo:supervisor-2');
        $second->timeout = 120;

        $repository->update(new Supervisor($first));
        $repository->update(new Supervisor($second));

        $this->assertSame(120, $repository->longestActiveTimeout());
    }

    public function test_expired_supervisors_can_be_flushed()
    {
        $repository = resolve(SupervisorRepository::class);

        $repository->update(new Supervisor($this->makeOptions('foo:supervisor-1')));

        $this->app['db']->connection()->table('horizon_supervisors')
            ->where('name', 'foo:supervisor-1')
            ->update(['expires_at' => now()->subSeconds(60)->getTimestamp()]);

        $repository->flushExpired();

        $this->assertNull($repository->find('foo:supervisor-1'));
    }

    /**
     * Build supervisor options for the given name.
     *
     * @param  string  $name
     * @return \Laravel\Horizon\SupervisorOptions
     */
    protected function makeOptions($name)
    {
        return new SupervisorOptions($name, 'redis', 'default');
    }
}
