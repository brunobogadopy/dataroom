<?php

namespace App\Services;

use RuntimeException;

class Vector
{
    public static function literal(array $values): string
    {
        $expected=(int)config('dataroom.ai.embedding_dimensions',1536);
        if(count($values)!==$expected)throw new RuntimeException("Embedding dimension mismatch: expected {$expected}, got ".count($values).'.');

        foreach($values as $value){
            if(!is_numeric($value))throw new RuntimeException('Embedding contains a non-numeric value.');
        }

        return '['.implode(',',array_map(fn($v)=>(string)(float)$v,$values)).']';
    }
}
