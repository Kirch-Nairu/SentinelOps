const STATIC_CACHE='sentinelops-static-v1';
self.addEventListener('install',event=>event.waitUntil(caches.open(STATIC_CACHE).then(cache=>cache.addAll(['/offline.html']))));
self.addEventListener('activate',event=>event.waitUntil(self.clients.claim()));
self.addEventListener('fetch',event=>{
  const req=event.request;
  if(req.method!=='GET') return;
  const url=new URL(req.url);
  if(url.origin!==self.location.origin) return;
  if(req.destination==='script'||req.destination==='style'||req.destination==='font'){
    event.respondWith(caches.open(STATIC_CACHE).then(async cache=>{const hit=await cache.match(req);if(hit)return hit;const response=await fetch(req);if(response.ok)cache.put(req,response.clone());return response;}));
  }
  if(req.mode==='navigate') event.respondWith(fetch(req).catch(()=>caches.match('/offline.html')));
});
self.addEventListener('message',event=>{if(event.data?.type==='CLEAR_PRIVATE_CACHES') event.waitUntil(caches.keys().then(keys=>Promise.all(keys.map(k=>caches.delete(k)))));});
