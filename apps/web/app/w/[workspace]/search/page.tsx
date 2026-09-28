'use client';

import {FormEvent,useState,use} from 'react';
import Link from 'next/link';
import {api} from '@/lib/api';

type Result={node_id:string;type:'folder'|'document'|'file';name:string;snippet?:string};

export default function Search({params}:{params:Promise<{workspace:string}>}){
  const {workspace}=use(params);
  const [results,setResults]=useState<Result[]>([]);
  const [q,setQ]=useState('');

  async function submit(e:FormEvent){
    e.preventDefault();
    const r=await api<{results:Result[]}>(`/workspaces/${workspace}/search?q=${encodeURIComponent(q)}`);
    setResults(r.results);
  }

  function href(r:Result){
    if(r.type==='document')return `/w/${workspace}/doc/${r.node_id}`;
    if(r.type==='file')return `/w/${workspace}/file/${r.node_id}`;
    return `/w/${workspace}/browse?folder=${r.node_id}`;
  }

  return <div className="grid">
    <aside className="sidebar stack">
      <h2>Dataroom</h2>
      <Link href={`/w/${workspace}/browse`}>Browse</Link>
      <Link href={`/w/${workspace}/ask`}>Ask Dataroom</Link>
      <b>Search</b>
    </aside>
    <main className="main">
      <h1>Search everything</h1>
      <form className="row" onSubmit={submit}>
        <input className="input" value={q} onChange={e=>setQ(e.target.value)} placeholder="Search documents and files..."/>
        <button className="btn">Search</button>
      </form>
      <div className="card searchResults">
        {results.map(r=><Link className="node clickable" href={href(r)} key={r.node_id}>
          <div>
            <b>{r.type==='folder'?'📁 ':r.type==='document'?'📄 ':'📎 '}{r.name}</b>
            <div className="muted resultSnippet">{r.snippet?.replace(/<\/?mark>/g,'')}</div>
          </div>
          <small>{r.type}</small>
        </Link>)}
        {!results.length&&q&&<p className="muted">No results yet.</p>}
      </div>
    </main>
  </div>;
}
