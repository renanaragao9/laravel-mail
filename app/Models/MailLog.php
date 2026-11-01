<?php

namespace App\Models;

use App\Enums\MailStatus;
use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MailLog extends Model
{
    use BelongsToUser;

    protected $fillable = ['user_id', 'contact_id', 'template_id', 'email', 'subject', 'status', 'error', 'sent_at'];

    protected function casts(): array
    {
        return ['status' => MailStatus::class, 'sent_at' => 'datetime'];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class)->withTrashed();
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class)->withTrashed();
    }
}
