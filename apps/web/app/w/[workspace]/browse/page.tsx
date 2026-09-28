'use client';

import {DragEvent,FormEvent,use,useEffect,useRef,useState} from 'react';
import Link from 'next/link';
import {useRouter,useSearchParams} from 'next/navigation';
import {api,downloadFile,uploadFile} from '@/lib/api';

type Node={id:string;parent_id?:string|null;type:'folder'|'document'|'file';name:string;visibility?:'workspace'|'restricted';can_edit?:boolean;is_favorite?:boolean;file?:{size_bytes:number;current_version?:{extraction_status:string}}};
type Crumb={id:string;name:string};
type Workspace={id:string;name:string;slug:string;my_role:'owner'|'admin'|'member'|'viewer'};

export default function Browse({params}:{params:Promise<{workspace:string}>}){
  const {workspace}=use(params);const router=useRouter();const search=useSearchParams();const folderId=search.get('folder');
  const [nodes,setNodes]=useState<Node[]>([]);const [crumbs,setCrumbs]=useState<Crumb[]>([]);const [ws,setWs]=useState<Workspace|null>(null);
  const [error,setError]=useState('');const [progress,setProgress]=useState<number|null>(null);const input=useRef<HTMLInputElement>(null);

  async function load(){
    try{
      setError('');
      const [workspaceData,items]=await Promise.all([
        api<Workspace>(`/workspaces/${workspace}`),
        api<Node[]>(`/workspaces/${workspace}/nodes${folderId?`?parent_id=${encodeURIComponent(folderId)}`:''}`)
      ]);
      setWs(workspaceData);setNodes(items);
      setCrumbs(folderId?await api<Crumb[]>(`/workspaces/${workspace}/folders/${folderId}/breadcrumbs`):[]);
    }catch(e){setError(e instanceof Error?e.message:'Something went wrong.')}
  }
  useEffect(()=>{load()},[workspace,folderId]);

  const canWrite=ws?.my_role!=='viewer';const canManage=ws&&['owner','admin'].includes(ws.my_role);

  async function folder(e:FormEvent<HTMLFormElement>){e.preventDefault();const f=new FormData(e.currentTarget);await api(`/workspaces/${workspace}/folders`,{method:'POST',body:JSON.stringify({name:f.get('name'),parent_id:folderId})});e.currentTarget.reset();load()}
  async function doc(){const created=await api<Node>(`/workspaces/${workspace}/documents`,{method:'POST',body:JSON.stringify({name:'Untitled document',parent_id:folderId,content_json:{type:'doc',content:[{type:'paragraph'}]},plain_text:''})});router.push(`/w/${workspace}/doc/${created.id}`)}
  async function send(file:File){setError('');setProgress(0);try{await uploadFile(`/workspaces/${workspace}/files`,file,folderId,setProgress);await load()}catch(e){setError(e instanceof Error?e.message:'Something went wrong.')}finally{setProgress(null);if(input.current)input.current.value=''}}
  function drop(e:DragEvent){e.preventDefault();if(!canWrite)return;const file=e.dataTransfer.files?.[0];if(file)send(file)}
  function openNode(node:Node){if(node.type==='folder')router.push(`/w/${workspace}/browse?folder=${node.id}`);if(node.type==='document')router.push(`/w/${workspace}/doc/${node.id}`);if(node.type==='file')router.push(`/w/${workspace}/file/${node.id}`)}
  async function favorite(id:string){await api(`/nodes/${id}/favorite`,{method:'POST'});load()}
  async function trash(id:string){if(!confirm('Move this item to trash?'))return;await api(`/nodes/${id}`,{method:'DELETE'});load()}

  return <div className="grid">
    <aside className="sidebar stack"><h2>Dataroom</h2><b>Browse</b><Link href={`/w/${workspace}/library`}>Library</Link><Link href="/notifications">Notifications</Link><Link href={`/w/${workspace}/ask`}>Ask Dataroom</Link><Link href={`/w/${workspace}/search`}>Search</Link><Link href={`/w/${workspace}/settings`}>Settings</Link></aside>
    <main className="main">
      <div className="breadcrumbs"><button className="crumb" onClick={()=>router.push(`/w/${workspace}/browse`)}>Company knowledge</button>{crumbs.map(c=><span className="row" key={c.id}><span className="muted">/</span><button className="crumb" onClick={()=>router.push(`/w/${workspace}/browse?folder=${c.id}`)}>{c.name}</button></span>)}</div>
      <div className="topbar"><div><h1>{crumbs.at(-1)?.name??'Company knowledge'}</h1><p className="muted">{ws?.name} · {ws?.my_role}</p></div>{canWrite&&<div className="row"><input ref={input} type="file" hidden onChange={e=>{const f=e.target.files?.[0];if(f)send(f)}}/><button className="btn secondary" onClick={()=>input.current?.click()}>↑ Upload</button><button className="btn" onClick={doc}>+ Document</button></div>}</div>
      {canWrite&&<><form className="row" onSubmit={folder}><input className="input" name="name" placeholder="New folder name"/><button className="btn secondary">+ Folder</button></form><div className="dropzone" onDragOver={e=>e.preventDefault()} onDrop={drop} onClick={()=>input.current?.click()}><b>Drop a file here</b><span className="muted"> or click to upload</span>{progress!==null&&<div className="progress"><span style={{width:`${progress}%`}}/></div>}</div></>}
      <div className="card browserCard">{nodes.map(n=><div className="node clickable" key={n.id} onDoubleClick={()=>openNode(n)}>
        <button className="nodeTitle" onClick={()=>openNode(n)}><span>{n.type==='folder'?'📁':n.type==='document'?'📄':'📎'}</span><span>{n.name}</span>{n.visibility==='restricted'&&<span title="Restricted">🔒</span>}</button>
        <div className="row">
          <button className="iconButton" title="Toggle favorite" onClick={()=>favorite(n.id)}>{n.is_favorite?'★':'☆'}</button>
          {n.type==='file'&&<><small className="muted">{n.file?.current_version?.extraction_status??'pending'}</small><button className="linkButton" onClick={()=>downloadFile(n.id,n.name)}>Download</button></>}
          {canManage&&<Link className="linkButton" href={`/w/${workspace}/access/${n.id}`}>Access</Link>}
          {n.can_edit&&<button className="dangerLink" onClick={()=>trash(n.id)}>Trash</button>}
          <small className="muted">{n.type}</small>
        </div>
      </div>)}{!nodes.length&&<div className="emptyState"><b>This folder is empty.</b><span className="muted">{canWrite?'Create a document, folder, or upload a file.':'Nothing has been shared here yet.'}</span></div>}</div>
      {error&&<pre>{error}</pre>}
    </main>
  </div>;
}
