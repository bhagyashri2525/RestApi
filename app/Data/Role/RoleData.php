<?php

namespace App\Data\Role;

use Spatie\LaravelData\Data;

class RoleData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        public string $order,
        public bool $is_active,
    )
    {
        
    }
}
