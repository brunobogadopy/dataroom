<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoredFile extends Model
{
    protected $table = 'files';
    protected $primaryKey = 'node_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['node_id', 'current_version_id', 'mime_type', 'size_bytes'];

    public function node() { return $this->belongsTo(Node::class, 'node_id'); }
    public function currentVersion() { return $this->belongsTo(FileVersion::class, 'current_version_id'); }
    public function versions() { return $this->hasMany(FileVersion::class, 'node_id'); }
}
