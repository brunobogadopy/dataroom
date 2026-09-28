<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasUuids;

    public $timestamps=false;
    protected $fillable=['user_id','workspace_id','node_id','type','data','read_at','created_at'];
    protected $casts=['data'=>'array','read_at'=>'datetime','created_at'=>'datetime'];

    public function node(){return $this->belongsTo(Node::class);}
}
