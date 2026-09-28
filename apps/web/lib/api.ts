export const API = process.env.NEXT_PUBLIC_API_URL ?? 'http://localhost:8080/api/v1';

export function token(){
  if(typeof window==='undefined')return null;
  return localStorage.getItem('dataroom_token');
}

// Never surface raw server bodies (stack traces, HTML) to the user: only 4xx messages, generic text otherwise.
function messageFrom(status:number,body:string){
  if(status>=500)return 'Something went wrong. Please try again.';
  try{const b=JSON.parse(body);const first=b?.errors&&Object.values(b.errors as Record<string,string[]>)[0]?.[0];return first||b?.message||`Request failed (${status})`}
  catch{return `Request failed (${status})`}
}

export async function api<T>(path:string, init:RequestInit={}):Promise<T>{
  const headers=new Headers(init.headers);
  headers.set('Accept','application/json');
  const isForm=typeof FormData!=='undefined' && init.body instanceof FormData;
  if(!isForm && init.body)headers.set('Content-Type','application/json');
  const t=token();
  if(t)headers.set('Authorization',`Bearer ${t}`);
  const r=await fetch(`${API}${path}`,{...init,headers});
  if(!r.ok)throw new Error(messageFrom(r.status,await r.text()));
  if(r.status===204)return undefined as T;
  return r.json();
}

export function uploadFile<T>(path:string,file:File,parentId:string|null,onProgress:(pct:number)=>void):Promise<T>{
  return new Promise((resolve,reject)=>{
    const xhr=new XMLHttpRequest();
    xhr.open('POST',`${API}${path}`);
    const t=token();
    xhr.setRequestHeader('Accept','application/json');
    if(t)xhr.setRequestHeader('Authorization',`Bearer ${t}`);
    xhr.upload.onprogress=e=>{if(e.lengthComputable)onProgress(Math.round((e.loaded/e.total)*100))};
    xhr.onerror=()=>reject(new Error('Upload failed'));
    xhr.onload=()=>{if(xhr.status>=200&&xhr.status<300)resolve(JSON.parse(xhr.responseText));else reject(new Error(messageFrom(xhr.status,xhr.responseText)))};
    const form=new FormData();
    form.append('file',file);
    if(parentId)form.append('parent_id',parentId);
    xhr.send(form);
  });
}

async function authenticatedBlob(path:string){
  const headers=new Headers({Accept:'application/json'});
  const t=token();
  if(t)headers.set('Authorization',`Bearer ${t}`);
  const r=await fetch(`${API}${path}`,{headers});
  if(!r.ok)throw new Error(messageFrom(r.status,await r.text()));
  return r.blob();
}

export async function previewFile(nodeId:string){
  const blob=await authenticatedBlob(`/files/${nodeId}/preview`);
  return URL.createObjectURL(blob);
}

export async function downloadFile(nodeId:string,name:string){
  const blob=await authenticatedBlob(`/files/${nodeId}/download`);
  const url=URL.createObjectURL(blob);
  const a=document.createElement('a');
  a.href=url;a.download=name;a.click();
  URL.revokeObjectURL(url);
}
