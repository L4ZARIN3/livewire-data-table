<?php

declare(strict_types=1);

namespace Lazarini\LivewireDataTable\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

final class FacebookAccount extends Model
{
    protected $table = 'facebook_accounts';

    protected $guarded = [];
}
