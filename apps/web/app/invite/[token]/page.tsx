'use client';

import {use,useState} from 'react';
import {useRouter} from 'next/navigation';
import Link from 'next/link';
import {api,token as authToken} from '@/lib/api';

export default function AcceptInvite({params}:{params:Promise<{token:string}>}){
  const {token}=use(params);
  const router=useRouter();
  const [error,setError]=useState('');
  const [busy,setBusy]=useState(false);

  async function accept(){
    setBusy(true);setError('');
    try{
      const result=await api<{workspace:{slug:string}}>(`/invitations/${token}/accept`,{method:'POST'});
      router.push(`/w/${result.workspace.slug}/browse`);
    }catch(e){setError(String(e));setBusy(false)}
  }

  const loggedIn=typeof window!=='undefined'&&!!authToken();

  return <main className="shell">
    <div className="card stack inviteCard">
      <h1>Join workspace</h1>
      <p className="muted">This invitation is tied to the email address it was sent to.</p>
      {loggedIn?<button className="btn" disabled={busy} onClick={accept}>{busy?'Joining…':'Accept invitation'}</button>:<>
        <p>Log in or create an account with the invited email, then return to this link.</p>
        <div className="row"><Link className="btn" href="/login">Log in</Link><Link className="btn secondary" href="/register">Create account</Link></div>
      </>}
      {error&&<pre>{error}</pre>}
    </div>
  </main>;
}
