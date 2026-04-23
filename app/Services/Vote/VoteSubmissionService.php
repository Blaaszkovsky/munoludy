<?php

namespace App\Services\Vote;

use App\Enums\QuestionFieldType;
use App\Models\Answer;
use App\Models\Participant;
use App\Models\Question;
use App\Models\VoteSubmission;
use App\Services\Analysis\FuzzyGrouper;
use Illuminate\Support\Facades\DB;

class VoteSubmissionService
{
    public function __construct(private FuzzyGrouper $grouper) {}

    public function submit(Participant $participant, array $draft, array $meta): VoteSubmission
    {
        return DB::transaction(function () use ($participant, $draft, $meta) {
            $locked = Participant::whereKey($participant->id)->lockForUpdate()->first();
            if ($locked->voted_at) {
                throw new \RuntimeException('Already voted');
            }

            $submission = VoteSubmission::create([
                'participant_id' => $participant->id,
                'edition_id' => $participant->edition_id,
                'audience' => $participant->type->value,
                'submitted_at' => now(),
                'ip' => $meta['ip'] ?? null,
                'user_agent' => substr((string) ($meta['user_agent'] ?? ''), 0, 500),
            ]);

            $totalPoints = 0;
            foreach ($draft as $questionId => $values) {
                $question = Question::find($questionId);
                if (!$question || $question->edition_id !== $participant->edition_id) {
                    continue;
                }

                $answers = $this->buildAnswers($question, $values, $submission->id);
                foreach ($answers as $a) {
                    $answer = Answer::create($a);
                    $totalPoints += $answer->points;
                    $this->grouper->assign($answer);
                }
            }

            $submission->update(['total_points' => $totalPoints]);
            $locked->update(['voted_at' => now()]);

            return $submission;
        });
    }

    private function buildAnswers(Question $q, array $values, int $submissionId): array
    {
        $out = [];
        if ($q->field_type === QuestionFieldType::RankedText5) {
            $seenNormalizedFirst = null;
            foreach ([1, 2, 3, 4, 5] as $pos) {
                $val = trim((string) ($values[$pos] ?? ''));
                if ($val === '') {
                    continue;
                }
                $points = $q->pointsForPosition($pos);

                // "podanie 5 razy tej samej nazwy" rule: same value only counted once at position 1's points
                $norm = mb_strtolower($val);
                if ($pos === 1) {
                    $seenNormalizedFirst = $norm;
                } elseif ($seenNormalizedFirst === $norm) {
                    continue;
                }

                $out[] = [
                    'vote_submission_id' => $submissionId,
                    'question_id' => $q->id,
                    'position' => $pos,
                    'value' => $val,
                    'points' => $points,
                ];
            }
        } elseif (in_array($q->field_type, [QuestionFieldType::Text, QuestionFieldType::Textarea], true)) {
            $val = trim((string) ($values[1] ?? ''));
            if ($val !== '') {
                $out[] = [
                    'vote_submission_id' => $submissionId,
                    'question_id' => $q->id,
                    'position' => 1,
                    'value' => $val,
                    'points' => 0,
                ];
            }
        } elseif (in_array($q->field_type, [QuestionFieldType::Radio, QuestionFieldType::Select], true)) {
            $optId = (int) ($values[1] ?? 0);
            $opt = $q->options()->find($optId);
            if ($opt) {
                $out[] = [
                    'vote_submission_id' => $submissionId,
                    'question_id' => $q->id,
                    'position' => 1,
                    'value' => $opt->label,
                    'question_option_id' => $opt->id,
                    'points' => $opt->points,
                ];
            }
        } elseif ($q->field_type === QuestionFieldType::Checkbox) {
            foreach ((array) ($values ?? []) as $i => $optId) {
                $opt = $q->options()->find((int) $optId);
                if ($opt) {
                    $out[] = [
                        'vote_submission_id' => $submissionId,
                        'question_id' => $q->id,
                        'position' => $i + 1,
                        'value' => $opt->label,
                        'question_option_id' => $opt->id,
                        'points' => $opt->points,
                    ];
                }
            }
        }
        return $out;
    }
}
