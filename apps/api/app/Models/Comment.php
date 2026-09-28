<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasUuids;

    protected $fillable = ['workspace_id','node_id','user_id','body'];

    public function user(){return $this->belongsTo(User::class);}
    public function node(){return $this->belongsTo(Node::class);}
    public function mentions(){return $this->belongsToMany(User::class,'comment_mentions');}
}
