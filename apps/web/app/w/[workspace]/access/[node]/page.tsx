'use client';

import {use,useEffect,useState} from 'react';
import Link from 'next/link';
import {api} from '@/lib/api';

type Member={id:string;name:string;email:string;role:string};
type Grant={user_id:string;permission:'view'|'edit'};
type Access={node_id:string;visibility:'workspace'|'restricted';permissions:{user_id:string;permission:'view'|'edit';user:Member}[]};

export default function AccessPage({params}:{params:Promise<{workspace:string;node:string}>}){
  const {workspace,node}=use(params);
  const [members,setMembers]=useState<Member[]>([]);
  const [visibility,setVisibility]=useState<'workspace'|'restricted'>('workspace');
  const [grants,setGrants]=useState<Grant[]>([]);
  const [saved,setSaved]=useState(true);
  const [error,setError]=useState('');

  useEffect(()=>{
    Promise.all([
      api<Member[]>(`/workspaces/${workspace}/members`),
      api<Access>(`/workspaces/${workspace}/nodes/${node}/permissions`)
    ]).then(([m,a])=>{setMembers(m);setVisibility(a.visibility);setGrants(a.permissions.map(p=>({user_id:p.user_id,permission:p.permission})))})
      .catch(e=>setError(String(e)));
  },[workspace,node]);

  function setGrant(userId:string,value:string){
    setSaved(false);
    setGrants(current=>{
      const rest=current.filter(g=>g.user_id!==userId);
      if(value==='none')return rest;
      return [...rest,{user_id:userId,permission:value as 'view'|'edit'}];
    });
  }

  async function save(){
    setError('');
    try{
      await api(`/workspaces/${workspace}/nodes/${node}/permissions`,{
        method:'PUT',
        body:JSON.stringify({visibility,grants:visibility==='restricted'?grants:[]})
      });
      setSaved(true);
    }catch(e){setError(String(e))}
  }

  return <div className="grid">
    <aside className="sidebar stack"><h2>Dataroom</h2><Link href={`/w/${workspace}/browse`}>← Browse</Link><Link href={`/w/${workspace}/settings`}>Workspace settings</Link></aside>
    <main className="main accessMain">
      <div className="topbar"><div><h1>Access</h1><p className="muted">Control who can see or edit this item and its children.</p></div><button className="btn" onClick={save}>{saved?'Saved':'Save changes'}</button></div>

      <div className="card stack">
        <label className="accessOption"><input type="radio" checked={visibility==='workspace'} onChange={()=>{setVisibility('workspace');setSaved(false)}}/><span><b>Everyone in the workspace</b><small className="muted">Workspace roles apply normally.</small></span></label>
        <label className="accessOption"><input type="radio" checked={visibility==='restricted'} onChange={()=>{setVisibility('restricted');setSaved(false)}}/><span><b>Restricted</b><small className="muted">Only admins, owners and explicitly granted members can access it.</small></span></label>
      </div>

      {visibility==='restricted'&&<div className="card browserCard settingsSection">
        {members.filter(m=>!['owner','admin'].includes(m.role)).map(m=>{
          const grant=grants.find(g=>g.user_id===m.id)?.permission??'none';
          return <div className="node" key={m.id}><div><b>{m.name}</b><div className="muted">{m.email} · {m.role}</div></div><select className="roleControl" value={grant} onChange={e=>setGrant(m.id,e.target.value)}><option value="none">No access</option><option value="view">Can view</option>{m.role!=='viewer'&&<option value="edit">Can edit</option>}</select></div>
        })}
      </div>}
      {error&&<pre>{error}</pre>}
    </main>
  </div>;
}
