<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:test-search-query')]
#[Description('Command description')]
class TestSearchQuery extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
    }
}
