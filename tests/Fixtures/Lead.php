<?php

declare(strict_types=1);

namespace Lazarini\LivewireDataTable\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

final class Lead extends Model
{
    protected $table = 'leads';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}
