<?php

namespace App\Exports;

use App\Models\Answer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class VoteSubmissionsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Answer::with(['submission.participant', 'question'])->get();
    }

    public function headings(): array
    {
        return [
            'SubmissionID',
            'Audience',
            'Email',
            'QuestionID',
            'QuestionTitle',
            'Position',
            'Value',
            'Points',
            'SubmittedAt',
        ];
    }

    public function map($a): array
    {
        return [
            $a->vote_submission_id,
            optional($a->submission)->audience,
            optional(optional($a->submission)->participant)->email,
            $a->question_id,
            optional($a->question)->title,
            $a->position,
            $a->value,
            $a->points,
            optional(optional($a->submission)->submitted_at)->toDateTimeString(),
        ];
    }
}
