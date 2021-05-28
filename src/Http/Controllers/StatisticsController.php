<?php


namespace Laravel\Horizon\Http\Controllers;


use Illuminate\Http\Request;
use Laravel\Horizon\Repositories\RedisStatisticsRepository;

class StatisticsController extends Controller
{
    /**
     * @var RedisStatisticsRepository
     */
    private $repository;

    public function __construct(RedisStatisticsRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param Request $request
     * @return array
     */
    public function index(Request $request): array
    {
        $type = $request->get('type');

        return $this->repository->statisticsByType($type);
    }
}
