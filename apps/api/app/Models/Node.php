<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Node extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = ['workspace_id', 'parent_id', 'type', 'name', 'slug', 'created_by'];

    public function workspace() { return $this->belongsTo(Workspace::class); }
    public function parent() { return $this->belongsTo(Node::class, 'parent_id'); }
    public function children() { return $this->hasMany(Node::class, 'parent_id'); }
    public function document() { return $this->hasOne(Document::class); }
    public function file() { return $this->hasOne(StoredFile::class, 'node_id'); }
}
