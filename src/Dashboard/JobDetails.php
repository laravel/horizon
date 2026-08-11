<?php

declare(strict_types=1);

namespace Laravel\Horizon\Dashboard;

use Laravel\Horizon\Contracts\JobRepository;
use Throwable;

final readonly class JobDetails
{
    public function __construct(private JobRepository $jobs)
    {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $type, string $id): ?array
    {
        $job = $type === 'failed'
            ? $this->jobs->findFailed($id)
            : $this->jobs->getJobs([$id])->first();

        if (! is_object($job)) {
            return null;
        }

        $payload = $this->decode((string) ($job->payload ?? ''));
        $pushedAt = $this->timestamp($payload['pushedAt'] ?? null);
        $reservedAt = $this->timestamp($job->reserved_at ?? null);
        $completedAt = $this->timestamp($job->completed_at ?? null);
        $failedAt = $this->timestamp($job->failed_at ?? null);
        $finishedAt = $failedAt ?? $completedAt;

        return [
            'id' => (string) ($job->id ?? $id),
            'name' => (string) ($job->name ?? $payload['displayName'] ?? $id),
            'connection' => (string) ($job->connection ?? 'default'),
            'queue' => (string) ($job->queue ?? 'default'),
            'status' => (string) ($job->status ?? 'unknown'),
            'tags' => $this->tags($payload),
            'attempts' => is_numeric($payload['attempts'] ?? null)
                ? (int) $payload['attempts']
                : 0,
            'retryOf' => is_string($payload['retry_of'] ?? null)
                ? $payload['retry_of']
                : null,
            'batchId' => $this->batchId($payload),
            'delay' => is_numeric($job->delay ?? null) ? (int) $job->delay : null,
            'pushedAt' => $pushedAt,
            'reservedAt' => $reservedAt,
            'completedAt' => $completedAt,
            'failedAt' => $failedAt,
            'runtime' => $reservedAt !== null && $finishedAt !== null
                ? round(max(0, $finishedAt - $reservedAt), 4)
                : null,
            'payload' => $this->safePayload($payload),
            'context' => $this->decode((string) ($job->context ?? '')),
            'exception' => is_string($job->exception ?? null)
                ? mb_convert_encoding($job->exception, 'UTF-8', 'UTF-8')
                : '',
            'retriedBy' => $this->retriedBy($job->retried_by ?? null),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(string $value): array
    {
        if ($value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, string>
     */
    private function tags(array $payload): array
    {
        $tags = $payload['tags'] ?? [];

        return is_array($tags)
            ? array_values(array_filter($tags, is_string(...)))
            : [];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function batchId(array $payload): ?string
    {
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];

        return is_string($data['batchId'] ?? null) ? $data['batchId'] : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function safePayload(array $payload): array
    {
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $decodedCommand = $this->decodedCommand($data['command'] ?? null);
        unset($data['command']);

        if ($decodedCommand !== null) {
            $data['decodedCommand'] = $decodedCommand;
        }

        return array_filter([
            'displayName' => is_string($payload['displayName'] ?? null)
                ? $payload['displayName']
                : null,
            'job' => is_string($payload['job'] ?? null) ? $payload['job'] : null,
            'uuid' => is_string($payload['uuid'] ?? null) ? $payload['uuid'] : null,
            'maxTries' => is_numeric($payload['maxTries'] ?? null)
                ? (int) $payload['maxTries']
                : null,
            'timeout' => is_numeric($payload['timeout'] ?? null)
                ? (int) $payload['timeout']
                : null,
            'data' => $data,
        ], static fn (mixed $value): bool => $value !== null);
    }

    /**
     * @return array<array-key, mixed>|null
     */
    private function decodedCommand(mixed $serialized): ?array
    {
        if (! is_string($serialized) || $serialized === '') {
            return null;
        }

        try {
            $command = @unserialize($serialized, ['allowed_classes' => false]);
        } catch (Throwable) {
            return null;
        }

        $normalized = $this->normalizeCommandValue($command);

        return is_array($normalized) ? $normalized : null;
    }

    private function normalizeCommandValue(mixed $value, int $depth = 0): mixed
    {
        if ($depth >= 8) {
            return '[Maximum depth reached]';
        }

        if ($value === null || is_scalar($value)) {
            return $value;
        }

        if (is_array($value)) {
            $normalized = [];

            foreach (array_slice($value, 0, 200, true) as $key => $item) {
                $normalized[$key] = $this->normalizeCommandValue($item, $depth + 1);
            }

            return $normalized;
        }

        if (! is_object($value)) {
            return null;
        }

        $normalized = [];

        foreach (array_slice(get_object_vars($value), 0, 200, true) as $key => $item) {
            $normalized[$this->normalizeCommandKey($key)] = $this->normalizeCommandValue(
                $item,
                $depth + 1,
            );
        }

        return $normalized;
    }

    private function normalizeCommandKey(int|string $key): int|string
    {
        if (! is_string($key)) {
            return $key;
        }

        if ($key === '__PHP_Incomplete_Class_Name') {
            return 'class';
        }

        $segments = explode("\0", $key);

        return end($segments) ?: $key;
    }

    /**
     * @return array<int, mixed>
     */
    private function retriedBy(mixed $value): array
    {
        if (! is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? array_values($decoded) : [];
    }

    private function timestamp(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
