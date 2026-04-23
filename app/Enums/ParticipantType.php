<?php

namespace App\Enums;

enum ParticipantType: string
{
    case Public_ = 'public';
    case Jury = 'jury';

    public function label(): string
    {
        return match ($this) {
            self::Public_ => 'Publiczność',
            self::Jury => 'Jury',
        };
    }
}
