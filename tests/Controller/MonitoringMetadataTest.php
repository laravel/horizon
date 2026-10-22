<?php

namespace Laravel\Horizon\Tests\Controller;

use Illuminate\Support\Carbon;
use Laravel\Horizon\Contracts\TagRepository;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\Jobs\MonitorTag;
use Laravel\Horizon\Jobs\StopMonitoringTag;
use Laravel\Horizon\Repositories\RedisMonitoringMetadataRepository;
use Laravel\Horizon\Tests\ControllerTest;

class MonitoringMetadataTest extends ControllerTest
{
    protected function signIn($id = 42, $name = 'Taylor')
    {
        return $this->actingAs((new Fakes\User)->forceFill(['id' => $id, 'name' => $name]));
    }

    protected function metadata($tag)
    {
        return resolve(RedisMonitoringMetadataRepository::class)->get([$tag])[$tag];
    }

    public function test_creation_records_server_identity_and_time_and_trims_input()
    {
        $this->signIn()->postJson('/horizon/api/monitoring', [
            'tag' => ' tenant:42 ',
            'reason' => ' Investigate retries ',
            'created_by' => ['id' => 999, 'name' => 'Spoofed'],
            'created_at' => '2000-01-01',
        ])->assertSuccessful();

        $this->assertTrue(Horizon::scriptVariables()['canManageMonitoring']);
        $metadata = $this->metadata('tenant:42');
        $this->assertSame('Investigate retries', $metadata['reason']);
        $this->assertSame(['id' => 42, 'name' => 'Taylor'], $metadata['created_by']);
        $this->assertSame(now()->toDateString(), substr($metadata['created_at'], 0, 10));
        $this->getJson('/horizon/api/monitoring')->assertJsonFragment($metadata);
    }

    public function test_creation_time_is_relative_and_retains_the_full_timestamp()
    {
        $this->travelTo(Carbon::parse('2026-10-22 12:00:00'));
        $this->signIn()->postJson('/horizon/api/monitoring', ['tag' => 'first', 'reason' => 'Debug'])
            ->assertSuccessful();
        $createdAt = $this->metadata('first')['created_at'];

        $this->travel(5)->minutes();
        $this->getJson('/horizon/api/monitoring')->assertJsonFragment([
            'created_at' => $createdAt,
            'created_at_human' => '5 minutes ago',
        ]);

        $this->travel(5)->minutes();
        $this->getJson('/horizon/api/monitoring')->assertJsonFragment([
            'created_at' => $createdAt,
            'created_at_human' => '10 minutes ago',
        ]);
    }

    public function test_creation_requires_a_tag_and_nonblank_reason_with_at_most_500_characters()
    {
        $this->signIn();

        foreach ([null, '', '   ', [], str_repeat('x', 501)] as $reason) {
            $this->postJson('/horizon/api/monitoring', ['tag' => 'first', 'reason' => $reason])
                ->assertUnprocessable()->assertJsonValidationErrors('reason');
        }

        foreach ([null, '', '  ', []] as $tag) {
            $this->postJson('/horizon/api/monitoring', ['tag' => $tag, 'reason' => 'Debug'])
                ->assertUnprocessable()->assertJsonValidationErrors('tag');
        }

        $this->assertSame([], resolve(TagRepository::class)->monitoring());
        $this->postJson('/horizon/api/monitoring', ['tag' => 'first', 'reason' => str_repeat('x', 500)])
            ->assertSuccessful();
    }

    public function test_duplicate_creation_preserves_original_metadata()
    {
        $this->signIn()->postJson('/horizon/api/monitoring', ['tag' => 'first', 'reason' => 'Original'])
            ->assertSuccessful();
        $original = $this->metadata('first');

        $this->signIn(43, 'Someone else')->postJson('/horizon/api/monitoring', ['tag' => 'first', 'reason' => 'Changed'])
            ->assertSuccessful();

        $this->assertSame($original, $this->metadata('first'));
    }

