<?php

namespace App\Enums;

enum ContentStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Published = 'published';
}
