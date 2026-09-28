export const API = process.env.NEXT_PUBLIC_API_URL ?? 'http://localhost:8080/api/v1';

export function token(){
  if(typeof window==='undefined')return null;
  return localStorage.getItem('dataroom_token');
}

export async function api<T>(path:string, init:RequestInit={}):Promise<T>{
  const headers=new Headers(init.headers);
  const isForm=typeof FormData!=='undefined' && init.body instanceof FormData;
  if(!isForm && init.body)headers.set('Content-Type','application/json');
  const t=token();
  if(t)headers.set('Authorization',`Bearer ${t}`);
  const r=await fetch(`${API}${path}`,{...init,headers});
  if(!r.ok){const body=await r.text();throw new Error(body||`HTTP ${r.status}`)}
  if(r.status===204)return undefined as T;
  return r.json();
}

export function uploadFile<T>(path:string,file:File,parentId:string|null,onProgress:(pct:number)=>void):Promise<T>{
  return new Promise((resolve,reject)=>{
    const xhr=new XMLHttpRequest();
    xhr.open('POST',`${API}${path}`);
    const t=token();
    if(t)xhr.setRequestHeader('Authorization',`Bearer ${t}`);
    xhr.upload.onprogress=e=>{if(e.lengthComputable)onProgress(Math.round((e.loaded/e.total)*100))};
    xhr.onerror=()=>reject(new Error('Upload failed'));
    xhr.onload=()=>{if(xhr.status>=200&&xhr.status<300)resolve(JSON.parse(xhr.responseText));else reject(new Error(xhr.responseText||`HTTP ${xhr.status}`))};
    const form=new FormData();
    form.append('file',file);
    if(parentId)form.append('parent_id',parentId);
    xhr.send(form);
  });
}

async function authenticatedBlob(path:string){
  const headers=new Headers();
  const t=token();
  if(t)headers.set('Authorization',`Bearer ${t}`);
  const r=await fetch(`${API}${path}`,{headers});
  if(!r.ok)throw new Error(await r.text());
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
