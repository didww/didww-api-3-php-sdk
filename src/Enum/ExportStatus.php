<?php

namespace Didww\Enum;

enum ExportStatus: string
{
    case PENDING = 'Pending';
    case PROCESSING = 'Processing';
    case COMPLETED = 'Completed';
}
