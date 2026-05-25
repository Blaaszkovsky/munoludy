<?php

namespace App\Exports;

use App\Models\JuryMember;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class JuryMembersExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private ?int $editionId = null) {}

    public function collection()
    {
        $query = JuryMember::query()->with('edition');

        if ($this->editionId) {
            $query->where('edition_id', $this->editionId);
        }

        return $query->orderBy('edition_id')->orderBy('email')->get();
    }

    public function headings(): array
    {
        return ['Edycja', 'E-mail', 'Imię i nazwisko', 'Link do głosowania', 'Zagłosował', 'Notatki'];
    }

    public function map($row): array
    {
        /** @var JuryMember $row */
        return [
            $row->edition?->name,
            $row->email,
            $row->display_name,
            $row->votingUrl(),
            $row->hasVoted() ? 'tak' : 'nie',
            $row->notes,
        ];
    }
}
