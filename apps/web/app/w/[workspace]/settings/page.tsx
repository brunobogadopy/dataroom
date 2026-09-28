'use client';

import {FormEvent,use,useEffect,useState} from 'react';
import Link from 'next/link';
import {api} from '@/lib/api';

type Member={id:string;name:string;email:string;role:'owner'|'admin'|'member'|'viewer'};
type Invite={id:string;email:string;role:string;expires_at:string};
type Workspace={id:string;name:string;slug:string;my_role:string};

export default function Settings({params}:{params:Promise<{workspace:string}>}){
  const {workspace}=use(params);
  const [ws,setWs]=useState<Workspace|null>(null);
  const [members,setMembers]=useState<Member[]>([]);
  const [invites,setInvites]=useState<Invite[]>([]);
  const [inviteLink,setInviteLink]=useState('');
  const [error,setError]=useState('');

  async function load(){
    try{
      const w=await api<Workspace>(`/workspaces/${workspace}`);
      setWs(w);
      setMembers(await api<Member[]>(`/workspaces/${workspace}/members`));
      if(['owner','admin'].includes(w.my_role)){
        setInvites(await api<Invite[]>(`/workspaces/${workspace}/invitations`));
      }
    }catch(e){setError(String(e))}
  }
  useEffect(()=>{load()},[workspace]);

  async function invite(e:FormEvent<HTMLFormElement>){
    e.preventDefault();setError('');
    const f=new FormData(e.currentTarget);
    try{
      const result=await api<{token:string}>(`/workspaces/${workspace}/invitations`,{
        method:'POST',
        body:JSON.stringify({email:f.get('email'),role:f.get('role')})
      });
      setInviteLink(`${window.location.origin}/invite/${result.token}`);
      e.currentTarget.reset();load();
    }catch(e){setError(String(e))}
  }

  async function role(userId:string,role:string){
    await api(`/workspaces/${workspace}/members/${userId}`,{method:'PUT',body:JSON.stringify({role})});
    load();
  }

  async function remove(userId:string){
    if(!confirm('Remove this member from the workspace?'))return;
    await api(`/workspaces/${workspace}/members/${userId}`,{method:'DELETE'});load();
  }

  const canManage=ws&&['owner','admin'].includes(ws.my_role);

  return <div className="grid">
    <aside className="sidebar stack">
      <h2>Dataroom</h2>
      <Link href={`/w/${workspace}/browse`}>Browse</Link>
      <Link href={`/w/${workspace}/search`}>Search</Link>
      <b>Settings</b>
    </aside>
    <main className="main">
      <div className="topbar"><div><h1>{ws?.name??'Workspace'} settings</h1><p className="muted">Members, roles and invitations.</p></div><span className="badge">{ws?.my_role}</span></div>

      {canManage&&<section className="card stack">
        <h2>Invite someone</h2>
        <form className="row" onSubmit={invite}>
          <input className="input" name="email" type="email" placeholder="person@company.com" required/>
          <select className="input roleSelect" name="role" defaultValue="member">
            <option value="admin">Admin</option><option value="member">Member</option><option value="viewer">Viewer</option>
          </select>
          <button className="btn">Create invite</button>
        </form>
        {inviteLink&&<div className="inviteLink"><input className="input" value={inviteLink} readOnly/><button className="btn secondary" onClick={()=>navigator.clipboard.writeText(inviteLink)}>Copy link</button></div>}
        <p className="muted">Invite links expire after 7 days and only work for the invited email address.</p>
      </section>}

      <section className="card browserCard settingsSection">
        <div className="sectionHeader"><h2>Members</h2><span className="muted">{members.length}</span></div>
        {members.map(m=><div className="node" key={m.id}>
          <div><b>{m.name}</b><div className="muted">{m.email}</div></div>
          <div className="row">
            {canManage&&m.role!=='owner'?<select className="roleControl" value={m.role} onChange={e=>role(m.id,e.target.value)}><option value="admin">Admin</option><option value="member">Member</option><option value="viewer">Viewer</option></select>:<span className="badge">{m.role}</span>}
            {canManage&&m.role!=='owner'&&<button className="dangerLink" onClick={()=>remove(m.id)}>Remove</button>}
          </div>
        </div>)}
      </section>

      {canManage&&invites.length>0&&<section className="card browserCard settingsSection">
        <div className="sectionHeader"><h2>Pending invitations</h2></div>
        {invites.map(i=><div className="node" key={i.id}><div><b>{i.email}</b><div className="muted">Expires {new Date(i.expires_at).toLocaleDateString()}</div></div><span className="badge">{i.role}</span></div>)}
      </section>}

      {error&&<pre>{error}</pre>}
    </main>
  </div>;
}
