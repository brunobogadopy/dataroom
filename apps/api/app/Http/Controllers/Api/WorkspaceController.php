<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WorkspaceController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()->workspaces()->orderBy('name')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120']]);
        return DB::transaction(function () use ($request, $data) {
            $base = Str::slug($data['name']) ?: 'workspace';
            $slug = $base;
            $n = 2;
            while (Workspace::where('slug', $slug)->exists()) $slug = $base.'-'.$n++;
            $workspace = Workspace::create([
                'name' => $data['name'], 'slug' => $slug,
                'owner_user_id' => $request->user()->id,
                'storage_quota_bytes' => 107374182400,
            ]);
            $workspace->members()->attach($request->user()->id, ['role' => 'owner']);
            return response()->json($workspace, 201);
        });
    }

    public function show(Request $request, Workspace $workspace)
    {
        abort_unless($workspace->members()->whereKey($request->user()->id)->exists(), 403);
        return $workspace;
    }
}
