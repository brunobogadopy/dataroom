<?php

namespace App\Services;

class TextChunker
{
    public function chunks(string $text): array
    {
        $text=trim(preg_replace('/\r\n?/',"\n",$text));
        if($text==='')return [];

        $size=max(500,(int)config('dataroom.ai.chunk_chars',2400));
        $overlap=min($size-1,max(0,(int)config('dataroom.ai.chunk_overlap_chars',300)));

        $chunks=[];$offset=0;$length=mb_strlen($text);

        while($offset<$length){
            $piece=mb_substr($text,$offset,$size);

            if($offset+$size<$length){
                $lastBreak=max(
                    (int)mb_strrpos($piece,"\n\n"),
                    (int)mb_strrpos($piece,"\n"),
                    (int)mb_strrpos($piece,'. ')
                );
                if($lastBreak>(int)($size*0.55))$piece=mb_substr($piece,0,$lastBreak+1);
            }

            $piece=trim($piece);
            if($piece!=='')$chunks[]=$piece;

            $advance=max(1,mb_strlen($piece)-$overlap);
            $offset+=$advance;
        }

        return $chunks;
    }
}
