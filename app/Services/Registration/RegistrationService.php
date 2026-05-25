<?php

namespace App\Services\Registration;

use App\Enums\ParticipantType;
use App\Models\Edition;
use App\Models\Participant;
use App\Services\Content\TokenGenerator;
use App\Services\UserCom\UserComSyncService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RegistrationService
{
    public function __construct(
        private TokenGenerator $tokens,
        private UserComSyncService $userCom,
    ) {}

    public function register(Edition $edition, string $email, array $meta): Participant
    {
        return DB::transaction(function () use ($edition, $email, $meta) {
            $participant = Participant::firstOrNew([
                'edition_id' => $edition->id,
                'email' => $email,
            ]);

            if (!$participant->exists) {
                $participant->type = ParticipantType::Public_;
                $participant->link_hash = $this->tokens->uniqueLinkHash();
                $participant->access_code = $this->tokens->sixDigitCode();
                $participant->consented_privacy = $meta['privacy'] ?? false;
                $participant->consented_marketing = $meta['marketing'] ?? false;
                $participant->registered_ip = $meta['ip'] ?? null;
                $participant->registered_user_agent = $meta['user_agent'] ?? null;
                $participant->save();
            }

            try {
                $this->userCom->sync($participant);
            } catch (\Throwable $e) {
                Log::error('user.com sync failed for participant '.$participant->id, ['error' => $e->getMessage()]);
            }

            return $participant;
        });
    }
}
