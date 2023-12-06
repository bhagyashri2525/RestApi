<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class ParticularUserData extends Data
{
    public function __construct(
        public string $id,
        public string $email,
        public ?Array $companies,
        public ?Array $company_mapping,
        public ?Array $zoom_integrations,
    )
    {
        
    }
}
