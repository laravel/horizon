<?php

namespace Laravel\Horizon\Tests\Unit;

use Illuminate\Filesystem\Filesystem;
use Laravel\Horizon\Support\ComposerAssetHook;
use Laravel\Horizon\Support\ComposerAssetHookResult;
use Laravel\Horizon\Tests\UnitTest;
use Mockery;
use RuntimeException;

class ComposerAssetHookTest extends UnitTest
{
    private string $workspace;

    private Filesystem $files;

    private ComposerAssetHook $hook;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = new Filesystem;
        $this->hook = new ComposerAssetHook($this->files);
        $this->workspace = sys_get_temp_dir().'/horizon-composer-'.bin2hex(random_bytes(8));
        $this->files->ensureDirectoryExists($this->workspace);
    }

    protected function tearDown(): void
    {
        $this->files->deleteDirectory($this->workspace);

        parent::tearDown();
    }

    public function test_appends_hook_after_existing_post_autoload_dump_entries()
    {
        $path = $this->composerPath([
            'name' => 'acme/app',
            'scripts' => [
                'post-autoload-dump' => [
                    '@php artisan package:discover --ansi',
                ],
                'test' => 'phpunit',
            ],
            'require' => [
                'php' => '^8.2',
            ],
        ]);

        $this->assertSame(ComposerAssetHookResult::Added, $this->hook->ensure($path));

        $composer = json_decode($this->files->get($path), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame([
            '@php artisan package:discover --ansi',
            ComposerAssetHook::SCRIPT,
        ], $composer['scripts']['post-autoload-dump']);
        $this->assertSame('phpunit', $composer['scripts']['test']);
        $this->assertSame('acme/app', $composer['name']);
        $this->assertSame(['php' => '^8.2'], $composer['require']);
    }

    public function test_creates_post_autoload_dump_when_scripts_are_missing()
    {
        $path = $this->composerPath(['name' => 'acme/app']);

        $this->assertSame(ComposerAssetHookResult::Added, $this->hook->ensure($path));

        $composer = json_decode($this->files->get($path), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame([ComposerAssetHook::SCRIPT], $composer['scripts']['post-autoload-dump']);
    }

    public function test_creates_post_autoload_dump_when_scripts_map_exists_without_that_event()
    {
        $path = $this->composerPath([
            'scripts' => [
                'test' => 'phpunit',
            ],
        ]);

        $this->assertSame(ComposerAssetHookResult::Added, $this->hook->ensure($path));

        $composer = json_decode($this->files->get($path), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame([ComposerAssetHook::SCRIPT], $composer['scripts']['post-autoload-dump']);
        $this->assertSame('phpunit', $composer['scripts']['test']);
    }

    public function test_promotes_string_post_autoload_dump_to_list()
    {
        $path = $this->composerPath([
            'scripts' => [
                'post-autoload-dump' => '@php artisan package:discover --ansi',
            ],
        ]);

        $this->assertSame(ComposerAssetHookResult::Added, $this->hook->ensure($path));

        $composer = json_decode($this->files->get($path), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame([
            '@php artisan package:discover --ansi',
            ComposerAssetHook::SCRIPT,
        ], $composer['scripts']['post-autoload-dump']);
    }

    public function test_is_idempotent_when_hook_already_present()
    {
        $path = $this->composerPath([
            'scripts' => [
                'post-autoload-dump' => [
                    '@php artisan package:discover --ansi',
                    ComposerAssetHook::SCRIPT,
                ],
            ],
        ]);

        $before = $this->files->get($path);

        $this->assertSame(ComposerAssetHookResult::AlreadyPresent, $this->hook->ensure($path));
        $this->assertSame($before, $this->files->get($path));
    }

    public function test_returns_missing_when_composer_json_absent()
    {
        $this->assertSame(
            ComposerAssetHookResult::Missing,
            $this->hook->ensure($this->workspace.'/missing-composer.json'),
        );
    }

    public function test_returns_malformed_for_invalid_json()
    {
        $path = $this->workspace.'/composer.json';
        $this->files->put($path, '{not-json');

        $this->assertSame(ComposerAssetHookResult::Malformed, $this->hook->ensure($path));
        $this->assertSame('{not-json', $this->files->get($path));
    }

    public function test_returns_malformed_for_json_list_root()
    {
        $path = $this->composerPath(['not', 'an', 'object']);

        $this->assertSame(ComposerAssetHookResult::Malformed, $this->hook->ensure($path));
    }

    public function test_returns_malformed_when_scripts_is_a_list()
    {
        $path = $this->composerPath([
            'scripts' => ['phpunit', 'pint'],
        ]);

        $this->assertSame(ComposerAssetHookResult::Malformed, $this->hook->ensure($path));
    }

    public function test_returns_malformed_when_post_autoload_dump_is_associative()
    {
        $path = $this->composerPath([
            'scripts' => [
                'post-autoload-dump' => [
                    'first' => '@php artisan package:discover --ansi',
                ],
            ],
        ]);

        $this->assertSame(ComposerAssetHookResult::Malformed, $this->hook->ensure($path));
    }

    public function test_returns_malformed_when_post_autoload_dump_list_contains_non_strings()
    {
        $path = $this->composerPath([
            'scripts' => [
                'post-autoload-dump' => [
                    '@php artisan package:discover --ansi',
                    ['nested' => true],
                ],
            ],
        ]);

        $this->assertSame(ComposerAssetHookResult::Malformed, $this->hook->ensure($path));
    }

    public function test_returns_failed_when_composer_json_cannot_be_read()
    {
        $path = $this->workspace.'/composer.json';
        $this->files->put($path, '{}');

        $filesystem = Mockery::mock(Filesystem::class);
        $filesystem->shouldReceive('isFile')->with($path)->andReturn(true);
        $filesystem->shouldReceive('get')->with($path)->andThrow(new RuntimeException('read failed'));

        $hook = new ComposerAssetHook($filesystem);

        $this->assertSame(ComposerAssetHookResult::Failed, $hook->ensure($path));
    }

    public function test_returns_failed_when_composer_json_cannot_be_written()
    {
        $path = $this->workspace.'/composer.json';
        $payload = json_encode(['name' => 'acme/app'], JSON_THROW_ON_ERROR);

        $filesystem = Mockery::mock(Filesystem::class);
        $filesystem->shouldReceive('isFile')->with($path)->andReturn(true);
        $filesystem->shouldReceive('get')->with($path)->andReturn($payload);
        $filesystem->shouldReceive('put')->andReturn(false);
        $filesystem->shouldReceive('exists')->andReturn(false);

        $hook = new ComposerAssetHook($filesystem);

        $this->assertSame(ComposerAssetHookResult::Failed, $hook->ensure($path));
    }

    /**
     * @param  array<string, mixed>|list<mixed>  $payload
     */
    private function composerPath(array $payload): string
    {
        $path = $this->workspace.'/composer.json';
        $this->files->put(
            $path,
            json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n",
        );

        return $path;
    }
}
