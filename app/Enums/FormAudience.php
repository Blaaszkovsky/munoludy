<?php

namespace App\Enums;

enum FormAudience: string
{
    case Public_ = 'public';
    case Jury = 'jury';
    case Both = 'both';

    public function visibleFor(ParticipantType $type): bool
    {
        return match ($this) {
            self::Both => true,
            self::Public_ => $type === ParticipantType::Public_,
            self::Jury => $type === ParticipantType::Jury,
        };
    }
}
