<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class UserData extends Data
{
    public function __construct(
        public string $id,
        public string $display_name,
        public ?Array $details,
        public string $email,
        public bool $is_email_verified,
        public bool $is_active,
        public ?Array $companies,
        public ?string $display_profile_url,
    )
    {
        
    }
}