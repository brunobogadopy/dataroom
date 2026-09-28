'use client';

import {use,useEffect,useRef,useState} from 'react';
import Link from 'next/link';
import {api,downloadFile,previewFile,uploadFile} from '@/lib/api';
import CommentsPanel from '@/components/CommentsPanel';

type FileNode={id:string;parent_id:string|null;name:string;can_edit:boolean;file:{mime_type:string;size_bytes:number;current_version:{mime_type:string;size_bytes:number;extraction_status:string;extracted_text?:string|null}}};
type Version={id:string;version:number;mime_type:string;size_bytes:number;extraction_status:string;created_at:string};

export default function FilePage({params}:{params:Promise<{workspace:string;node:string}>}){
  const {workspace,node}=use(params);const [file,setFile]=useState<FileNode|null>(null);const [preview,setPreview]=useState<string|null>(null);const [previewError,setPreviewError]=useState(false);const [versions,setVersions]=useState<Version[]>([]);const [progress,setProgress]=useState<number|null>(null);const input=useRef<HTMLInputElement>(null);

  async function load(){let url:string|null=null;const data=await api<FileNode>(`/files/${node}`);setFile(data);setVersions(await api<Version[]>(`/files/${node}/versions`));try{url=await previewFile(node);setPreview(old=>{if(old)URL.revokeObjectURL(old);return url});setPreviewError(false)}catch{setPreviewError(true)}}
  useEffect(()=>{load();return()=>{if(preview)URL.revokeObjectURL(preview)}},[node]);
  async function newVersion(f:File){setProgress(0);try{await uploadFile(`/files/${node}/versions`,f,null,setProgress);await load()}finally{setProgress(null);if(input.current)input.current.value=''}}

  if(!file)return <div className="shell">Loading…</div>;
  const mime=file.file.current_version.mime_type;const isImage=mime.startsWith('image/');const isPdf=mime==='application/pdf';const isText=mime.startsWith('text/');const size=(file.file.current_version.size_bytes/1024/1024).toFixed(2);

  return <div className="grid">
    <aside className="sidebar stack"><h2>Dataroom</h2><Link href={file.parent_id?`/w/${workspace}/browse?folder=${file.parent_id}`:`/w/${workspace}/browse`}>← Browse</Link><Link href={`/w/${workspace}/library`}>Library</Link><Link href="/notifications">Notifications</Link><Link href={`/w/${workspace}/search`}>Search</Link></aside>
    <main className="main">
      <div className="topbar"><div><h1>{file.name}</h1><p className="muted">{mime} · {size} MB · {file.file.current_version.extraction_status}</p></div><div className="row">{file.can_edit&&<><input ref={input} hidden type="file" onChange={e=>{const f=e.target.files?.[0];if(f)newVersion(f)}}/><button className="btn secondary" onClick={()=>input.current?.click()}>Upload new version</button></>}<button className="btn" onClick={()=>downloadFile(file.id,file.name)}>Download</button></div></div>
      {progress!==null&&<div className="progress"><span style={{width:`${progress}%`}}/></div>}
      <div className="previewCard">{preview&&isImage&&<img className="imagePreview" src={preview} alt={file.name}/>} {preview&&isPdf&&<iframe className="filePreview" src={preview} title={file.name}/>} {preview&&isText&&<iframe className="filePreview" src={preview} title={file.name}/>} {!preview&&!previewError&&<div className="emptyState">Loading preview…</div>} {previewError&&<div className="emptyState"><b>Preview unavailable.</b><span className="muted">You can still download this file.</span></div>}</div>
      <div className="card versionsCard"><h3>Versions</h3>{versions.map(v=><div className="node" key={v.id}><div><b>Version {v.version}</b><div className="muted">{(v.size_bytes/1024/1024).toFixed(2)} MB · {new Date(v.created_at).toLocaleString()}</div></div><span className="badge">{v.extraction_status}</span></div>)}</div>
      {file.file.current_version.extracted_text&&<div className="card extractedText"><h3>Extracted text</h3><p>{file.file.current_version.extracted_text.slice(0,4000)}</p></div>}
      <CommentsPanel nodeId={node}/>
    </main>
  </div>;
}
