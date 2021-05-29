<?php

namespace Laravel\Horizon\Http\Controllers;

use Laravel\Horizon\Contracts\StatisticsRepository;

class StatisticsController extends Controller
{
    /**
     * @var StatisticsRepository
     */
    private $statistics;

    public function __construct(StatisticsRepository $statistics)
    {
        parent::__construct();

        $this->statistics = $statistics;
    }
    public function index(string $type): array
    {
        return $this->statistics->byType($type);
    }
}
