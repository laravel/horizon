<?php

declare(strict_types=1);

namespace Laravel\Horizon\Support;

use Inertia\ProvidesScrollMetadata;

final readonly class HorizonScrollMetadata implements ProvidesScrollMetadata
{
    public function __construct(
        private string $pageName,
        private int|string|null $previous,
        private int|string|null $next,
        private int|string|null $current,
    ) {
    }

    public function getPageName(): string
    {
        return $this->pageName;
    }

    public function getPreviousPage(): int|string|null
    {
        return $this->previous;
    }

    public function getNextPage(): int|string|null
    {
        return $this->next;
    }

    public function getCurrentPage(): int|string|null
    {
        return $this->current;
    }
}
