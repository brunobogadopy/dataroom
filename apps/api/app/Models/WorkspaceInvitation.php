<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class WorkspaceInvitation extends Model
{
    use HasUuids;

    protected $fillable = [
        'workspace_id', 'email', 'role', 'token_hash', 'invited_by', 'expires_at', 'accepted_at',
    ];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'accepted_at' => 'datetime'];
    }

    public function workspace() { return $this->belongsTo(Workspace::class); }
    public function inviter() { return $this->belongsTo(User::class, 'invited_by'); }
}
