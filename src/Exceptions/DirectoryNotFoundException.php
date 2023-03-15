<?php

namespace ArchiElite\LaravelDevTools\Exceptions;

use InvalidArgumentException;

class DirectoryNotFoundException extends InvalidArgumentException
{
    public function __construct(string $path)
    {
        parent::__construct("The \"$path\" directory does not exist");
    }
}
