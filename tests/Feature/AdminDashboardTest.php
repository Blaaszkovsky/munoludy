<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->path = config('munoludy.admin_path');
    $this->admin = adminUser();
});

it('admin dashboard loads with widgets without error', function () {
    $this->actingAs($this->admin)->get("/{$this->path}")->assertOk();
});

it('vote submissions resource loads', function () {
    $this->actingAs($this->admin)->get("/{$this->path}/vote-submissions")->assertOk();
});

it('answer groups resource loads', function () {
    $this->actingAs($this->admin)->get("/{$this->path}/answer-groups")->assertOk();
});

it('answer group view page loads', function () {
    $edition = \App\Models\Edition::first();
    $question = \App\Models\Question::where('edition_id', $edition->id)->first();
    $group = \App\Models\AnswerGroup::create([
        'question_id' => $question->id,
        'canonical_label' => 'Test grupa analityczna',
        'aggregated_count' => 3,
        'aggregated_points' => 12,
    ]);

    $this->actingAs($this->admin)
        ->get("/{$this->path}/answer-groups/{$group->id}")
        ->assertOk()
        ->assertSee('Test grupa analityczna');
});

it('page content list loads', function () {
    $this->actingAs($this->admin)->get("/{$this->path}/page-contents")->assertOk();
});

it('page content edit page loads for each seeded view', function () {
    foreach (\App\Models\PageContent::all() as $pc) {
        $this->actingAs($this->admin)->get("/{$this->path}/page-contents/{$pc->id}/edit")->assertOk();
    }
});

it('results publisher page loads', function () {
    $this->actingAs($this->admin)->get("/{$this->path}/results-publisher")->assertOk();
});

it('participant view page loads', function () {
    $edition = \App\Models\Edition::first();
    $participant = \App\Models\Participant::create([
        'edition_id' => $edition->id,
        'type' => 'public',
        'email' => 'view-test@example.com',
        'link_hash' => 'viewhash' . uniqid(),
        'access_code' => 'ABC123',
        'consented_privacy' => true,
        'consented_marketing' => false,
    ]);

    $this->actingAs($this->admin)
        ->get("/{$this->path}/participants/{$participant->id}")
        ->assertOk();
});

it('participant view page loads with submitted votes', function () {
    $edition = \App\Models\Edition::first();
    $question = \App\Models\Question::where('edition_id', $edition->id)
        ->where('audience', 'public')
        ->first();

    $participant = \App\Models\Participant::create([
        'edition_id' => $edition->id,
        'type' => 'public',
        'email' => 'voted-view@example.com',
        'link_hash' => 'viewvote' . uniqid(),
        'access_code' => 'DEF456',
        'consented_privacy' => true,
        'consented_marketing' => false,
        'voted_at' => now(),
    ]);

    $submission = \App\Models\VoteSubmission::create([
        'participant_id' => $participant->id,
        'edition_id' => $edition->id,
        'audience' => 'public',
        'submitted_at' => now(),
        'total_points' => 15,
    ]);

    for ($i = 1; $i <= 5; $i++) {
        \App\Models\Answer::create([
            'vote_submission_id' => $submission->id,
            'question_id' => $question->id,
            'position' => $i,
            'value' => 'Artysta ' . $i,
            'value_normalized' => 'artysta ' . $i,
            'points' => 6 - $i,
        ]);
    }

    $this->actingAs($this->admin)
        ->get("/{$this->path}/participants/{$participant->id}")
        ->assertOk()
        ->assertSee('Artysta 1');
});
