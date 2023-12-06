<?php

namespace App\Data\Company;

use Spatie\LaravelData\Data;

class CompanyData extends Data
{
    public function __construct(
        public string $id,
        public ?string $code,
        public ?string $name,
        public ?string $slug,
        public ?string $descriptions,
        public ?bool $is_active,
        public ?Array $departments,
    )
    {
        
    }
}
