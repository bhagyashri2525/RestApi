<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class UserSnippetData extends Data
{
    public function __construct(
        public string $id,
        public string $display_name,
        public string $email,
    )
    {
        
    }
}
