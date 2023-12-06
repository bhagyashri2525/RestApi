<?php

namespace App\Data\Event;

use Spatie\LaravelData\Data;

class EventData extends Data
{
    public function __construct(
        public string $id,
        public ?string $name,
        public ?string $slug,
        public string $company_id,
        public ?string $description,
        public ?bool $is_active,
        public ?string $created_at,
        public ?string $type,
        public ?string $parent_id,
        public ?string $event_count,
        public ?string $start_datetime,
    )
    {
        
    }
}