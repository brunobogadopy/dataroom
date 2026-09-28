'use client';

import {use,useEffect,useRef,useState} from 'react';
import Link from 'next/link';
import {useRouter} from 'next/navigation';
import {EditorContent,useEditor} from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import {api} from '@/lib/api';

type DocumentNode={id:string;parent_id:string|null;name:string;can_edit:boolean;document:{content_json:Record<string,unknown>;plain_text:string;version:number;status:string}};
type Revision={id:string;version:number;plain_text:string;created_at:string;creator?:{name:string}|null};

export default function DocumentPage({params}:{params:Promise<{workspace:string;node:string}>}){
  const {workspace,node}=use(params);const router=useRouter();const [record,setRecord]=useState<DocumentNode|null>(null);const [title,setTitle]=useState('');
  const [saveState,setSaveState]=useState<'loading'|'saved'|'saving'|'error'>('loading');const [history,setHistory]=useState<Revision[]>([]);const [showHistory,setShowHistory]=useState(false);const timer=useRef<ReturnType<typeof setTimeout>|null>(null);

  const editor=useEditor({
    extensions:[StarterKit],content:{type:'doc',content:[{type:'paragraph'}]},immediatelyRender:false,editable:record?.can_edit??false,
    editorProps:{attributes:{class:'editorSurface'}},
    onUpdate:({editor})=>{if(!record?.can_edit)return;setSaveState('saving');if(timer.current)clearTimeout(timer.current);timer.current=setTimeout(async()=>{try{const updated=await api<DocumentNode>(`/documents/${node}`,{method:'PUT',body:JSON.stringify({content_json:editor.getJSON(),plain_text:editor.getText()})});setRecord(updated);setSaveState('saved')}catch{setSaveState('error')}},700)}
  },[record?.id,record?.can_edit]);

  async function load(){try{const data=await api<DocumentNode>(`/documents/${node}`);setRecord(data);setTitle(data.name);editor?.setEditable(data.can_edit);editor?.commands.setContent(data.document.content_json,{emitUpdate:false});setSaveState('saved')}catch{setSaveState('error')}}
  useEffect(()=>{load()},[node,editor]);useEffect(()=>()=>{if(timer.current)clearTimeout(timer.current)},[]);
  async function loadHistory(){setHistory(await api<Revision[]>(`/documents/${node}/versions`));setShowHistory(true)}
  async function restore(id:string){if(!record?.can_edit||!confirm('Restore this version? The current content will remain in history.'))return;const updated=await api<DocumentNode>(`/documents/${node}/versions/${id}/restore`,{method:'POST'});setRecord(updated);editor?.commands.setContent(updated.document.content_json,{emitUpdate:false});await loadHistory()}
  async function saveTitle(){if(!record?.can_edit||title.trim()===''||title===record.name)return;setSaveState('saving');try{const updated=await api<DocumentNode>(`/documents/${node}`,{method:'PUT',body:JSON.stringify({name:title.trim()})});setRecord(updated);setTitle(updated.name);setSaveState('saved')}catch{setSaveState('error')}}

  if(!record)return <div className="shell">{saveState==='error'?'Unable to load document.':'Loading…'}</div>;

  return <div className="grid">
    <aside className="sidebar stack"><h2>Dataroom</h2><Link href={record.parent_id?`/w/${workspace}/browse?folder=${record.parent_id}`:`/w/${workspace}/browse`}>← Browse</Link><Link href={`/w/${workspace}/library`}>Library</Link><Link href={`/w/${workspace}/search`}>Search</Link></aside>
    <main className="documentMain">
      <div className="documentTopbar"><button className="linkButton" onClick={()=>router.back()}>← Back</button><div className="row"><button className="linkButton" onClick={loadHistory}>History</button><span className={`saveState ${saveState}`}>{record.can_edit?(saveState==='saving'?'Saving…':saveState==='error'?'Save failed':`Saved · v${record.document.version}`):'Read only'}</span></div></div>
      <input className="documentTitle" value={title} onChange={e=>setTitle(e.target.value)} onBlur={saveTitle} disabled={!record.can_edit} aria-label="Document title"/>
      {record.can_edit&&<div className="editorToolbar"><button className={editor?.isActive('bold')?'active':''} onClick={()=>editor?.chain().focus().toggleBold().run()}><b>B</b></button><button className={editor?.isActive('italic')?'active':''} onClick={()=>editor?.chain().focus().toggleItalic().run()}><i>I</i></button><button className={editor?.isActive('heading',{level:2})?'active':''} onClick={()=>editor?.chain().focus().toggleHeading({level:2}).run()}>H2</button><button className={editor?.isActive('bulletList')?'active':''} onClick={()=>editor?.chain().focus().toggleBulletList().run()}>• List</button><button className={editor?.isActive('orderedList')?'active':''} onClick={()=>editor?.chain().focus().toggleOrderedList().run()}>1. List</button><button onClick={()=>editor?.chain().focus().undo().run()}>Undo</button><button onClick={()=>editor?.chain().focus().redo().run()}>Redo</button></div>}
      <EditorContent editor={editor}/>
    </main>
    {showHistory&&<aside className="historyPanel"><div className="topbar"><h3>Version history</h3><button className="linkButton" onClick={()=>setShowHistory(false)}>Close</button></div>{history.map(h=><div className="historyItem" key={h.id}><b>Version {h.version}</b><span className="muted">{new Date(h.created_at).toLocaleString()} · {h.creator?.name??'Unknown'}</span><p>{h.plain_text.slice(0,140)||'Empty document'}</p>{record.can_edit&&<button className="btn secondary" onClick={()=>restore(h.id)}>Restore</button>}</div>)}</aside>}
  </div>;
}
