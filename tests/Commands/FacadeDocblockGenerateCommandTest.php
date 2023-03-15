<?php

namespace ArchiElite\LaravelDevTools\Tests\Commands;

use ArchiElite\LaravelDevTools\FacadeFinder;
use ArchiElite\LaravelDevTools\Tests\TestCase;

class FacadeDocblockGenerateCommandTest extends TestCase
{
    public function testEnsureItRunSuccessfully()
    {
        $finder = (new FacadeFinder())->find('');

        dd($finder->toArray());
    }
}
