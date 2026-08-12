@once
<style>
#oc-launch{position:fixed;left:18px;bottom:18px;z-index:2147481000;display:flex;align-items:center;gap:8px;padding:9px 12px;border-radius:999px;background:#0f1d35;color:#fff;text-decoration:none;box-shadow:0 12px 32px rgba(15,23,42,.28);font-size:11px;font-weight:800}#oc-badge{display:none;min-width:20px;height:20px;padding:0 6px;align-items:center;justify-content:center;border-radius:999px;background:#ef4444;color:#fff;font-size:9px}.oc-pulse{animation:ocp .7s ease 2}@keyframes ocp{50%{transform:scale(1.05)}}
</style>
<a id="oc-launch" href="{{ route('operator-chat.supervisor.index') }}">💬 Chat Operator <span id="oc-badge"></span></a>
<script>
(()=>{const a=document.getElementById('oc-launch'),b=document.getElementById('oc-badge');let last=0;const poll=async()=>{try{let r=await fetch(@json(route('operator-chat.supervisor.unread')),{headers:{Accept:'application/json'},credentials:'same-origin'});if(!r.ok)return;let d=await r.json(),n=Number(d.count||0);b.style.display=n?'inline-flex':'none';b.textContent=n>99?'99+':String(n||'');if(n>last){a.classList.remove('oc-pulse');void a.offsetWidth;a.classList.add('oc-pulse')}last=n}catch{}};poll();setInterval(poll,5000)})();
</script>
@endonce
