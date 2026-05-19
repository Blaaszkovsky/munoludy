<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\AccessCodeRequest;
use App\Http\Requests\SubmitVoteRequest;
use App\Models\PageContent;
use App\Models\Participant;
use App\Models\Question;
use App\Services\UserCom\UserComSyncService;
use App\Services\Vote\VoteSessionManager;
use App\Services\Vote\VoteSubmissionService;
use Artesaos\SEOTools\Facades\SEOTools;
use Illuminate\Support\Facades\Log;

class VoteController extends Controller
{
    public function __construct(private VoteSessionManager $session) {}

    public function start(string $hash)
    {
        $participant = $this->resolveParticipant($hash);
        if ($participant->hasVoted()) {
            return $this->renderThankYou($participant);
        }
        $edition = $participant->edition;
        if (!$edition->isVotingOpen()) {
            return view('closed', ['edition' => $edition]);
        }
        $view = $participant->type->value === 'jury' ? 'vote_start_jury' : 'vote_start_public';
        $content = PageContent::where('edition_id', $edition->id)->where('view', $view)->firstOrFail();
        SEOTools::setTitle($content->og_title ?? 'Głosowanie');
        SEOTools::setDescription($content->og_description ?? '');
        return view('vote.start', [
            'participant' => $participant,
            'content' => $content->content,
            'hash' => $hash,
        ]);
    }

    public function verifyCode(AccessCodeRequest $request, string $hash)
    {
        $participant = $this->resolveParticipant($hash);
        if ($participant->access_code !== $request->input('code')) {
            return back()->withErrors(['code' => 'Nieprawidłowy kod.']);
        }
        $this->session->authorize($participant);
        return redirect()->route('vote.step', ['hash' => $hash, 'n' => 1]);
    }

    public function step(string $hash, int $n)
    {
        $participant = $this->ensureAuthorized($hash);
        $questions = $this->questionsFor($participant);
        abort_if($n < 1 || $n > count($questions), 404);
        $question = $questions->values()[$n - 1];
        return view('vote.question', [
            'participant' => $participant,
            'question' => $question,
            'step' => $n,
            'total' => count($questions),
            'draft' => $this->session->draft()[$question->id] ?? [],
            'hash' => $hash,
        ]);
    }

    public function saveStep(SubmitVoteRequest $request, string $hash, int $n)
    {
        $participant = $this->ensureAuthorized($hash);
        $questions = $this->questionsFor($participant);
        abort_if($n < 1 || $n > count($questions), 404);
        $question = $questions->values()[$n - 1];
        $values = $request->input("answers.{$question->id}", []);
        $this->session->saveStep($question->id, $values);

        $next = $request->input('direction', 'next') === 'prev' ? $n - 1 : $n + 1;
        if ($next < 1) {
            return redirect()->route('vote.start', ['hash' => $hash]);
        }
        if ($next > count($questions)) {
            return redirect()->route('vote.summary', ['hash' => $hash]);
        }
        return redirect()->route('vote.step', ['hash' => $hash, 'n' => $next]);
    }

    public function summary(string $hash)
    {
        $participant = $this->ensureAuthorized($hash);
        return $this->summaryView($participant, $hash);
    }

    private function summaryView(Participant $participant, string $hash, ?string $voteError = null, array $missingTitles = [])
    {
        return view('vote.summary', [
            'participant' => $participant,
            'questions' => $this->questionsFor($participant),
            'draft' => $this->session->draft(),
            'hash' => $hash,
            'voteError' => $voteError,
            'missingTitles' => $missingTitles,
        ]);
    }

    public function submit(string $hash, VoteSubmissionService $service, UserComSyncService $userCom)
    {
        $participant = $this->ensureAuthorized($hash);

        if ($participant->hasVoted()) {
            $this->session->clear();
            return redirect()->route('vote.thank-you', ['hash' => $hash]);
        }

        abort_unless($participant->edition->isVotingOpen(), 403);

        $draft = $this->session->draft();
        $missing = $this->unansweredQuestions($this->questionsFor($participant), $draft);
        if ($missing->isNotEmpty()) {
            // Render bezpośrednio jako odpowiedź na POST (status 422) — Cloudflare
            // nie cache'uje POST, więc komunikaty pojawią się niezależnie od
            // konfiguracji CDN (brak zależności od redirectu na cache'owany GET).
            return response($this->summaryView(
                $participant,
                $hash,
                'W każdej kategorii musisz oddać co najmniej jeden głos. Uzupełnij brakujące kategorie i spróbuj ponownie.',
                $missing->pluck('title')->all(),
            ), 422);
        }

        try {
            $service->submit($participant, $draft, [
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        } catch (\RuntimeException $e) {
            $this->session->clear();
            return redirect()->route('vote.thank-you', ['hash' => $hash]);
        }

        try {
            $userCom->tagVoted($participant->fresh(), config('munoludy.user_com.voted_tag_name'));
        } catch (\Throwable $e) {
            Log::error('user.com tag after vote failed', [
                'participant_id' => $participant->id,
                'error' => $e->getMessage(),
            ]);
        }

        $this->session->clear();
        return redirect()->route('vote.thank-you', ['hash' => $hash]);
    }

    public function thankYou(string $hash)
    {
        return $this->renderThankYou($this->resolveParticipant($hash));
    }

    private function renderThankYou(Participant $participant)
    {
        $content = PageContent::where('edition_id', $participant->edition_id)
            ->where('view', 'vote_thank_you')
            ->first();
        return view('vote.thank-you', ['content' => $content?->content ?? []]);
    }

    private function resolveParticipant(string $hash): Participant
    {
        return Participant::where('link_hash', $hash)->firstOrFail();
    }

    private function ensureAuthorized(string $hash): Participant
    {
        $participant = $this->resolveParticipant($hash);
        $authorizedId = $this->session->authorizedParticipantId();
        if ($authorizedId !== $participant->id) {
            abort(redirect()->route('vote.start', ['hash' => $hash]));
        }
        return $participant;
    }

    private function questionsFor(Participant $participant)
    {
        return Question::where('edition_id', $participant->edition_id)
            ->whereIn('audience', [$participant->type->value, 'both'])
            ->orderBy('order')
            ->get();
    }

    /**
     * Kategorie (pytania), w których nie oddano żadnego niepustego głosu.
     * Wymóg: w każdej kategorii musi paść co najmniej 1 głos w dowolnej punktacji.
     */
    private function unansweredQuestions($questions, array $draft)
    {
        return $questions->filter(function ($question) use ($draft) {
            return !$this->hasAnyValue($draft[$question->id] ?? []);
        })->values();
    }

    private function hasAnyValue($values): bool
    {
        foreach ((array) $values as $value) {
            if (is_array($value)) {
                if ($this->hasAnyValue($value)) {
                    return true;
                }
            } elseif (trim((string) $value) !== '') {
                return true;
            }
        }
        return false;
    }
}
