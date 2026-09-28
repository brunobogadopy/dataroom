'use client';

import {FormEvent,useEffect,useState} from 'react';
import {api} from '@/lib/api';

type Comment={id:string;body:string;created_at:string;user:{id:string;name:string;email:string};mentions:{id:string;name:string;email:string}[]};

export default function CommentsPanel({nodeId}:{nodeId:string}){
  const [items,setItems]=useState<Comment[]>([]);
  const [error,setError]=useState('');
  const [busy,setBusy]=useState(false);

  async function load(){
    try{setItems(await api<Comment[]>(`/nodes/${nodeId}/comments`));setError('')}
    catch(e){setError(e instanceof Error?e.message:'Something went wrong.')}
  }

  useEffect(()=>{load()},[nodeId]);

  async function submit(e:FormEvent<HTMLFormElement>){
    e.preventDefault();setBusy(true);setError('');
    const form=e.currentTarget;const data=new FormData(form);
    try{
      await api(`/nodes/${nodeId}/comments`,{method:'POST',body:JSON.stringify({body:data.get('body')})});
      form.reset();await load();
    }catch(e){setError(e instanceof Error?e.message:'Something went wrong.')}
    finally{setBusy(false)}
  }

  return <section className="card commentsCard">
    <div><h3>Comments</h3><p className="muted">Mention a teammate with @email@example.com.</p></div>
    <form className="stack" onSubmit={submit}>
      <textarea className="input commentInput" name="body" placeholder="Add a comment…" required/>
      <div><button className="btn" disabled={busy}>{busy?'Posting…':'Comment'}</button></div>
    </form>
    <div className="commentList">
      {items.map(c=><article className="commentItem" key={c.id}>
        <div className="commentMeta"><b>{c.user.name}</b><span className="muted">{new Date(c.created_at).toLocaleString()}</span></div>
        <p>{c.body}</p>
      </article>)}
      {!items.length&&<p className="muted">No comments yet.</p>}
    </div>
    {error&&<pre>{error}</pre>}
  </section>;
}
