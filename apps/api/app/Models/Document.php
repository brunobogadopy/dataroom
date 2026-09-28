<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $primaryKey = 'node_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;
    protected $fillable = ['node_id', 'content_json', 'plain_text', 'status', 'owner_user_id', 'version'];
    protected $casts = ['content_json' => 'array'];

    public function node() { return $this->belongsTo(Node::class); }
}
