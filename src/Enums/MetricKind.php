<?php

namespace Laravel\Horizon\Enums;

enum MetricKind: string
{
    case Job = 'job';
    case Queue = 'queue';
}
