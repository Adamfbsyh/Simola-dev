<x-app-layout>
<x-slot name="header">
<div style="max-width:900px;margin:auto;display:flex;justify-content:space-between;gap:12px;align-items:center">
    <div>
        <a href="{{ route('operator-chat.supervisor.index') }}" style="font-size:9px;color:#2563eb">← Inbox</a>
        <h2 style="font-weight:800;margin-top:4px">{{ $thread->displayName() }}</h2>
        <p style="font-size:9px;color:#94a3b8">
            @if($device)
                {{ $device->is_active ? 'Perangkat aktif' : 'Perangkat belum aktif' }}
                @if($device->last_seen_at)
                    · terakhir terhubung {{ $device->last_seen_at->format('d-m H:i') }}
                @endif
            @else
                Thread legacy
            @endif
        </p>
    </div>

    <form method="POST" action="{{ route('operator-chat.supervisor.resolve', $thread) }}">
        @csrf
        @method('PATCH')
        <button style="padding:8px 10px;border:1px solid #cbd5e1;border-radius:9px;background:transparent;color:inherit;font-size:9px">
            {{ $thread->status === 'resolved' ? 'Buka Kembali' : 'Tandai Selesai' }}
        </button>
    </form>
</div>
</x-slot>

<style>
.sw{max-width:900px;margin:auto;padding:18px}.panel{height:calc(100vh - 180px);min-height:520px;display:grid;grid-template-rows:1fr auto;border:1px solid #dbe3ed;border-radius:15px;overflow:hidden;background:#fff}.dark .panel{background:#101c30;border-color:#2d405d}.msgs{overflow:auto;padding:16px}.r{display:flex;margin:9px 0}.r.sup{justify-content:flex-end}.m{max-width:76%}.meta{font-size:8px;color:#94a3b8;margin:0 4px 3px}.sup .meta{text-align:right}.b{padding:9px 11px;border:1px solid #dbe3ed;background:#f8fafc;border-radius:13px 13px 13px 4px;white-space:pre-wrap;font-size:10px;line-height:1.5}.dark .b{background:#172640;border-color:#304562}.sup .b{background:#2563eb;color:#fff;border-color:#2563eb;border-radius:13px 13px 4px 13px}.read{font-size:8px;color:#94a3b8;text-align:right}.cmp{display:flex;gap:8px;padding:10px;border-top:1px solid #dbe3ed}.dark .cmp{border-color:#2d405d}.cmp textarea{flex:1;background:transparent;border:1px solid #94a3b8;border-radius:10px;padding:10px;color:inherit;font-size:10px}.cmp button{border:0;border-radius:10px;background:#2563eb;color:#fff;padding:0 16px}
</style>

<div class="sw">
<section class="panel">
<div id="msgs" class="msgs">
@forelse($messages as $m)
<div class="r {{ $m->sender_type === 'supervisor' ? 'sup' : '' }}" data-id="{{ $m->id }}">
    <div class="m">
        <div class="meta">
            {{ $m->sender_type === 'operator' ? 'PC '.$thread->pc_number : ($m->sender?->name ?? 'Pengawas') }}
            · {{ $m->created_at?->format('H:i') }}
        </div>
        <div class="b">{{ $m->body }}</div>
        @if($m->sender_type === 'supervisor')
            <div class="read">{{ $m->read_at ? 'Dibaca' : 'Terkirim' }}</div>
        @endif
    </div>
</div>
@empty
<div style="padding:30px;text-align:center;color:#94a3b8;font-size:10px">Belum ada percakapan.</div>
@endforelse
</div>

<form id="frm" class="cmp" action="{{ route('operator-chat.supervisor.send', $thread) }}">
    @csrf
    <textarea id="inp" rows="2" maxlength="2000" placeholder="Tulis balasan..." required></textarea>
    <button>➤</button>
</form>
</section>
</div>

<script>
(()=>{
const box=document.getElementById('msgs'),form=document.getElementById('frm'),input=document.getElementById('inp');
const csrf=document.querySelector('meta[name=csrf-token]').content;
let last=Number(box.querySelector('[data-id]:last-of-type')?.dataset.id||0),first=true,ctx=null,armed=false;
const esc=v=>{const d=document.createElement('div');d.textContent=v??'';return d.innerHTML};
const arm=()=>{armed=true;try{const A=window.AudioContext||window.webkitAudioContext;if(A&&!ctx)ctx=new A();if(ctx?.state==='suspended')ctx.resume().catch(()=>{})}catch{}};
const ding=()=>{if(!armed||!ctx)return;try{const n=ctx.currentTime,o=ctx.createOscillator(),g=ctx.createGain();o.frequency.value=830;g.gain.setValueAtTime(.0001,n);g.gain.exponentialRampToValueAtTime(.06,n+.02);g.gain.exponentialRampToValueAtTime(.0001,n+.27);o.connect(g);g.connect(ctx.destination);o.start(n);o.stop(n+.29)}catch{}};
const render=a=>{const stick=box.scrollHeight-box.scrollTop-box.clientHeight<120;box.innerHTML=a.length?a.map(m=>`<div class="r ${m.is_mine?'sup':''}" data-id="${m.id}"><div class="m"><div class="meta">${esc(m.sender_name)} · ${esc(m.time)}</div><div class="b">${esc(m.body)}</div>${m.is_mine?`<div class="read">${m.read?'Dibaca':'Terkirim'}</div>`:''}</div></div>`).join(''):'<div style="padding:30px;text-align:center;color:#94a3b8">Belum ada percakapan.</div>';if(stick)box.scrollTop=box.scrollHeight};
const poll=async()=>{try{const r=await fetch(@json(route('operator-chat.supervisor.messages',$thread)),{headers:{Accept:'application/json'},credentials:'same-origin'});if(!r.ok)return;const d=await r.json(),a=d.messages||[],n=a.length?Number(a[a.length-1].id):0,incoming=a.some(m=>Number(m.id)>last&&m.sender_type==='operator');render(a);if(!first&&incoming)ding();last=Math.max(last,n);first=false}catch{}};
form.addEventListener('submit',async e=>{e.preventDefault();arm();const body=input.value.trim();if(!body)return;input.disabled=true;try{const r=await fetch(form.action,{method:'POST',headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrf},credentials:'same-origin',body:JSON.stringify({body})});if(r.ok){input.value='';await poll()}else alert('Balasan gagal dikirim.')}finally{input.disabled=false;input.focus()}});
input.addEventListener('keydown',e=>{if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();form.requestSubmit()}});
document.addEventListener('pointerdown',arm,{once:true,passive:true});
box.scrollTop=box.scrollHeight;setInterval(poll,3000);
})();
</script>
</x-app-layout>
