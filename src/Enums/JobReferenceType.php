<?php

namespace Laravel\Horizon\Enums;

enum JobReferenceType: string
{
    case Recent = 'recent';
    case Pending = 'pending';
    case Completed = 'completed';
    case Silenced = 'silenced';
    case Failed = 'failed';
    case RecentFailed = 'recent_failed';
    case Monitored = 'monitored';
}
