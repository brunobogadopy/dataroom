'use client';

import {use,useEffect,useState} from 'react';
import Link from 'next/link';
import {api,downloadFile,previewFile} from '@/lib/api';

type FileNode={
  id:string;
  parent_id:string|null;
  name:string;
  file:{
    mime_type:string;
    size_bytes:number;
    current_version:{
      mime_type:string;
      size_bytes:number;
      extraction_status:string;
      extracted_text?:string|null;
    };
  };
};

export default function FilePage({params}:{params:Promise<{workspace:string;node:string}>}){
  const {workspace,node}=use(params);
  const [file,setFile]=useState<FileNode|null>(null);
  const [preview,setPreview]=useState<string|null>(null);
  const [previewError,setPreviewError]=useState(false);

  useEffect(()=>{
    let url:string|null=null;
    api<FileNode>(`/files/${node}`).then(async data=>{
      setFile(data);
      try{
        url=await previewFile(node);
        setPreview(url);
      }catch{
        setPreviewError(true);
      }
    });
    return()=>{if(url)URL.revokeObjectURL(url)};
  },[node]);

  if(!file)return <div className="shell">Loading…</div>;

  const mime=file.file.current_version.mime_type;
  const isImage=mime.startsWith('image/');
  const isPdf=mime==='application/pdf';
  const isText=mime.startsWith('text/');
  const size=(file.file.current_version.size_bytes/1024/1024).toFixed(2);

  return <div className="grid">
    <aside className="sidebar stack">
      <h2>Dataroom</h2>
      <Link href={file.parent_id?`/w/${workspace}/browse?folder=${file.parent_id}`:`/w/${workspace}/browse`}>← Browse</Link>
      <Link href={`/w/${workspace}/search`}>Search</Link>
    </aside>
    <main className="main">
      <div className="topbar">
        <div>
          <h1>{file.name}</h1>
          <p className="muted">{mime} · {size} MB · {file.file.current_version.extraction_status}</p>
        </div>
        <button className="btn" onClick={()=>downloadFile(file.id,file.name)}>Download</button>
      </div>

      <div className="previewCard">
        {preview&&isImage&&<img className="imagePreview" src={preview} alt={file.name}/>}
        {preview&&isPdf&&<iframe className="filePreview" src={preview} title={file.name}/>}
        {preview&&isText&&<iframe className="filePreview" src={preview} title={file.name}/>}
        {!preview&&!previewError&&<div className="emptyState">Loading preview…</div>}
        {previewError&&<div className="emptyState"><b>Preview unavailable.</b><span className="muted">You can still download this file.</span></div>}
      </div>

      {file.file.current_version.extracted_text&&<div className="card extractedText">
        <h3>Extracted text</h3>
        <p>{file.file.current_version.extracted_text.slice(0,4000)}</p>
      </div>}
    </main>
  </div>;
}
