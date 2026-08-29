import { useEffect, useState } from 'react';
import { listOperationsForScope } from '../offline/db';
import { currentScopeKey } from '../offline/scope';
import { flushQueue } from '../offline/sync';
export default function Connectivity() {
  const [online,setOnline]=useState(navigator.onLine); const [counts,setCounts]=useState({pending:0,rejected:0});
  const refresh=async()=>{const ops=await listOperationsForScope(currentScopeKey());setCounts({pending:ops.filter(o=>['pending','syncing','retryable_error'].includes(o.status)).length,rejected:ops.filter(o=>o.status==='rejected').length});};
  useEffect(()=>{const on=()=>{setOnline(navigator.onLine);void refresh();}; window.addEventListener('online',on);window.addEventListener('offline',on);window.addEventListener('sentinelops:queue-updated',on as EventListener);void refresh();return()=>{window.removeEventListener('online',on);window.removeEventListener('offline',on);window.removeEventListener('sentinelops:queue-updated',on as EventListener);};},[]);
  return <div className={`connectivity ${online?'online':'offline'}`}><span className="dot"/><b>{online?'ONLINE':'OFFLINE'}</b><span>{counts.pending} pending</span>{counts.rejected>0&&<span className="danger">{counts.rejected} rejected</span>}<button className="ghost compact" disabled={!online} onClick={()=>void flushQueue().then(refresh)}>Sync now</button></div>;
}
