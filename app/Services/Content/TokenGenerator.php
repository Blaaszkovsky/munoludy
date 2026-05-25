<?php

namespace App\Services\Content;

use App\Models\JuryMember;
use App\Models\Participant;

class TokenGenerator
{
    public function uniqueLinkHash(): string
    {
        do {
            $hash = substr(bin2hex(random_bytes(20)), 0, 40);
        } while (Participant::where('link_hash', $hash)->exists());
        return $hash;
    }

    public function uniqueJuryLinkHash(): string
    {
        do {
            $hash = substr(bin2hex(random_bytes(20)), 0, 40);
        } while (
            JuryMember::where('link_hash', $hash)->exists()
            || Participant::where('link_hash', $hash)->exists()
        );
        return $hash;
    }

    public function sixDigitCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
