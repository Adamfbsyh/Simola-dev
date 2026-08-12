<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>SIMOLA Operator · {{ $device->displayName() }}</title>
<style>
*{box-sizing:border-box}
:root{font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}
html,body{margin:0;min-height:100%}
body{min-height:100vh;background:radial-gradient(circle at 18% 10%,rgba(37,99,235,.08),transparent 30%),#eef3f9;color:#182338}
button,textarea,select{font:inherit}
.page{min-height:100vh;padding:18px}
.bar{max-width:920px;margin:auto;display:flex;align-items:center;justify-content:space-between;gap:12px}
.brand{display:flex;align-items:center;gap:9px}
.logo{width:35px;height:35px;display:grid;place-items:center;border-radius:11px;background:#172844;color:#fff;font-weight:900}
.brand b{display:block;font-size:13px}.brand small{display:block;margin-top:2px;color:#718198;font-size:8px}
.device-pill{border:1px solid #d8e1ec;border-radius:999px;background:#fff;padding:7px 10px;color:#526178;font-size:9px}
.welcome{max-width:920px;margin:48px auto 0;border:1px solid #dfe6ef;border-radius:18px;background:rgba(255,255,255,.76);padding:24px;box-shadow:0 12px 38px rgba(15,23,42,.06)}
.welcome h1{margin:0;font-size:18px}.welcome p{max-width:630px;margin:7px 0 0;color:#6b7a91;font-size:10px;line-height:1.65}
.quick{margin-top:15px;display:flex;gap:7px;flex-wrap:wrap}.q{border:1px solid #dce5ef;border-radius:999px;background:#fff;padding:7px 9px;color:#5b6980;font-size:8px}.q strong{color:#172033}

.launch{position:fixed;right:16px;bottom:16px;z-index:9000;display:inline-flex;align-items:center;gap:9px;border:0;border-radius:999px;background:#172844;color:#fff;padding:8px 12px 8px 8px;box-shadow:0 14px 36px rgba(15,23,42,.25);cursor:pointer}
.launch-ico{width:31px;height:31px;display:grid;place-items:center;border-radius:999px;background:#2563eb}
.launch b{display:block;text-align:left;font-size:9px}.launch small{display:block;margin-top:1px;color:#bbc8db;font-size:7px}.launch-badge{display:none;min-width:18px;height:18px;align-items:center;justify-content:center;border-radius:999px;background:#ef4444;color:#fff;padding:0 5px;font-size:7px;font-weight:900}

.widget{position:fixed;right:16px;bottom:16px;z-index:9010;width:min(382px,calc(100vw - 18px));height:min(625px,calc(100vh - 22px));display:none;grid-template-rows:auto auto 1fr;overflow:hidden;border:1px solid #cad6e5;border-radius:18px;background:#fff;box-shadow:0 22px 65px rgba(15,23,42,.28)}
.widget.open{display:grid;animation:pop .18s ease-out}
.head{min-height:59px;padding:10px 11px;display:flex;align-items:center;justify-content:space-between;gap:8px;background:#172844;color:#fff}
.title{display:flex;align-items:center;gap:8px}.avatar{width:34px;height:34px;display:grid;place-items:center;border-radius:11px;background:#29456f}.title b{display:block;font-size:10px}.title small{display:block;margin-top:2px;color:#bdcadc;font-size:7px}
.min{width:29px;height:29px;border:0;border-radius:8px;background:transparent;color:#fff;cursor:pointer;font-size:14px}.min:hover{background:rgba(255,255,255,.1)}
.tabs{display:grid;grid-template-columns:1fr 1fr;border-bottom:1px solid #e4e9f0;background:#f8fafc}.tab{border:0;border-bottom:2px solid transparent;background:transparent;color:#7b8799;padding:10px;cursor:pointer;font-size:8px;font-weight:900}.tab.active{border-bottom-color:#2563eb;background:#fff;color:#1d4ed8}
.view{display:none;min-height:0}.view.active{display:grid}.chat-view{grid-template-rows:1fr auto}
.messages{min-height:0;overflow:auto;padding:11px;background:#f8fafc}.row{display:flex;margin:7px 0}.row.mine{justify-content:flex-end}.msg{max-width:82%}.meta{margin:0 4px 3px;color:#9aa6b5;font-size:7px}.mine .meta{text-align:right}.bubble{padding:8px 10px;border:1px solid #dbe3ed;border-radius:12px 12px 12px 4px;background:#fff;color:#29364a;font-size:9px;line-height:1.5;white-space:pre-wrap;word-break:break-word}.mine .bubble{border-color:#2563eb;border-radius:12px 12px 4px 12px;background:#2563eb;color:#fff}.read{text-align:right;color:#9aa6b5;font-size:6.5px;margin-top:3px}
.compose{display:flex;gap:6px;padding:9px;border-top:1px solid #e4e9f0;background:#fff}.compose textarea{flex:1;min-height:39px;max-height:90px;resize:none;border:1px solid #d7e0ea;border-radius:11px;background:#f8fafc;color:#27364b;padding:9px;font-size:9px;outline:none}.send{width:42px;border:0;border-radius:11px;background:#2563eb;color:#fff;cursor:pointer}

.notes-view{grid-template-rows:auto 1fr auto;background:#f1f5f9}.noteform{padding:10px;border-bottom:1px solid #e2e8f0;background:#f8fafc}.sticky{position:relative;padding:11px;border:1px solid #ecc85e;border-radius:10px;background:linear-gradient(155deg,#fff8bd,#ffef8c);box-shadow:0 8px 20px rgba(146,104,16,.09)}.sticky:before{content:"";position:absolute;top:-1px;left:50%;width:46px;height:7px;transform:translateX(-50%);border-radius:0 0 5px 5px;background:rgba(255,255,255,.58)}
.sticky textarea{width:100%;min-height:70px;resize:vertical;border:0;outline:none;background:transparent;color:#493911;padding:4px;font-size:9px;line-height:1.55}.note-hint{margin:6px 2px 0;color:#80691f;font-size:6.5px;line-height:1.45}.note-hint b{color:#5f4a13}.quickbtns{display:flex;gap:6px;padding-top:7px;margin-top:7px;border-top:1px dashed rgba(117,88,14,.18)}.quickbtn{min-width:40px;border:1px solid rgba(117,88,14,.18);border-radius:999px;background:rgba(255,255,255,.62);color:#684f12;padding:5px 8px;cursor:pointer;font-size:7px;font-weight:900}.note-save-row{margin-top:8px;display:flex;justify-content:flex-end}.note-save{border:0;border-radius:8px;background:#172844;color:#fff;padding:7px 10px;font-size:7px;font-weight:900;cursor:pointer}
.preview{margin-top:8px;padding:8px;border:1px solid rgba(117,88,14,.12);border-radius:8px;background:rgba(255,255,255,.34)}.preview:empty{display:none}.preview-title{margin-bottom:6px;color:#7a641f;font-size:6px;font-weight:900;text-transform:uppercase;letter-spacing:.06em}
.duration{display:grid;grid-template-columns:auto minmax(0,1fr) auto;align-items:center;gap:6px;min-height:23px;margin-bottom:4px}.kind{min-width:23px;height:21px;display:grid;place-items:center;border:1px solid rgba(96,67,8,.12);border-radius:7px;background:rgba(255,255,255,.72);color:#5d4813;font-size:7px;font-weight:950}.detail{min-width:0;color:#493911;font-size:8px;word-break:break-word}.dur{display:inline-flex;align-items:center;min-height:19px;padding:3px 6px;border-radius:999px;font-size:6.2px;font-weight:950;white-space:nowrap}.dur.future{background:#dbeafe;color:#1d4ed8}.dur.overdue{background:#fee2e2;color:#b91c1c}.dur.elapsed{background:#fef3c7;color:#92400e}.dur.now{background:#dcfce7;color:#166534}

.notes{overflow:auto;padding:9px;display:grid;grid-template-columns:repeat(auto-fill,minmax(138px,1fr));grid-auto-rows:max-content;align-items:start;align-content:start;gap:7px}.note{position:relative;min-height:0;height:auto;align-self:start;display:flex;flex-direction:column;padding:9px 9px 7px;border:1px solid #efcf76;border-radius:7px;background:linear-gradient(145deg,#fff7b5,#ffeb7c);box-shadow:0 3px 8px rgba(146,104,16,.09)}.note.received{border-color:#93c5fd;background:linear-gradient(145deg,#f4f8ff,#dbeafe)}.note-select{position:absolute;top:7px;right:7px;width:14px;height:14px;accent-color:#2563eb;cursor:pointer}.note-body{padding:1px 20px 1px 0;color:#4b3a13;font-size:8px;line-height:1.4;white-space:pre-wrap;word-break:break-word}.received .note-body{color:#1e3a5f}.note-origin,.note-status{display:inline-flex;align-self:flex-start;max-width:calc(100% - 22px);margin-bottom:5px;border-radius:999px;padding:3px 6px;font-size:5.8px;font-weight:900;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.note-origin{background:#bfdbfe;color:#1d4ed8}.note-status.pending{background:#fef3c7;color:#92400e}.note-status.approved{background:#dcfce7;color:#166534}.note-status.rejected{background:#fee2e2;color:#b91c1c}.note-foot{position:static;margin-top:7px;padding-top:6px;border-top:1px dashed rgba(117,88,14,.14);display:flex;align-items:center;justify-content:space-between;gap:5px;color:#8d742f;font-size:5.8px}.note-actions{display:flex;align-items:center;gap:3px;flex:0 0 auto}.split-note,.del{border:0;background:transparent;cursor:pointer;font-size:6px;font-weight:900}.split-note{color:#1d4ed8}.del{color:#9f3d2f}
@media(max-width:330px){.notes{grid-template-columns:1fr;padding:8px}.note{padding:9px}}@media(min-width:520px){.notes{grid-template-columns:repeat(auto-fill,minmax(150px,1fr))}}.transferbar{display:none;align-items:center;gap:6px;padding:8px;border-top:1px solid #dbe3ed;background:#fff}.transferbar.show{display:flex}.transferbar span{font-size:7px;font-weight:900;color:#43526a}.transferbar select{min-width:0;flex:1;border:1px solid #d4deea;border-radius:8px;background:#f8fafc;color:#334155;padding:7px;font-size:7px}.transferbtn{border:0;border-radius:8px;background:#2563eb;color:#fff;padding:7px 9px;font-size:7px;font-weight:900;cursor:pointer}.transferbtn:disabled{opacity:.5;cursor:not-allowed}

.empty{min-height:100%;display:grid;place-items:center;text-align:center;color:#8996a8;font-size:8px;line-height:1.6;padding:25px}.alert{max-width:920px;margin:10px auto 0;border:1px solid #bfdbfe;border-radius:9px;background:#eff6ff;color:#1e40af;padding:8px 10px;font-size:8px}
@keyframes pop{from{opacity:0;transform:translateY(8px) scale(.985)}to{opacity:1;transform:none}}
@media(max-width:520px){.page{padding:12px}.welcome{margin-top:28px;padding:18px}.widget{right:7px;bottom:7px;width:calc(100vw - 14px);height:min(620px,calc(100vh - 14px))}.launch{right:9px;bottom:9px}.notes{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="page">
<header class="bar">
    <div class="brand">
        <div class="logo">S</div>
        <div>
            <b>SIMOLA Operator</b>
            <small>Tanpa login · terikat ke perangkat ini</small>
        </div>
    </div>
    <span class="device-pill">{{ $device->displayName() }}</span>
</header>

@if(session('success'))
<div class="alert">{{ session('success') }}</div>
@endif

<section class="welcome">
    <h1>{{ $device->displayName() }}</h1>
    <p>
        Gunakan mini panel di kanan bawah untuk chat dengan pengawas dan sticky note.
        Riwayat mengikuti nomor PC, sehingga operator shift berikutnya tetap melihat
        konteks PC ini.
    </p>
    <div class="quick">
        <span class="q">Status: <strong>Aktif</strong></span>
        <span class="q">Catatan: <strong>{{ $notes->count() }}</strong></span>
        <span class="q">Transfer: <strong>Perlu persetujuan pengawas</strong></span>
    </div>
</section>
</div>

<button id="launcher" class="launch" type="button">
    <span class="launch-ico">💬</span>
    <span><b>Chat Operasional</b><small>{{ $device->displayName() }}</small></span>
    <span id="launcherBadge" class="launch-badge"></span>
</button>

<section id="widget" class="widget">
<header class="head">
    <div class="title">
        <div class="avatar">💬</div>
        <div><b>Chat Operasional</b><small>{{ $device->displayName() }}</small></div>
    </div>
    <button id="minimize" class="min" type="button">—</button>
</header>

<nav class="tabs">
    <button class="tab active" type="button" data-tab="chat">💬 Chat</button>
    <button class="tab" type="button" data-tab="notes">📝 Catatan</button>
</nav>

<div class="view chat-view active" data-view="chat">
    <div id="messages" class="messages">
        @forelse($messages as $m)
        <div class="row {{ $m->sender_type === 'operator' ? 'mine' : '' }}" data-id="{{ $m->id }}">
            <div class="msg">
                <div class="meta">{{ $m->sender_type === 'operator' ? 'PC ini' : ($m->sender?->name ?? 'Pengawas') }} · {{ $m->created_at?->format('H:i') }}</div>
                <div class="bubble">{{ $m->body }}</div>
                @if($m->sender_type === 'operator')
                <div class="read">{{ $m->read_at ? 'Dibaca' : 'Terkirim' }}</div>
                @endif
            </div>
        </div>
        @empty
        <div class="empty">Belum ada percakapan.<br>Tulis pesan untuk pengawas.</div>
        @endforelse
    </div>

    <form id="chatForm" class="compose" action="{{ route('operator-device.send') }}">
        @csrf
        <textarea id="chatInput" rows="2" maxlength="2000" placeholder="Tulis pesan..." required></textarea>
        <button class="send">➤</button>
    </form>
</div>

<div class="view notes-view" data-view="notes">
    <form class="noteform" method="POST" action="{{ route('operator-device.notes.store') }}">
        @csrf
        <div class="sticky">
            <textarea
                id="stickyInput"
                name="body"
                maxlength="4000"
                placeholder="Contoh:&#10;G P 9114 UG : 13:30&#10;R P 9114 UG : 14:00&#10;PJ P 9114 UG : 13:00"
                required
            ></textarea>

            <div class="note-hint">
                <b>1 baris = 1 sticky note.</b>
                Setelah ditempel, tiap baris memiliki checkbox sendiri.
            </div>

            <div class="quickbtns">
                <button class="quickbtn" type="button" data-q="G">+ G</button>
                <button class="quickbtn" type="button" data-q="R">+ R</button>
                <button class="quickbtn" type="button" data-q="PJ">+ PJ</button>
            </div>
            <div id="durationPreview" class="preview"></div>
            <div class="note-save-row">
                <button class="note-save" type="submit">Tempel Catatan</button>
            </div>
        </div>
    </form>

    <div class="notes">
        @forelse($notes as $note)
        @php($statusItem = $outgoing->get($note->id))
        @php($transfer = $statusItem?->transfer)
        @php(
            $noteLineCount = collect(
                preg_split('/\R/u', trim($note->body)) ?: []
            )
                ->map(fn ($line) => trim((string) $line))
                ->filter(fn ($line) => $line !== '')
                ->count()
        )
        <article class="note {{ $note->source_device_id ? 'received' : '' }}">
            <input
                class="note-select"
                type="checkbox"
                value="{{ $note->id }}"
                title="Pilih untuk dikirim"
            >

            @if($note->sourceDevice)
                <span class="note-origin">
                    Dari {{ $note->sourceDevice->displayName() }}
                </span>
            @elseif($transfer)
                <span class="note-status {{ $transfer->status }}">
                    @if($transfer->status === 'pending')
                        Menunggu → PC {{ $transfer->targetDevice?->pc_number }}
                    @elseif($transfer->status === 'approved')
                        Terkirim → PC {{ $transfer->targetDevice?->pc_number }}
                    @else
                        Ditolak
                    @endif
                </span>
            @endif

            <div class="note-body" data-raw="{{ e($note->body) }}">{{ $note->body }}</div>

            <div class="note-foot">
                <span>
                    {{ $note->updated_at?->format('d-m H:i') }}
                    @if($noteLineCount > 1)
                        · {{ $noteLineCount }} bagian
                    @endif
                </span>

                <div class="note-actions">
                    @if(
                        $noteLineCount > 1
                        && !$note->source_device_id
                        && !$transfer
                    )
                        <form
                            method="POST"
                            action="{{ route('operator-device.notes.split', $note) }}"
                        >
                            @csrf
                            <button
                                class="split-note"
                                type="submit"
                                title="Pisahkan tiap baris menjadi sticky note sendiri"
                            >
                                Pisahkan
                            </button>
                        </form>
                    @endif

                    <form
                        method="POST"
                        action="{{ route('operator-device.notes.destroy', $note) }}"
                    >
                        @csrf
                        @method('DELETE')
                        <button class="del" type="submit">× Hapus</button>
                    </form>
                </div>
            </div>
        </article>
        @empty
        <div class="empty">Belum ada sticky note.</div>
        @endforelse
    </div>

    <div id="transferBar" class="transferbar">
        <span id="selectedCount">0 dipilih</span>

        <select id="targetDevice">
            <option value="">Kirim ke PC...</option>
            @foreach($targets as $target)
                <option value="{{ $target->id }}">
                    {{ $target->displayName() }}
                </option>
            @endforeach
        </select>

        <button
            id="transferButton"
            class="transferbtn"
            type="button"
            {{ $targets->isEmpty() ? 'disabled' : '' }}
        >
            Ajukan
        </button>
    </div>
</div>
</section>

<script>
(()=>{
const csrf=document.querySelector('meta[name="csrf-token"]').content;
const widget=document.getElementById('widget');
const launcher=document.getElementById('launcher');
const minimize=document.getElementById('minimize');
const badge=document.getElementById('launcherBadge');
const tabs=[...document.querySelectorAll('[data-tab]')];
const views=[...document.querySelectorAll('[data-view]')];
const openKey='simola-device-widget-open-v202';
const tabKey='simola-device-widget-tab-v202';

const setTab=name=>{
    tabs.forEach(t=>t.classList.toggle('active',t.dataset.tab===name));
    views.forEach(v=>v.classList.toggle('active',v.dataset.view===name));
    try{localStorage.setItem(tabKey,name)}catch{}
};

const open=()=>{
    widget.classList.add('open');
    launcher.style.display='none';
    try{localStorage.setItem(openKey,'1')}catch{}
};

const close=()=>{
    widget.classList.remove('open');
    launcher.style.display='inline-flex';
    try{localStorage.setItem(openKey,'0')}catch{}
};

launcher.addEventListener('click',()=>{badge.style.display='none';open()});
minimize.addEventListener('click',close);
tabs.forEach(t=>t.addEventListener('click',()=>setTab(t.dataset.tab)));

try{
    const saved=localStorage.getItem(tabKey);
    if(saved==='chat'||saved==='notes')setTab(saved);
    if(localStorage.getItem(openKey)!=='0')open();
}catch{open()}

/* Chat */
const box=document.getElementById('messages');
const chatForm=document.getElementById('chatForm');
const chatInput=document.getElementById('chatInput');
let last=Number(box.querySelector('[data-id]:last-of-type')?.dataset.id||0);
let first=true;
let audio=null;
let armed=false;

const esc=v=>{const d=document.createElement('div');d.textContent=v??'';return d.innerHTML};
const arm=()=>{
    armed=true;
    try{
        const A=window.AudioContext||window.webkitAudioContext;
        if(A&&!audio)audio=new A();
        if(audio?.state==='suspended')audio.resume().catch(()=>{});
    }catch{}
};
const ding=()=>{
    if(!armed||!audio)return;
    try{
        const n=audio.currentTime,o=audio.createOscillator(),g=audio.createGain();
        o.type='sine';o.frequency.setValueAtTime(735,n);o.frequency.exponentialRampToValueAtTime(930,n+.12);
        g.gain.setValueAtTime(.0001,n);g.gain.exponentialRampToValueAtTime(.06,n+.02);g.gain.exponentialRampToValueAtTime(.0001,n+.28);
        o.connect(g);g.connect(audio.destination);o.start(n);o.stop(n+.3);
    }catch{}
};
document.addEventListener('pointerdown',arm,{once:true,passive:true});

const renderMessages=items=>{
    const stick=box.scrollHeight-box.scrollTop-box.clientHeight<110;
    box.innerHTML=items.length?items.map(m=>`
        <div class="row ${m.is_mine?'mine':''}" data-id="${Number(m.id)}">
            <div class="msg">
                <div class="meta">${esc(m.is_mine?'PC ini':m.sender_name)} · ${esc(m.time)}</div>
                <div class="bubble">${esc(m.body)}</div>
                ${m.is_mine?`<div class="read">${m.read?'Dibaca':'Terkirim'}</div>`:''}
            </div>
        </div>
    `).join(''):'<div class="empty">Belum ada percakapan.</div>';
    if(stick)box.scrollTop=box.scrollHeight;
};

const poll=async()=>{
    try{
        const r=await fetch(@json(route('operator-device.messages')),{headers:{Accept:'application/json'},credentials:'same-origin'});
        if(r.status===401){location.reload();return}
        if(!r.ok)return;
        const d=await r.json(),items=d.messages||[];
        const newest=items.length?Number(items[items.length-1].id):0;
        const incoming=items.some(m=>Number(m.id)>last&&m.sender_type==='supervisor');
        renderMessages(items);
        if(!first&&incoming){ding();if(!widget.classList.contains('open')){badge.style.display='inline-flex';badge.textContent='1'}}
        last=Math.max(last,newest);first=false;
    }catch{}
};

chatForm.addEventListener('submit',async e=>{
    e.preventDefault();arm();
    const body=chatInput.value.trim();if(!body)return;
    chatInput.disabled=true;
    try{
        const r=await fetch(chatForm.action,{method:'POST',credentials:'same-origin',headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrf},body:JSON.stringify({body})});
        if(r.status===401){location.reload();return}
        if(r.ok){chatInput.value='';await poll();box.scrollTop=box.scrollHeight}else{alert('Pesan gagal dikirim.')}
    }finally{chatInput.disabled=false;chatInput.focus()}
});
chatInput.addEventListener('keydown',e=>{if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();chatForm.requestSubmit()}});
box.scrollTop=box.scrollHeight;
setInterval(poll,3000);

/* Sticky note + live duration */
const sticky=document.getElementById('stickyInput');
const preview=document.getElementById('durationPreview');
const noteSaveButton=document.querySelector('.note-save');
const qbtns=[...document.querySelectorAll('[data-q]')];
const noteBodies=[...document.querySelectorAll('.note-body')];

const noteLineCount=()=>{
    if(!sticky)return 0;

    return sticky.value
        .split(/\r?\n/)
        .map(line=>line.trim())
        .filter(Boolean)
        .length;
};

const refreshNoteSaveLabel=()=>{
    if(!noteSaveButton)return;

    const count=noteLineCount();

    noteSaveButton.textContent=
        count>1
            ? `Tempel ${count} Catatan`
            : 'Tempel Catatan';
};
const two=n=>String(n).padStart(2,'0');
const clock=()=>{const d=new Date();return `${two(d.getHours())}:${two(d.getMinutes())}`};

const normalize=k=>{
    k=String(k||'').toLowerCase();
    if(k==='g'||k==='greeting')return'g';
    if(k==='r'||k==='remind')return'r';
    if(k==='pj'||k==='parkir')return'pj';
    return k;
};

const parseLine=line=>{
    const raw=String(line||'').trim();if(!raw)return null;
    let m=raw.match(/^\s*(G|Greeting|R|Remind|PJ|Parkir)\s*(.*?)\s*[:=-]\s*([01]?\d|2[0-3])[:.]([0-5]\d)\s*$/i);
    if(m)return{raw,key:normalize(m[1]),subject:String(m[2]||'').trim(),h:Number(m[3]),m:Number(m[4])};
    m=raw.match(/^\s*(G|Greeting|R|Remind|PJ|Parkir)\s+([01]?\d|2[0-3])[:.]([0-5]\d)\b(.*)$/i);
    if(m)return{raw,key:normalize(m[1]),subject:String(m[4]||'').replace(/^\s*[-:=]\s*/,'').trim(),h:Number(m[2]),m:Number(m[3])};
    return null;
};

const fmt=x=>{x=Math.max(0,Math.round(x));if(x<60)return`${x} mnt`;const h=Math.floor(x/60),m=x%60;return m?`${h}j ${m}m`:`${h}j`};
const state=item=>{
    const now=new Date(),point=new Date(now);point.setHours(item.h,item.m,0,0);
    if(item.key==='g'||item.key==='r'){
        const d=(point-now)/60000;
        if(d>.5)return{c:'future',t:`${fmt(d)} lagi`};
        if(d>=-.5)return{c:'now',t:'sekarang'};
        return{c:'overdue',t:`lewat ${fmt(Math.abs(d))}`};
    }
    if(point>now)point.setDate(point.getDate()-1);
    return{c:'elapsed',t:`${fmt((now-point)/60000)} parkir`};
};
const renderDuration=(raw,previewMode=false)=>{
    let found=false;
    const html=String(raw||'').split(/\r?\n/).map(line=>{
        const it=parseLine(line);
        if(!it)return previewMode?'':`<div class="duration"><span class="kind">•</span><span class="detail">${esc(line)||'&nbsp;'}</span><span></span></div>`;
        found=true;const s=state(it),tm=`${two(it.h)}:${two(it.m)}`,kind=it.key==='g'?'G':it.key==='r'?'R':'PJ',detail=it.subject?`${it.subject} · ${tm}`:tm;
        return`<div class="duration"><span class="kind">${kind}</span><span class="detail">${esc(detail)}</span><span class="dur ${s.c}">${esc(s.t)}</span></div>`;
    }).filter(Boolean).join('');
    return previewMode&&!found?'':html;
};
const refreshPreview=()=>{
    const h=renderDuration(sticky.value,true);
    preview.innerHTML=h?`<div class="preview-title">Status Waktu</div>${h}`:'';
};
const refreshNotes=()=>{
    noteBodies.forEach(el=>{
        const raw=el.dataset.raw||el.textContent||'';
        el.dataset.raw=raw;
        el.innerHTML=renderDuration(raw,false);
    });
};
qbtns.forEach(b=>b.addEventListener('click',()=>{
    const label=b.dataset.q||'G',cur=sticky.value.trim(),line=`${label}  : ${clock()}`;
    sticky.value=cur?`${cur}\n${line}`:line;
    const p=sticky.value.lastIndexOf(' : ');
    sticky.focus();if(p>=0)sticky.setSelectionRange(p,p);
    refreshPreview();
    refreshNoteSaveLabel();
}));
sticky.addEventListener('input',()=>{
    refreshPreview();
    refreshNoteSaveLabel();
});
refreshNotes();
refreshPreview();
refreshNoteSaveLabel();
setInterval(()=>{refreshNotes();refreshPreview()},15000);

/* Selective note transfer */
const checks=[...document.querySelectorAll('.note-select')];
const transferBar=document.getElementById('transferBar');
const countEl=document.getElementById('selectedCount');
const target=document.getElementById('targetDevice');
const transferButton=document.getElementById('transferButton');
const syncTransfer=()=>{
    const selected=checks.filter(c=>c.checked);
    countEl.textContent=`${selected.length} dipilih`;
    transferBar.classList.toggle('show',selected.length>0);
};
checks.forEach(c=>c.addEventListener('change',syncTransfer));
transferButton.addEventListener('click',async()=>{
    const ids=checks.filter(c=>c.checked).map(c=>Number(c.value));
    const targetId=Number(target.value||0);
    if(!ids.length){alert('Pilih catatan terlebih dahulu.');return}
    if(!targetId){alert('Pilih PC tujuan.');return}
    transferButton.disabled=true;
    try{
        const r=await fetch(@json(route('operator-device.transfers.store')),{method:'POST',credentials:'same-origin',headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrf},body:JSON.stringify({note_ids:ids,target_device_id:targetId})});
        if(r.status===401){location.reload();return}
        const d=await r.json().catch(()=>({}));
        if(!r.ok){alert(d.message||'Permintaan gagal dikirim.');return}
        alert(d.message||'Permintaan dikirim ke pengawas.');
        location.reload();
    }finally{transferButton.disabled=false}
});
})();
</script>
</body>
</html>
