<?php

namespace Laravel\Horizon\Enums;

enum JobStatus: string
{
    case Pending = 'pending';
    case Reserved = 'reserved';
    case Completed = 'completed';
    case Failed = 'failed';
}
