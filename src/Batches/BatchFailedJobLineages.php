<?php

declare(strict_types=1);

namespace Laravel\Horizon\Batches;

use JsonException;

/**
 * Collapse retained failed jobs into logical retry lineages for the batch UI.
 *
 * One row per incomplete lineage (original plus retries), with aggregated attempts.
 * Does not mutate storage counters.
 */
final readonly class BatchFailedJobLineages
{
    /**
     * @param  array<int, object>  $retainedJobs
     * @param  array<int, string>  $failedJobIds
     * @return array{rows: array<int, array<string, mixed>>, complete: bool}
     */
    public function summarize(array $retainedJobs, array $failedJobIds): array
    {
        $nodes = [];
        $adjacency = [];
        $referenceStatuses = [];

        foreach ($retainedJobs as $job) {
            $id = $job->id ?? null;

            if (! is_string($id) || $id === '') {
                continue;
            }

            $payload = $this->decode($job->payload ?? null);
            $nodes[$id] = [
                'job' => $job,
                'payload' => $payload,
                'attempts' => max(1, is_numeric($payload['attempts'] ?? null)
                    ? (int) $payload['attempts']
                    : 0),
                'failedAt' => is_numeric($job->failed_at ?? null) ? (float) $job->failed_at : 0.0,
                'index' => is_numeric($job->index ?? null) ? (int) $job->index : 0,
            ];
            $adjacency[$id] ??= [];

            $retryOf = $payload['retry_of'] ?? null;

            if (is_string($retryOf) && $retryOf !== '') {
                $this->connect($adjacency, $id, $retryOf);
                $referenceStatuses[$retryOf] ??= 'failed';
            }

            foreach ($this->retries($job->retried_by ?? null) as $retry) {
                $retryId = $retry['id'];
                $this->connect($adjacency, $id, $retryId);
                $referenceStatuses[$retryId] = $retry['status'];
            }
        }

        $rows = [];
        $coveredIds = [];
        $visited = [];
        $allAttemptsComplete = true;

        foreach (array_keys($nodes) as $id) {
            if (isset($visited[$id])) {
                continue;
            }

            $component = $this->component($id, $adjacency, $visited);
            $coveredIds += array_fill_keys($component, true);
            $retained = array_values(array_filter(
                $component,
                static fn (string $componentId): bool => isset($nodes[$componentId]),
            ));

            if ($retained === [] || $this->wasCompleted($component, $referenceStatuses)) {
                continue;
            }

            usort(
                $retained,
                static fn (string $left, string $right): int => [
                    $nodes[$right]['failedAt'],
                    $nodes[$right]['index'],
                ] <=> [
                    $nodes[$left]['failedAt'],
                    $nodes[$left]['index'],
                ],
            );

            $attempts = 0;
            $attemptsComplete = true;

            foreach ($component as $componentId) {
                if (isset($nodes[$componentId])) {
                    $attempts += $nodes[$componentId]['attempts'];

                    continue;
                }

                $status = $referenceStatuses[$componentId] ?? 'failed';

                if ($status === 'pending') {
                    continue;
                }

                $attempts++;
                $attemptsComplete = false;
            }

            $representative = $nodes[$retained[0]];
            $job = $representative['job'];
            $payload = $representative['payload'];

            $rows[] = [
                'id' => $retained[0],
                'name' => (string) ($job->name ?? $payload['displayName'] ?? $retained[0]),
                'attempts' => $attempts,
                'attemptsComplete' => $attemptsComplete,
                'failed_at' => is_numeric($job->failed_at ?? null) ? (float) $job->failed_at : null,
                'reserved_at' => is_numeric($job->reserved_at ?? null) ? (float) $job->reserved_at : null,
                'payload' => [
                    'displayName' => is_string($payload['displayName'] ?? null)
                        ? $payload['displayName']
                        : null,
                ],
            ];
            $allAttemptsComplete = $allAttemptsComplete && $attemptsComplete;
        }

        usort(
            $rows,
            static fn (array $left, array $right): int => ($right['failed_at'] ?? 0) <=> ($left['failed_at'] ?? 0),
        );

        $knownFailedIds = array_fill_keys($failedJobIds, true);
        $allFailedIdsCovered = array_diff_key($knownFailedIds, $coveredIds) === [];

        return [
            'rows' => $rows,
            'complete' => $allFailedIdsCovered && $allAttemptsComplete,
        ];
    }

    /**
     * @param  array<string, array<string, true>>  $adjacency
     */
    private function connect(array &$adjacency, string $left, string $right): void
    {
        $adjacency[$left] ??= [];
        $adjacency[$right] ??= [];
        $adjacency[$left][$right] = true;
        $adjacency[$right][$left] = true;
    }

    /**
     * @param  array<string, array<string, true>>  $adjacency
     * @param  array<string, true>  $visited
     * @return array<int, string>
     */
    private function component(string $start, array $adjacency, array &$visited): array
    {
        $component = [];
        $pending = [$start];

        while ($pending !== []) {
            $id = array_pop($pending);

            if (isset($visited[$id])) {
                continue;
            }

            $visited[$id] = true;
            $component[] = $id;

            foreach (array_keys($adjacency[$id] ?? []) as $relatedId) {
                if (! isset($visited[$relatedId])) {
                    $pending[] = $relatedId;
                }
            }
        }

        return $component;
    }

    /**
     * @param  array<int, string>  $component
     * @param  array<string, string>  $referenceStatuses
     */
    private function wasCompleted(array $component, array $referenceStatuses): bool
    {
        foreach ($component as $id) {
            if (($referenceStatuses[$id] ?? null) === 'completed') {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || $value === '') {
            return [];
        }

        try {
            $decoded = json_decode($value, true, flags: JSON_THROW_ON_ERROR);

            return is_array($decoded) ? $decoded : [];
        } catch (JsonException) {
            return [];
        }
    }

    /**
     * @return array<int, array{id: string, status: string}>
     */
    private function retries(mixed $value): array
    {
        if (is_string($value)) {
            $value = $this->decode($value);
        }

        if (! is_array($value)) {
            return [];
        }

        $retries = [];

        foreach ($value as $retry) {
            if (! is_array($retry) || ! is_string($retry['id'] ?? null) || $retry['id'] === '') {
                continue;
            }

            $retries[] = [
                'id' => $retry['id'],
                'status' => is_string($retry['status'] ?? null) ? $retry['status'] : 'unknown',
            ];
        }

        return $retries;
    }
}
