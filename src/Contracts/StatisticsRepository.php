<?php

namespace Laravel\Horizon\Contracts;

interface StatisticsRepository
{
    public function byType(string $type): array;
}
