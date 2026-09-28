'use client';

import {use,useEffect,useRef,useState} from 'react';
import Link from 'next/link';
import {useRouter} from 'next/navigation';
import {EditorContent,useEditor} from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import {api} from '@/lib/api';

type DocumentNode={
  id:string;
  parent_id:string|null;
  name:string;
  document:{
    content_json:Record<string,unknown>;
    plain_text:string;
    version:number;
    status:string;
  };
};

export default function DocumentPage({params}:{params:Promise<{workspace:string;node:string}>}){
  const {workspace,node}=use(params);
  const router=useRouter();
  const [record,setRecord]=useState<DocumentNode|null>(null);
  const [title,setTitle]=useState('');
  const [saveState,setSaveState]=useState<'loading'|'saved'|'saving'|'error'>('loading');
  const timer=useRef<ReturnType<typeof setTimeout>|null>(null);

  const editor=useEditor({
    extensions:[StarterKit],
    content:{type:'doc',content:[{type:'paragraph'}]},
    immediatelyRender:false,
    editorProps:{attributes:{class:'editorSurface'}},
    onUpdate:({editor})=>{
      if(!record)return;
      setSaveState('saving');
      if(timer.current)clearTimeout(timer.current);
      timer.current=setTimeout(async()=>{
        try{
          await api(`/documents/${node}`,{
            method:'PUT',
            body:JSON.stringify({content_json:editor.getJSON(),plain_text:editor.getText()})
          });
          setSaveState('saved');
        }catch{
          setSaveState('error');
        }
      },700);
    }
  },[record?.id]);

  useEffect(()=>{
    api<DocumentNode>(`/documents/${node}`).then(data=>{
      setRecord(data);
      setTitle(data.name);
      editor?.commands.setContent(data.document.content_json,{emitUpdate:false});
      setSaveState('saved');
    }).catch(()=>setSaveState('error'));
  },[node,editor]);

  useEffect(()=>()=>{if(timer.current)clearTimeout(timer.current)},[]);

  async function saveTitle(){
    if(!record||title.trim()===''||title===record.name)return;
    setSaveState('saving');
    try{
      const updated=await api<DocumentNode>(`/documents/${node}`,{
        method:'PUT',
        body:JSON.stringify({name:title.trim()})
      });
      setRecord(updated);
      setTitle(updated.name);
      setSaveState('saved');
    }catch{setSaveState('error')}
  }

  if(!record)return <div className="shell">{saveState==='error'?'Unable to load document.':'Loading…'}</div>;

  return <div className="grid">
    <aside className="sidebar stack">
      <h2>Dataroom</h2>
      <Link href={record.parent_id?`/w/${workspace}/browse?folder=${record.parent_id}`:`/w/${workspace}/browse`}>← Browse</Link>
      <Link href={`/w/${workspace}/search`}>Search</Link>
    </aside>
    <main className="documentMain">
      <div className="documentTopbar">
        <button className="linkButton" onClick={()=>router.back()}>← Back</button>
        <span className={`saveState ${saveState}`}>{saveState==='saving'?'Saving…':saveState==='error'?'Save failed':'Saved'}</span>
      </div>
      <input className="documentTitle" value={title} onChange={e=>setTitle(e.target.value)} onBlur={saveTitle} aria-label="Document title"/>
      <div className="editorToolbar">
        <button className={editor?.isActive('bold')?'active':''} onClick={()=>editor?.chain().focus().toggleBold().run()}><b>B</b></button>
        <button className={editor?.isActive('italic')?'active':''} onClick={()=>editor?.chain().focus().toggleItalic().run()}><i>I</i></button>
        <button className={editor?.isActive('heading',{level:2})?'active':''} onClick={()=>editor?.chain().focus().toggleHeading({level:2}).run()}>H2</button>
        <button className={editor?.isActive('bulletList')?'active':''} onClick={()=>editor?.chain().focus().toggleBulletList().run()}>• List</button>
        <button className={editor?.isActive('orderedList')?'active':''} onClick={()=>editor?.chain().focus().toggleOrderedList().run()}>1. List</button>
        <button className={editor?.isActive('blockquote')?'active':''} onClick={()=>editor?.chain().focus().toggleBlockquote().run()}>Quote</button>
        <button onClick={()=>editor?.chain().focus().undo().run()}>Undo</button>
        <button onClick={()=>editor?.chain().focus().redo().run()}>Redo</button>
      </div>
      <EditorContent editor={editor}/>
    </main>
  </div>;
}
