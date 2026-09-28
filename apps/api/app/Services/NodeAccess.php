<?php

namespace App\Services;

use App\Models\Node;
use App\Models\User;
use App\Models\Workspace;

class NodeAccess
{
    public function role(User $user, Workspace $workspace): ?string
    {
        if ($workspace->owner_user_id === $user->id) return 'owner';
        return $workspace->members()->whereKey($user->id)->first()?->pivot?->role;
    }

    public function isMember(User $user, Workspace $workspace): bool
    {
        return $this->role($user, $workspace) !== null;
    }

    public function canManageWorkspace(User $user, Workspace $workspace): bool
    {
        return in_array($this->role($user, $workspace), ['owner', 'admin'], true);
    }

    public function canView(User $user, Node $node): bool
    {
        $role=$this->role($user,$node->workspace);
        if($role===null)return false;
        if(in_array($role,['owner','admin'],true))return true;

        foreach($this->restrictedPath($node) as $restricted){
            $allowed=$restricted->permissions()->where('user_id',$user->id)->whereIn('permission',['view','edit'])->exists();
            if(!$allowed)return false;
        }
        return true;
    }

    public function canEdit(User $user, Node $node): bool
    {
        $role=$this->role($user,$node->workspace);
        if($role===null||$role==='viewer')return false;
        if(in_array($role,['owner','admin'],true))return true;

        foreach($this->restrictedPath($node) as $restricted){
            if(!$restricted->permissions()->where('user_id',$user->id)->where('permission','edit')->exists())return false;
        }
        return true;
    }

    public function canCreateIn(User $user, Workspace $workspace, ?Node $parent): bool
    {
        $role=$this->role($user,$workspace);
        if($role===null||$role==='viewer')return false;
        if(!$parent)return true;
        return $this->canEdit($user,$parent);
    }

    private function restrictedPath(Node $node): array
    {
        $restricted=[];$current=$node;
        while($current){
            if($current->visibility==='restricted')$restricted[]=$current;
            $current=$current->parent;
        }
        return array_reverse($restricted);
    }
}
