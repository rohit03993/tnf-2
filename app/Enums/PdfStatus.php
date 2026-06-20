<?php

namespace App\Enums;

enum PdfStatus: string
{
    case Idle = 'idle';
    case Queued = 'queued';
    case Processing = 'processing';
    case Ready = 'ready';
    case Failed = 'failed';
}
