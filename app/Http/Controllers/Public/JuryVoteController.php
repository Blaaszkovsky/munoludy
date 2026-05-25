<?php

namespace App\Http\Controllers\Public;

use App\Enums\ParticipantType;
use App\Http\Controllers\Controller;
use App\Http\Requests\JuryEmailVerifyRequest;
use App\Models\JuryMember;
use App\Models\PageContent;
use App\Models\Participant;
use App\Services\Content\TokenGenerator;
use App\Services\Vote\VoteSessionManager;
use Artesaos\SEOTools\Facades\SEOTools;
use Illuminate\Support\Facades\DB;

class JuryVoteController extends Controller
{
    public function __construct(
        private VoteSessionManager $session,
        private TokenGenerator $tokens,
    ) {}

    public function start(string $hash)
    {
        $jury = $this->resolveJury($hash);
        $edition = $jury->edition;

        if ($this->participant($jury)?->hasVoted()) {
            return $this->renderThankYou($jury);
        }

        if (!$edition->isVotingOpen()) {
            return view('closed', ['edition' => $edition]);
        }

        $content = PageContent::where('edition_id', $edition->id)
            ->where('view', 'vote_start_jury')
            ->firstOrFail();

        SEOTools::setTitle($content->og_title ?? 'Głosowanie jury');
        SEOTools::setDescription($content->og_description ?? '');

        return view('vote.jury-start', [
            'jury' => $jury,
            'content' => $content->content,
            'hash' => $hash,
        ]);
    }

    public function verifyEmail(JuryEmailVerifyRequest $request, string $hash)
    {
        $jury = $this->resolveJury($hash);

        $submitted = mb_strtolower(trim($request->input('email')));
        $expected = mb_strtolower(trim($jury->email));

        if ($submitted !== $expected) {
            return back()
                ->withErrors(['email' => 'Adres e-mail nie pasuje do zaproszenia.'])
                ->withInput();
        }

        $participant = $this->ensureParticipant($jury);

        if ($participant->hasVoted()) {
            return redirect()->route('jury.vote.start', ['hash' => $hash]);
        }

        $this->session->authorize($participant);

        return redirect()->route('vote.step', ['hash' => $participant->link_hash, 'n' => 1]);
    }

    private function resolveJury(string $hash): JuryMember
    {
        return JuryMember::where('link_hash', $hash)->firstOrFail();
    }

    private function participant(JuryMember $jury): ?Participant
    {
        return Participant::where('edition_id', $jury->edition_id)
            ->where('email', $jury->email)
            ->first();
    }

    private function ensureParticipant(JuryMember $jury): Participant
    {
        return DB::transaction(function () use ($jury) {
            $participant = Participant::firstOrNew([
                'edition_id' => $jury->edition_id,
                'email' => $jury->email,
            ]);

            if (!$participant->exists) {
                $participant->type = ParticipantType::Jury;
                $participant->link_hash = $this->tokens->uniqueLinkHash();
                $participant->access_code = $this->tokens->sixDigitCode();
                $participant->consented_privacy = true;
                $participant->registered_ip = request()->ip();
                $participant->registered_user_agent = substr((string) request()->userAgent(), 0, 500);
                $participant->save();
            }

            return $participant;
        });
    }

    private function renderThankYou(JuryMember $jury)
    {
        $content = PageContent::where('edition_id', $jury->edition_id)
            ->where('view', 'vote_thank_you')
            ->first();

        return view('vote.thank-you', ['content' => $content?->content ?? []]);
    }
}
