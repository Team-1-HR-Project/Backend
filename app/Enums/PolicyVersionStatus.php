<?php

namespace App\Enums;

enum PolicyVersionStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';
}
