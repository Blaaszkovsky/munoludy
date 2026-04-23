<?php

namespace App\Services\Vote;

use App\Models\Participant;

class VoteSessionManager
{
    private const SESSION_KEY = 'vote_session';
    private const DRAFT_KEY = 'vote_draft';

    public function authorize(Participant $participant): void
    {
        session([self::SESSION_KEY => [
            'participant_id' => $participant->id,
            'authorized_at' => now()->toIso8601String(),
        ]]);
    }

    public function authorizedParticipantId(): ?int
    {
        $data = session(self::SESSION_KEY);
        if (!$data) {
            return null;
        }
        $ttl = (int) config('munoludy.vote_session_ttl_minutes') * 60;
        $diff = abs((int) now()->diffInSeconds($data['authorized_at']));
        if ($diff > $ttl) {
            session()->forget(self::SESSION_KEY);
            return null;
        }
        return $data['participant_id'] ?? null;
    }

    public function saveStep(int $questionId, array $values): void
    {
        $draft = session(self::DRAFT_KEY, []);
        $draft[$questionId] = $values;
        session([self::DRAFT_KEY => $draft]);
    }

    public function draft(): array
    {
        return session(self::DRAFT_KEY, []);
    }

    public function clear(): void
    {
        session()->forget([self::SESSION_KEY, self::DRAFT_KEY]);
    }
}
