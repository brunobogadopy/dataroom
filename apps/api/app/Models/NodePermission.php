<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class NodePermission extends Model
{
    use HasUuids;

    protected $fillable = ['workspace_id', 'node_id', 'user_id', 'permission'];

    public function node() { return $this->belongsTo(Node::class); }
    public function user() { return $this->belongsTo(User::class); }
}
