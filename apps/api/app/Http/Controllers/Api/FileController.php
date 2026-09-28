<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ExtractFileText;
use App\Models\FileVersion;
use App\Models\Node;
use App\Models\StoredFile;
use App\Models\Workspace;
use App\Services\BunnyStorage;
use App\Services\NodeAccess;
use App\Services\SearchIndex;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Throwable;

class FileController extends Controller
{
    public function store(Request $request, Workspace $workspace, BunnyStorage $storage, SearchIndex $index, NodeAccess $access)
    {
        $maxKb=max(1,(int)config('dataroom.max_upload_mb'))*1024;
        $data=$request->validate(['file'=>['required','file','max:'.$maxKb],'parent_id'=>['nullable','uuid']]);
        $parent=!empty($data['parent_id'])?$workspace->nodes()->whereKey($data['parent_id'])->where('type','folder')->firstOrFail():null;
        abort_unless($access->canCreateIn($request->user(),$workspace,$parent),403);

        $uploaded=$request->file('file');
        $used=(int)StoredFile::query()->whereHas('node',fn($q)=>$q->where('workspace_id',$workspace->id))->sum('size_bytes');
        abort_if($used+$uploaded->getSize()>$workspace->storage_quota_bytes,422,'Workspace storage quota exceeded.');

        $nodeId=(string)Str::uuid();$versionId=(string)Str::uuid();
        $extension=strtolower($uploaded->getClientOriginalExtension());
        $safeExtension=preg_match('/^[a-z0-9]{1,10}$/',$extension)?'.'.$extension:'';
        $objectKey=sprintf('workspaces/%s/files/%s/%s%s',$workspace->id,$nodeId,$versionId,$safeExtension);
        $storage->upload($uploaded,$objectKey);

        try{
            $node=DB::transaction(function()use($request,$workspace,$uploaded,$nodeId,$versionId,$objectKey,$parent){
                $node=Node::create(['id'=>$nodeId,'workspace_id'=>$workspace->id,'parent_id'=>$parent?->id,'type'=>'file','name'=>$uploaded->getClientOriginalName(),'slug'=>Str::slug(pathinfo($uploaded->getClientOriginalName(),PATHINFO_FILENAME)).'-'.Str::lower(Str::random(6)),'created_by'=>$request->user()->id]);
                $version=FileVersion::create(['id'=>$versionId,'node_id'=>$node->id,'version'=>1,'object_key'=>$objectKey,'mime_type'=>$uploaded->getMimeType()?:'application/octet-stream','size_bytes'=>$uploaded->getSize(),'sha256'=>hash_file('sha256',$uploaded->getRealPath()),'extraction_status'=>'pending','created_by'=>$request->user()->id,'created_at'=>now()]);
                StoredFile::create(['node_id'=>$node->id,'current_version_id'=>$version->id,'mime_type'=>$version->mime_type,'size_bytes'=>$version->size_bytes]);
                return $node->load('file.currentVersion');
            });
        }catch(Throwable $e){try{$storage->delete($objectKey);}catch(Throwable){}throw $e;}

        $index->indexNode($node);ExtractFileText::dispatch($versionId);return response()->json($node,201);
    }

    public function show(Request $request, Node $node, NodeAccess $access)
    {
        abort_unless($node->type==='file',404);abort_unless($access->canView($request->user(),$node),403);return $node->load('file.currentVersion');
    }

    public function preview(Request $request, Node $node, BunnyStorage $storage, NodeAccess $access)
    {
        abort_unless($node->type==='file',404);abort_unless($access->canView($request->user(),$node),403);
        $node->load('file.currentVersion');abort_unless($node->file?->currentVersion,404);
        $mime=$node->file->currentVersion->mime_type;
        abort_unless($mime==='application/pdf'||str_starts_with($mime,'image/')||str_starts_with($mime,'text/'),415,'Preview is not supported for this file type.');
        $tmp=tempnam(sys_get_temp_dir(),'dataroom-preview-');abort_unless($tmp,500);$storage->downloadTo($node->file->currentVersion->object_key,$tmp);
        return response()->file($tmp,['Content-Type'=>$mime,'Content-Disposition'=>(new ResponseHeaderBag())->makeDisposition('inline',$node->name)])->deleteFileAfterSend(true);
    }

    public function download(Request $request, Node $node, BunnyStorage $storage, NodeAccess $access)
    {
        abort_unless($node->type==='file',404);abort_unless($access->canView($request->user(),$node),403);
        $node->load('file.currentVersion');abort_unless($node->file?->currentVersion,404);
        $tmp=tempnam(sys_get_temp_dir(),'dataroom-download-');abort_unless($tmp,500);$storage->downloadTo($node->file->currentVersion->object_key,$tmp);
        return response()->download($tmp,$node->name,['Content-Type'=>$node->file->currentVersion->mime_type])->deleteFileAfterSend(true);
    }
}
