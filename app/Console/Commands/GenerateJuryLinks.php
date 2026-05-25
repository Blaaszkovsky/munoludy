<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\JuryMember;
use App\Services\Content\TokenGenerator;
use Illuminate\Console\Command;

class GenerateJuryLinks extends Command
{
    protected $signature = 'munoludy:generate-jury-links
        {--edition= : Ograniczenie do edycji (ID)}
        {--force : Wygeneruj nowe link_hash także dla rekordów, które już je mają}';

    protected $description = 'Generuje brakujące link_hash dla członków jury (do późniejszej wysyłki linków).';

    public function handle(TokenGenerator $tokens): int
    {
        $query = JuryMember::query();

        if ($editionId = $this->option('edition')) {
            $query->where('edition_id', (int) $editionId);
        }

        if (! $this->option('force')) {
            $query->whereNull('link_hash');
        }

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('Nic do zrobienia — wszyscy jurorzy mają już link_hash.');

            return self::SUCCESS;
        }

        $this->info(sprintf('Generuję link_hash dla %d rekordów...', $total));

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->orderBy('id')->each(function (JuryMember $jury) use ($tokens, $bar) {
            $jury->link_hash = $tokens->uniqueJuryLinkHash();
            $jury->save();
            $bar->advance();
        });

        $bar->finish();
        $this->newLine(2);
        $this->info('Gotowe.');

        return self::SUCCESS;
    }
}
