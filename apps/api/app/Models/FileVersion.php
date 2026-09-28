<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FileVersion extends Model
{
    use HasUuids;

    public $timestamps = false;
    protected $fillable = [
        'node_id', 'version', 'object_key', 'mime_type', 'size_bytes', 'sha256',
        'extracted_text', 'extraction_status', 'extraction_error', 'created_by', 'created_at',
    ];
    protected $casts = ['created_at' => 'datetime'];

    public function node() { return $this->belongsTo(Node::class); }
}
