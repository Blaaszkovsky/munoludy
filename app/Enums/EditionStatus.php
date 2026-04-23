<?php

namespace App\Enums;

enum EditionStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Finished = 'finished';
    case ResultsPublished = 'results_published';
}
