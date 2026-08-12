@can('operator-chat.manage')
    <x-nav-link
        :href="route('operator-chat.supervisor.index')"
        :active="request()->routeIs('operator-chat.supervisor.*') || request()->routeIs('operator-chat.devices.*') || request()->routeIs('operator-chat.transfers.*')"
        class="relative"
    >
        <span>Chat Operator</span>
        <span
            id="simola-chat-top-badge"
            style="display:none;min-width:17px;height:17px;margin-left:5px;padding:0 4px;align-items:center;justify-content:center;border-radius:999px;background:#ef4444;color:#fff;font-size:8px;font-weight:900;line-height:1"
        ></span>
    </x-nav-link>

    @once
    <script>
    document.addEventListener('DOMContentLoaded',()=>{
        const badge=document.getElementById('simola-chat-top-badge');if(!badge)return;
        let last=0,ctx=null,armed=false;
        const arm=()=>{armed=true;try{const A=window.AudioContext||window.webkitAudioContext;if(A&&!ctx)ctx=new A();if(ctx?.state==='suspended')ctx.resume().catch(()=>{})}catch{}};
        const ding=()=>{if(!armed||!ctx)return;try{const n=ctx.currentTime,o=ctx.createOscillator(),g=ctx.createGain();o.frequency.value=820;g.gain.setValueAtTime(.0001,n);g.gain.exponentialRampToValueAtTime(.05,n+.02);g.gain.exponentialRampToValueAtTime(.0001,n+.25);o.connect(g);g.connect(ctx.destination);o.start(n);o.stop(n+.27)}catch{}};
        const poll=async()=>{try{const r=await fetch(@json(route('operator-chat.supervisor.unread')),{headers:{Accept:'application/json'},credentials:'same-origin'});if(!r.ok)return;const d=await r.json(),n=Number(d.count||0);badge.style.display=n?'inline-flex':'none';badge.textContent=n>99?'99+':String(n||'');if(n>last)ding();last=n}catch{}};
        document.addEventListener('pointerdown',arm,{once:true,passive:true});poll();setInterval(poll,5000);
    });
    </script>
    @endonce
@endcan
