'use client';

import {use,useEffect,useState} from 'react';
import Link from 'next/link';
import {api} from '@/lib/api';

type Node={id:string;type:'folder'|'document'|'file';name:string;deleted_at?:string};
type Activity={id:string;action:string;created_at:string;actor?:{name:string}|null;node?:{id:string;name:string;type:string}|null;metadata?:Record<string,unknown>|null};

export default function Library({params}:{params:Promise<{workspace:string}>}){
  const {workspace}=use(params);
  const [tab,setTab]=useState<'favorites'|'recent'|'trash'|'activity'>('favorites');
  const [nodes,setNodes]=useState<Node[]>([]);
  const [activity,setActivity]=useState<Activity[]>([]);
  const [error,setError]=useState('');

  async function load(next=tab){
    setError('');
    try{
      if(next==='activity'){setActivity(await api<Activity[]>(`/workspaces/${workspace}/activity`));setNodes([])}
      else{setNodes(await api<Node[]>(`/workspaces/${workspace}/${next}`));setActivity([])}
    }catch(e){setError(e instanceof Error?e.message:'Something went wrong.')}
  }
  useEffect(()=>{load()},[workspace,tab]);

  function href(n:Node){
    if(n.type==='folder')return `/w/${workspace}/browse?folder=${n.id}`;
    if(n.type==='document')return `/w/${workspace}/doc/${n.id}`;
    return `/w/${workspace}/file/${n.id}`;
  }

  async function restore(id:string){await api(`/trash/${id}/restore`,{method:'POST'});load()}
  async function erase(id:string){if(confirm('Permanently delete this item? This cannot be undone.')){await api(`/trash/${id}`,{method:'DELETE'});load()}}

  return <div className="grid">
    <aside className="sidebar stack"><h2>Dataroom</h2><Link href={`/w/${workspace}/browse`}>Browse</Link><b>Library</b><Link href="/notifications">Notifications</Link><Link href={`/w/${workspace}/search`}>Search</Link><Link href={`/w/${workspace}/settings`}>Settings</Link></aside>
    <main className="main">
      <div className="topbar"><div><h1>Library</h1><p className="muted">Your shortcuts, history and recovery tools.</p></div></div>
      <div className="tabs">{(['favorites','recent','trash','activity'] as const).map(t=><button className={tab===t?'tab active':'tab'} key={t} onClick={()=>setTab(t)}>{t[0].toUpperCase()+t.slice(1)}</button>)}</div>

      {tab!=='activity'&&<div className="card browserCard">
        {nodes.map(n=><div className="node" key={n.id}>
          <Link className="nodeTitle" href={tab==='trash'?'#':href(n)}><span>{n.type==='folder'?'📁':n.type==='document'?'📄':'📎'}</span><span>{n.name}</span></Link>
          {tab==='trash'?<div className="row"><button className="linkButton" onClick={()=>restore(n.id)}>Restore</button><button className="dangerLink" onClick={()=>erase(n.id)}>Delete forever</button></div>:<small className="muted">{n.type}</small>}
        </div>)}
        {!nodes.length&&<div className="emptyState">Nothing here yet.</div>}
      </div>}

      {tab==='activity'&&<div className="card browserCard">
        {activity.map(a=><div className="node" key={a.id}><div><b>{a.actor?.name??'System'}</b> <span>{a.action.replaceAll('.',' ')}</span>{a.node&&<> <b>{a.node.name}</b></>}<div className="muted">{new Date(a.created_at).toLocaleString()}</div></div></div>)}
        {!activity.length&&<div className="emptyState">No activity yet.</div>}
      </div>}
      {error&&<pre>{error}</pre>}
    </main>
  </div>;
}
