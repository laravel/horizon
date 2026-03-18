<?php

namespace Laravel\Horizon\Tests\Unit;

use Laravel\Horizon\JobPayload;
use Laravel\Horizon\Tests\UnitTest;

class JobPayloadTest extends UnitTest
{
    public function test_id_returns_uuid_when_present()
    {
        $payload = new JobPayload(json_encode(['uuid' => 'abc-123', 'id' => 'fallback']));

        $this->assertSame('abc-123', $payload->id());
    }

    public function test_id_falls_back_to_id_when_uuid_missing()
    {
        $payload = new JobPayload(json_encode(['id' => 'fallback-id']));

        $this->assertSame('fallback-id', $payload->id());
    }

    public function test_id_returns_null_for_invalid_json()
    {
        $payload = new JobPayload('not-valid-json');

        $this->assertNull($payload->id());
    }

    public function test_id_returns_null_for_empty_object()
    {
        $payload = new JobPayload(json_encode([]));

        $this->assertNull($payload->id());
    }

    public function test_id_returns_null_for_null_decoded()
    {
        $payload = new JobPayload('null');

        $this->assertNull($payload->id());
    }
}
