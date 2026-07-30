<?php

namespace Laravel\Horizon\Tests\Unit;

use Laravel\Horizon\Tests\UnitTest;
use Symfony\Component\Process\Process;

class HorizonRouteEncodingTest extends UnitTest
{
    public function test_generated_helpers_and_route_rebasing_encode_fqcn_and_slash_values()
    {
        $process = new Process(['node', 'scripts/assert-route-encoding.mjs'], dirname(__DIR__, 2));
        $process->setTimeout(30);
        $process->run();

        $this->assertTrue(
            $process->isSuccessful(),
            trim($process->getErrorOutput()."\n".$process->getOutput()) ?: 'Route encoding script failed without output.',
        );
        $this->assertStringContainsString(
            'Route encoding assertions passed.',
            $process->getOutput(),
        );
    }

    public function test_representative_generated_route_files_wrap_path_params_with_encode_uri_component()
    {
        $files = [
            'resources/js/generated/routes/horizon/monitoring-jobs/index.ts',
            'resources/js/generated/routes/horizon/monitoring-tag/index.ts',
            'resources/js/generated/routes/horizon/metrics/page/index.ts',
            'resources/js/generated/routes/horizon/queues/pause/index.ts',
        ];

        $root = dirname(__DIR__, 2);

        foreach ($files as $relative) {
            $contents = file_get_contents($root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative));

            $this->assertIsString($contents, $relative);
            $this->assertMatchesRegularExpression(
                "/\.replace\('\{[^}]+\}',\s*encodeURIComponent\(/",
                $contents,
                "Expected encodeURIComponent path substitution in {$relative}.",
            );
            $this->assertDoesNotMatchRegularExpression(
                "/\.replace\('\{[^}]+\}',\s*parsedArgs\.[A-Za-z0-9_]+\.toString\(\)\)/",
                $contents,
                "Found unencoded path substitution in {$relative}.",
            );
        }
    }
}
