import Link from 'next/link';
export default function Home(){return <main className="shell"><div className="card stack"><h1>Dataroom</h1><p className="muted">Your company knowledge and files. Together.</p><div className="row"><Link className="btn" href="/register">Create account</Link><Link className="btn secondary" href="/login">Sign in</Link></div></div></main>}
