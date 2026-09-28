'use client';

import {useEffect,useState} from 'react';
import Link from 'next/link';
import {api} from '@/lib/api';

type Notification={
  id:string;type:string;read_at:string|null;created_at:string;workspace_id:string;node_id:string|null;
  data?:{actor_name?:string;node_name?:string;node_type?:'folder'|'document'|'file';workspace_slug?:string;body?:string}|null;
  node?:{id:string;type:string;name:string}|null
};

export default function Notifications(){
  const [items,setItems]=useState<Notification[]>([]);
  const [error,setError]=useState('');

  async function load(){try{setItems(await api<Notification[]>('/notifications'));setError('')}catch(e){setError(e instanceof Error?e.message:'Something went wrong.')}}
  useEffect(()=>{load()},[]);

  async function read(id:string){await api(`/notifications/${id}/read`,{method:'POST'});load()}
  async function readAll(){await api('/notifications/read-all',{method:'POST'});load()}

  function href(n:Notification){
    const slug=n.data?.workspace_slug;const type=n.data?.node_type;const id=n.node_id;
    if(!slug||!id)return '/workspaces';
    if(type==='document')return `/w/${slug}/doc/${id}`;
    if(type==='file')return `/w/${slug}/file/${id}`;
    return `/w/${slug}/browse?folder=${id}`;
  }

  return <main className="shell stack">
    <div className="topbar"><div><h1>Notifications</h1><p className="muted">Mentions and collaboration updates.</p></div><button className="btn secondary" onClick={readAll}>Mark all read</button></div>
    <div className="card browserCard">
      {items.map(n=><div className={n.read_at?'node':'node unreadNotification'} key={n.id}>
        <Link href={href(n)} onClick={()=>read(n.id)}>
          <b>{n.data?.actor_name??'Someone'} mentioned you</b>
          <div>{n.data?.node_name??n.node?.name??'Content'}</div>
          {n.data?.body&&<div className="muted resultSnippet">{n.data.body}</div>}
          <small className="muted">{new Date(n.created_at).toLocaleString()}</small>
        </Link>
        {!n.read_at&&<button className="linkButton" onClick={()=>read(n.id)}>Mark read</button>}
      </div>)}
      {!items.length&&<div className="emptyState">You have no notifications.</div>}
    </div>
    <Link className="linkButton" href="/workspaces">← Workspaces</Link>
    {error&&<pre>{error}</pre>}
  </main>;
}
