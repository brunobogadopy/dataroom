'use client';

import {DragEvent,FormEvent,use,useEffect,useRef,useState} from 'react';
import Link from 'next/link';
import {useRouter,useSearchParams} from 'next/navigation';
import {api,downloadFile,uploadFile} from '@/lib/api';

type Node={
  id:string;
  parent_id?:string|null;
  type:'folder'|'document'|'file';
  name:string;
  file?:{size_bytes:number;current_version?:{extraction_status:string}};
};
type Crumb={id:string;name:string};

export default function Browse({params}:{params:Promise<{workspace:string}>}){
  const {workspace}=use(params);
  const router=useRouter();
  const search=useSearchParams();
  const folderId=search.get('folder');
  const [nodes,setNodes]=useState<Node[]>([]);
  const [crumbs,setCrumbs]=useState<Crumb[]>([]);
  const [error,setError]=useState('');
  const [progress,setProgress]=useState<number|null>(null);
  const input=useRef<HTMLInputElement>(null);

  async function load(){
    try{
      setError('');
      const query=folderId?`?parent_id=${encodeURIComponent(folderId)}`:'';
      const items=await api<Node[]>(`/workspaces/${workspace}/nodes${query}`);
      setNodes(items);
      if(folderId){
        setCrumbs(await api<Crumb[]>(`/workspaces/${workspace}/folders/${folderId}/breadcrumbs`));
      }else{
        setCrumbs([]);
      }
    }catch(e){setError(e instanceof Error?e.message:'Something went wrong.')}
  }

  useEffect(()=>{load()},[workspace,folderId]);

  async function folder(e:FormEvent<HTMLFormElement>){
    e.preventDefault();
    const f=new FormData(e.currentTarget);
    await api(`/workspaces/${workspace}/folders`,{
      method:'POST',
      body:JSON.stringify({name:f.get('name'),parent_id:folderId})
    });
    e.currentTarget.reset();
    load();
  }

  async function doc(){
    const created=await api<Node>(`/workspaces/${workspace}/documents`,{
      method:'POST',
      body:JSON.stringify({
        name:'Untitled document',
        parent_id:folderId,
        content_json:{type:'doc',content:[{type:'paragraph'}]},
        plain_text:''
      })
    });
    router.push(`/w/${workspace}/doc/${created.id}`);
  }

  async function send(file:File){
    setError('');
    setProgress(0);
    try{
      await uploadFile(`/workspaces/${workspace}/files`,file,folderId,setProgress);
      await load();
    }catch(e){setError(e instanceof Error?e.message:'Something went wrong.')}
    finally{
      setProgress(null);
      if(input.current)input.current.value='';
    }
  }

  function drop(e:DragEvent){
    e.preventDefault();
    const file=e.dataTransfer.files?.[0];
    if(file)send(file);
  }

  function openNode(node:Node){
    if(node.type==='folder')router.push(`/w/${workspace}/browse?folder=${node.id}`);
    if(node.type==='document')router.push(`/w/${workspace}/doc/${node.id}`);
    if(node.type==='file')router.push(`/w/${workspace}/file/${node.id}`);
  }

  return <div className="grid">
    <aside className="sidebar stack">
      <h2>Dataroom</h2>
      <b>Browse</b>
      <Link href={`/w/${workspace}/search`}>Search</Link>
    </aside>
    <main className="main">
      <div className="breadcrumbs">
        <button className="crumb" onClick={()=>router.push(`/w/${workspace}/browse`)}>Company knowledge</button>
        {crumbs.map(c=><span className="row" key={c.id}><span className="muted">/</span><button className="crumb" onClick={()=>router.push(`/w/${workspace}/browse?folder=${c.id}`)}>{c.name}</button></span>)}
      </div>

      <div className="topbar">
        <div>
          <h1>{crumbs.at(-1)?.name??'Company knowledge'}</h1>
          <p className="muted">Folders, documents and files in one place.</p>
        </div>
        <div className="row">
          <input ref={input} type="file" hidden onChange={e=>{const f=e.target.files?.[0];if(f)send(f)}}/>
          <button className="btn secondary" onClick={()=>input.current?.click()}>↑ Upload</button>
          <button className="btn" onClick={doc}>+ Document</button>
        </div>
      </div>

      <form className="row" onSubmit={folder}>
        <input className="input" name="name" placeholder="New folder name"/>
        <button className="btn secondary">+ Folder</button>
      </form>

      <div className="dropzone" onDragOver={e=>e.preventDefault()} onDrop={drop} onClick={()=>input.current?.click()}>
        <b>Drop a file here</b>
        <span className="muted"> or click to upload</span>
        {progress!==null&&<div className="progress"><span style={{width:`${progress}%`}}/></div>}
      </div>

      <div className="card browserCard">
        {nodes.map(n=><div className="node clickable" key={n.id} onDoubleClick={()=>openNode(n)}>
          <button className="nodeTitle" onClick={()=>openNode(n)}>
            <span>{n.type==='folder'?'📁':n.type==='document'?'📄':'📎'}</span>
            <span>{n.name}</span>
          </button>
          <div className="row">
            {n.type==='file'&&<>
              <small className="muted">{n.file?.current_version?.extraction_status??'pending'}</small>
              <button className="linkButton" onClick={()=>downloadFile(n.id,n.name)}>Download</button>
            </>}
            <small className="muted">{n.type}</small>
          </div>
        </div>)}
        {!nodes.length&&<div className="emptyState"><b>This folder is empty.</b><span className="muted">Create a document, folder, or upload a file.</span></div>}
      </div>
      {error&&<pre>{error}</pre>}
    </main>
  </div>;
}
