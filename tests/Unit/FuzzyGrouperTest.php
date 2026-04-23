<?php

use App\Models\Answer;
use App\Models\Edition;
use App\Models\Participant;
use App\Models\VoteSubmission;
use App\Services\Analysis\AnswerNormalizer;
use App\Services\Analysis\FuzzyGrouper;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->edition = Edition::active();
    $this->question = $this->edition->questions()->first();
    $this->participant = Participant::create([
        'edition_id' => $this->edition->id,
        'email' => 'x@example.com',
        'type' => 'public',
        'link_hash' => str_repeat('a', 40),
        'access_code' => '111111',
    ]);
    $this->submission = VoteSubmission::create([
        'participant_id' => $this->participant->id,
        'edition_id' => $this->edition->id,
        'audience' => 'public',
        'submitted_at' => now(),
    ]);
    $this->grouper = new FuzzyGrouper(new AnswerNormalizer());
});

it('groups similar answers together', function () {
    $a1 = Answer::create(['vote_submission_id' => $this->submission->id, 'question_id' => $this->question->id, 'position' => 1, 'value' => 'DJ Hazel', 'points' => 5]);
    $a2 = Answer::create(['vote_submission_id' => $this->submission->id, 'question_id' => $this->question->id, 'position' => 2, 'value' => 'dj hazel', 'points' => 4]);
    $a3 = Answer::create(['vote_submission_id' => $this->submission->id, 'question_id' => $this->question->id, 'position' => 3, 'value' => 'Hazel DJ', 'points' => 3]);

    $g1 = $this->grouper->assign($a1);
    $g2 = $this->grouper->assign($a2);
    $g3 = $this->grouper->assign($a3);

    expect($g1->id)->toBe($g2->id);
    expect($g2->id)->toBe($g3->id);
    expect($g1->fresh()->aggregated_points)->toBe(12);
});

it('creates separate groups for clearly different answers', function () {
    $a1 = Answer::create(['vote_submission_id' => $this->submission->id, 'question_id' => $this->question->id, 'position' => 1, 'value' => 'DJ Hazel', 'points' => 5]);
    $a2 = Answer::create(['vote_submission_id' => $this->submission->id, 'question_id' => $this->question->id, 'position' => 2, 'value' => 'Kryptogram', 'points' => 4]);

    $g1 = $this->grouper->assign($a1);
    $g2 = $this->grouper->assign($a2);

    expect($g1->id)->not->toBe($g2->id);
});