    public function test_reason_edit_preserves_creator_and_creation_time_and_supports_slashes()
    {
        $tag = 'App/Jobs/Import:42';
        $url = '/horizon/api/monitoring/'.rawurlencode($tag);
        $this->signIn()->postJson('/horizon/api/monitoring', ['tag' => $tag, 'reason' => 'Original'])
            ->assertSuccessful();
        $original = $this->metadata($tag);

        $this->signIn(43, 'Editor')->patchJson($url, ['reason' => ' Updated ', 'created_by' => ['id' => 43]])
            ->assertSuccessful();

        $this->assertSame(array_replace($original, ['reason' => 'Updated']), $this->metadata($tag));
        $this->deleteJson($url)->assertSuccessful();
        $this->assertSame([], resolve(TagRepository::class)->monitoring());
        $this->assertSame(['reason' => null, 'created_by' => null, 'created_at' => null], $this->metadata($tag));
    }

    public function test_editing_legacy_monitor_keeps_unknown_history()
    {
        resolve(TagRepository::class)->monitor('legacy');
        $unknown = ['reason' => null, 'created_by' => null, 'created_at' => null];
        $this->getJson('/horizon/api/monitoring')->assertJsonFragment($unknown + ['created_at_human' => null]);

        $this->signIn()->patchJson('/horizon/api/monitoring/legacy', ['reason' => 'Still needed'])
            ->assertSuccessful();
        $this->assertSame(array_replace($unknown, ['reason' => 'Still needed']), $this->metadata('legacy'));

        $this->postJson('/horizon/api/monitoring', ['tag' => 'legacy', 'reason' => 'Duplicate'])
            ->assertSuccessful();
        $this->assertNull($this->metadata('legacy')['created_at']);
    }

    public function test_edit_requires_an_existing_monitor_and_valid_reason()
    {
        $this->signIn()->patchJson('/horizon/api/monitoring/missing', ['reason' => 'Debug'])->assertNotFound();
        dispatch(new MonitorTag('first'));

        foreach ([' ', [], str_repeat('x', 501)] as $reason) {
            $this->patchJson('/horizon/api/monitoring/first', ['reason' => $reason])
                ->assertUnprocessable()->assertJsonValidationErrors('reason');
        }
    }

    public function test_guest_can_view_but_cannot_mutate_monitors()
    {
        dispatch(new MonitorTag('first'));
        $this->getJson('/horizon/api/monitoring')->assertSuccessful();
        $this->assertFalse(Horizon::scriptVariables()['canManageMonitoring']);
        $this->postJson('/horizon/api/monitoring', ['tag' => 'second', 'reason' => 'Debug'])->assertForbidden();
        $this->patchJson('/horizon/api/monitoring/first', ['reason' => 'Debug'])->assertForbidden();
        $this->deleteJson('/horizon/api/monitoring/first')->assertForbidden();
        $this->assertSame(['first'], resolve(TagRepository::class)->monitoring());
    }

    public function test_programmatic_stop_cleans_metadata_and_recreation_uses_new_creator()
    {
        $this->signIn()->postJson('/horizon/api/monitoring', ['tag' => 'first', 'reason' => 'Original'])
            ->assertSuccessful();
        $this->patchJson('/horizon/api/monitoring/first', ['reason' => 'Edited'])->assertSuccessful();
        dispatch(new StopMonitoringTag('first'));

        $this->signIn(43, 'New owner')->postJson('/horizon/api/monitoring', ['tag' => 'first', 'reason' => 'New'])
            ->assertSuccessful();
        $this->assertSame('New', $this->metadata('first')['reason']);
        $this->assertSame(['id' => 43, 'name' => 'New owner'], $this->metadata('first')['created_by']);
    }

    public function test_programmatic_monitor_has_unknown_creator_and_known_creation_time()
    {
        dispatch(new MonitorTag('first'));
        $metadata = $this->metadata('first');
        $this->assertNull($metadata['reason']);
        $this->assertNull($metadata['created_by']);
        $this->assertNotNull($metadata['created_at']);
    }
}
