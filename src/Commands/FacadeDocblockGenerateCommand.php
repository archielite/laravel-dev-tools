<?php

namespace ArchiElite\LaravelDevTools\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand('dev-tool:facade-docblock-generate')]
class FacadeDocblockGenerateCommand extends Command
{
    public function handle(): int
    {
        return self::SUCCESS;
    }
}
