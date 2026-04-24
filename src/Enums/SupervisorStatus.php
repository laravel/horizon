<?php

namespace Laravel\Horizon\Enums;

enum SupervisorStatus: string
{
    case Running = 'running';
    case Paused = 'paused';
}
