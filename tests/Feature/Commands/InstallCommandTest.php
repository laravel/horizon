<?php

namespace Laravel\Horizon\Tests\Feature\Commands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Laravel\Horizon\Exceptions\UnsupportedDriverException;
use Laravel\Horizon\Tests\IntegrationTest;

class InstallCommandTest extends IntegrationTest
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! is_dir(app_path('Providers'))) {
            File::makeDirectory(app_path('Providers'), 0755, true);
        }

        File::put(
            app_path('Providers/HorizonServiceProvider.php'),
            "<?php\n\nnamespace App\Providers;\n\nclass HorizonServiceProvider {}\n"
        );
    }

    protected function tearDown(): void
    {
        foreach ([
            app_path('Providers/HorizonServiceProvider.php'),
            config_path('horizon.php'),
        ] as $file) {
            if (File::exists($file)) {
                File::delete($file);
            }
        }

        foreach (File::glob(database_path('migrations/*_create_horizon_tables.php')) as $file) {
            File::delete($file);
        }

        parent::tearDown();
    }

    public function test_install_with_redis_driver_publishes_provider_and_config_only()
    {
        $this->artisan('horizon:install', ['--driver' => 'redis'])->assertSuccessful();

        $this->assertTrue(File::exists(config_path('horizon.php')));

        $this->assertStringContainsString(
            "'driver' => env('HORIZON_DRIVER', 'redis')",
            File::get(config_path('horizon.php'))
        );

        $this->assertEmpty(
            File::glob(database_path('migrations/*_create_horizon_tables.php')),
            'Redis driver install must not publish Horizon database migrations.'
        );

        $this->assertFalse(
            Schema::hasTable('horizon_jobs'),
            'Redis driver install must not create Horizon database tables.'
        );
    }

    public function test_install_with_database_driver_publishes_migrations_rewrites_config_and_runs_migrations()
    {
        $this->artisan('horizon:install', ['--driver' => 'database'])->assertSuccessful();

        $this->assertTrue(File::exists(config_path('horizon.php')));

        $published = File::get(config_path('horizon.php'));

        $this->assertStringContainsString(
            "'driver' => env('HORIZON_DRIVER', 'database')",
            $published
        );

        $this->assertStringContainsString("'connection' => 'database',", $published);
        $this->assertStringContainsString("'database:default' => 60,", $published);
        $this->assertStringNotContainsString("'connection' => 'redis',", $published);
        $this->assertStringNotContainsString("'redis:default' => 60,", $published);

        $this->assertNotEmpty(
            File::glob(database_path('migrations/*_create_horizon_tables.php')),
            'Database driver install must publish Horizon database migrations.'
        );

        $this->assertTrue(Schema::hasTable('horizon_jobs'));
        $this->assertTrue(Schema::hasTable('horizon_tags'));
        $this->assertTrue(Schema::hasTable('horizon_metrics'));
    }

    public function test_install_with_database_driver_can_skip_running_migrations()
    {
        $this->artisan('horizon:install', ['--driver' => 'database', '--no-migrate' => true])->assertSuccessful();

        $this->assertNotEmpty(
            File::glob(database_path('migrations/*_create_horizon_tables.php')),
            'Migrations should still be published when --no-migrate is passed.'
        );

        $this->assertFalse(
            Schema::hasTable('horizon_jobs'),
            '--no-migrate must skip the migration step.'
        );
    }

    public function test_install_prompts_for_driver_when_option_is_omitted()
    {
        $this->artisan('horizon:install')
            ->expectsChoice(
                'Which driver would you like Horizon to use?',
                'database',
                ['redis', 'database']
            )
            ->assertSuccessful();

        $this->assertStringContainsString(
            "'driver' => env('HORIZON_DRIVER', 'database')",
            File::get(config_path('horizon.php'))
        );

        $this->assertTrue(Schema::hasTable('horizon_jobs'));
    }

    public function test_install_rejects_unsupported_driver()
    {
        $this->expectException(UnsupportedDriverException::class);

        $this->artisan('horizon:install', ['--driver' => 'memcached']);
    }

    public function test_database_install_throws_when_marker_line_is_missing()
    {
        File::put(config_path('horizon.php'), "<?php return ['driver' => 'redis'];");

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/HORIZON_DRIVER/');

        $this->artisan('horizon:install', ['--driver' => 'database', '--no-migrate' => true]);
    }

    public function test_database_install_throws_when_marker_uses_non_canonical_quotes()
    {
        File::put(
            config_path('horizon.php'),
            '<?php return ["driver" => env("HORIZON_DRIVER", "redis"),];'
        );

        $this->expectException(\RuntimeException::class);

        $this->artisan('horizon:install', ['--driver' => 'database', '--no-migrate' => true]);
    }

    public function test_redis_install_is_noop_even_when_config_marker_is_malformed()
    {
        $malformed = "<?php return ['driver' => 'gibberish'];";
        File::put(config_path('horizon.php'), $malformed);

        $this->artisan('horizon:install', ['--driver' => 'redis'])->assertSuccessful();

        $this->assertSame($malformed, File::get(config_path('horizon.php')));
    }
}
