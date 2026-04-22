<?php

declare(strict_types=1);

namespace Lazarini\LivewireDataTable\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MetaAccountSync extends Model
{
    protected $table = 'meta_account_syncs';

    protected $guarded = [];

    public function facebookAccount(): BelongsTo
    {
        return $this->belongsTo(FacebookAccount::class, 'local_facebook_account_id', 'id');
    }
}
