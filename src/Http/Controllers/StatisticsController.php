<?php

namespace Laravel\Horizon\Http\Controllers;

use Laravel\Horizon\Repositories\RedisStatisticsRepository;

class StatisticsController extends Controller
{
    /**
     * @var RedisStatisticsRepository
     */
    private $repository;

    public function __construct(RedisStatisticsRepository $repository)
    {
        parent::__construct();

        $this->repository = $repository;
    }

    /**
     * @param  string  $type
     * @return array
     */
    public function index($type)
    {
        return $this->repository->statisticsByType($type);
    }
}
