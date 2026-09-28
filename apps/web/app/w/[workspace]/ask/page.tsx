'use client';

import {FormEvent,use,useState} from 'react';
import Link from 'next/link';
import {api} from '@/lib/api';

type Source={
  label:string;
  node_id:string;
  type:'document'|'file';
  name:string;
  parent_id:string|null;
  snippet:string;
  score:number;
};

type Answer={
  question:string;
  answer:string;
  sources:Source[];
};

export default function AskDataroom({params}:{params:Promise<{workspace:string}>}){
  const {workspace}=use(params);
  const [question,setQuestion]=useState('');
  const [result,setResult]=useState<Answer|null>(null);
  const [busy,setBusy]=useState(false);
  const [error,setError]=useState('');

  async function submit(e:FormEvent){
    e.preventDefault();
    if(!question.trim())return;

    setBusy(true);setError('');
    try{
      setResult(await api<Answer>(`/workspaces/${workspace}/ask`,{
        method:'POST',
        body:JSON.stringify({question:question.trim()})
      }));
    }catch(e){
      setError(e instanceof Error?e.message:'Something went wrong.');
    }finally{
      setBusy(false);
    }
  }

  function href(source:Source){
    if(source.type==='document')return `/w/${workspace}/doc/${source.node_id}`;
    return `/w/${workspace}/file/${source.node_id}`;
  }

  return <div className="grid">
    <aside className="sidebar stack">
      <h2>Dataroom</h2>
      <Link href={`/w/${workspace}/browse`}>Browse</Link>
      <Link href={`/w/${workspace}/library`}>Library</Link>
      <b>Ask Dataroom</b>
      <Link href={`/w/${workspace}/search`}>Search</Link>
      <Link href="/notifications">Notifications</Link>
    </aside>

    <main className="main askMain">
      <div>
        <h1>Ask Dataroom</h1>
        <p className="muted">Answers are generated only from content you are allowed to access.</p>
      </div>

      <form className="askForm" onSubmit={submit}>
        <textarea
          className="input askInput"
          value={question}
          onChange={e=>setQuestion(e.target.value)}
          placeholder="What does our refund policy say about enterprise customers?"
          required
        />
        <button className="btn" disabled={busy}>{busy?'Searching…':'Ask'}</button>
      </form>

      {result&&<section className="askAnswer">
        <div className="card">
          <div className="answerLabel">ANSWER</div>
          <div className="answerText">{result.answer}</div>
        </div>

        <div className="sourcesGrid">
          {result.sources.map(source=><Link className="card sourceCard" key={source.label} href={href(source)}>
            <div className="sourceTop"><b>{source.label}</b><span className="badge">{source.type}</span></div>
            <h3>{source.name}</h3>
            <p>{source.snippet}</p>
            <small className="muted">Semantic match {(source.score*100).toFixed(0)}%</small>
          </Link>)}
        </div>
      </section>}

      {error&&<div className="card errorCard">{error}</div>}
    </main>
  </div>;
}
