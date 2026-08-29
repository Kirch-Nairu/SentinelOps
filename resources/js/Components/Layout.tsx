import { Link, router, usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import type { SharedProps } from '../types';

export default function Layout({ children }: PropsWithChildren) {
  const { auth } = usePage<SharedProps>().props;
  const logout = () => {
    navigator.serviceWorker?.controller?.postMessage({ type: 'CLEAR_PRIVATE_CACHES' });
    router.post('/logout');
  };
  return <div className="app-shell">
    <header className="topbar">
      <div className="brand"><span className="brand-mark">S</span><div><strong>SentinelOps</strong><small>field authority console</small></div></div>
      <nav><Link href="/dashboard">Overview</Link><Link href="/assets">Assets</Link></nav>
      <div className="identity"><span><b>{auth.user?.name}</b><small>{auth.role?.replace('_',' ')} · {auth.organization?.name}</small></span><button onClick={logout} className="ghost">Sign out</button></div>
    </header>
    <main>{children}</main>
  </div>;
}
