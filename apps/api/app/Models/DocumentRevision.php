<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DocumentRevision extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'node_id', 'version', 'content_json', 'plain_text', 'created_by', 'created_at',
    ];

    protected $casts = [
        'content_json' => 'array',
        'created_at' => 'datetime',
    ];

    public function node() { return $this->belongsTo(Node::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
