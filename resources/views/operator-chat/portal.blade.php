<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>SIMOLA Operator</title>

<style>
*{box-sizing:border-box}
:root{font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}
html,body{margin:0;min-height:100%}
body{min-height:100vh;background:radial-gradient(circle at 15% 10%,rgba(37,99,235,.08),transparent 30%),#eef3f9;color:#182338}
button,textarea,input{font:inherit}

.op-page{min-height:100vh;padding:20px}
.op-top{max-width:980px;margin:auto;display:flex;align-items:center;justify-content:space-between;gap:14px;min-height:56px}
.op-brand{display:flex;align-items:center;gap:10px}
.op-logo{width:35px;height:35px;display:grid;place-items:center;border-radius:11px;background:#14213a;color:white;font-size:13px;font-weight:900}
.op-brand b{display:block;font-size:14px}
.op-brand small{display:block;margin-top:2px;color:#708199;font-size:9px}
.op-actions{display:flex;align-items:center;gap:7px}
.op-user,.op-logout{border:1px solid #d4dde8;border-radius:9px;background:rgba(255,255,255,.9);color:#42516a;padding:7px 9px;font-size:9px}
.op-logout{cursor:pointer}

.op-welcome{max-width:980px;margin:52px auto 0;padding:26px;border:1px solid #dfe6ef;border-radius:18px;background:rgba(255,255,255,.76);box-shadow:0 12px 38px rgba(15,23,42,.06)}
.op-welcome h1{margin:0;font-size:20px}
.op-welcome p{max-width:640px;margin:8px 0 0;color:#66758c;font-size:11px;line-height:1.65}
.op-pills{margin-top:17px;display:flex;flex-wrap:wrap;gap:7px}
.op-pill{padding:7px 10px;border:1px solid #dce5ef;border-radius:999px;background:#fff;color:#5b6980;font-size:9px}
.op-pill strong{color:#172033}

.op-launcher{position:fixed;right:18px;bottom:18px;z-index:9000;display:inline-flex;align-items:center;gap:9px;border:0;border-radius:999px;background:#172844;color:white;padding:9px 13px 9px 9px;box-shadow:0 14px 36px rgba(15,23,42,.25);cursor:pointer}
.op-launcher:hover{transform:translateY(-2px)}
.op-launcher-icon{width:31px;height:31px;display:grid;place-items:center;border-radius:999px;background:#2563eb}
.op-launcher-copy{text-align:left}
.op-launcher-copy b{display:block;font-size:10px}
.op-launcher-copy small{display:block;margin-top:1px;color:#b9c6da;font-size:8px}
.op-launcher-badge{display:none;min-width:19px;height:19px;align-items:center;justify-content:center;border-radius:999px;background:#ef4444;color:white;padding:0 5px;font-size:8px;font-weight:900}

.op-widget{position:fixed;right:18px;bottom:18px;z-index:9010;width:min(365px,calc(100vw - 20px));height:min(600px,calc(100vh - 26px));display:none;grid-template-rows:auto auto 1fr;overflow:hidden;border:1px solid #ced8e6;border-radius:18px;background:white;box-shadow:0 22px 66px rgba(15,23,42,.28)}
.op-widget.open{display:grid;animation:widgetIn .18s ease-out}
.op-head{min-height:60px;padding:10px 11px;display:flex;align-items:center;justify-content:space-between;gap:9px;background:#172844;color:white}
.op-title{display:flex;align-items:center;gap:9px;min-width:0}
.op-avatar{width:34px;height:34px;display:grid;place-items:center;border-radius:11px;background:#29446f}
.op-title b{display:block;font-size:10px}
.op-title small{display:block;margin-top:2px;color:#b9c6da;font-size:8px}
.op-head-actions{display:flex;gap:2px}
.op-icon{width:29px;height:29px;display:grid;place-items:center;border:0;border-radius:8px;background:transparent;color:#e9eff8;cursor:pointer}
.op-icon:hover{background:rgba(255,255,255,.1)}

.op-tabs{display:grid;grid-template-columns:1fr 1fr;border-bottom:1px solid #e4e9f0;background:#f8fafc}
.op-tab{border:0;border-bottom:2px solid transparent;background:transparent;color:#7a8799;padding:10px 8px;cursor:pointer;font-size:9px;font-weight:800}
.op-tab.active{border-bottom-color:#2563eb;background:white;color:#1d4ed8}

.op-view{display:none;min-height:0}
.op-view.active{display:grid}
.op-chat-view{grid-template-rows:1fr auto}
.op-chat{min-height:0;overflow:auto;padding:12px;background:#f8fafc;scroll-behavior:smooth}
.op-row{display:flex;margin:8px 0}
.op-row.mine{justify-content:flex-end}
.op-msg{max-width:82%}
.op-meta{margin:0 4px 3px;color:#9aa6b5;font-size:7.5px}
.op-row.mine .op-meta{text-align:right}
.op-bubble{padding:8px 10px;border:1px solid #dbe3ed;border-radius:12px 12px 12px 4px;background:white;color:#29364a;font-size:10px;line-height:1.5;white-space:pre-wrap;word-break:break-word}
.op-row.mine .op-bubble{border-color:#2563eb;border-radius:12px 12px 4px 12px;background:#2563eb;color:white}
.op-read{margin-top:3px;color:#9aa6b5;text-align:right;font-size:7px}
.op-empty{min-height:100%;display:grid;place-items:center;padding:25px;color:#8996a8;text-align:center;font-size:9px;line-height:1.6}
.op-compose{display:flex;gap:6px;padding:9px;border-top:1px solid #e4e9f0;background:white}
.op-compose textarea{flex:1;min-height:39px;max-height:92px;resize:none;border:1px solid #d7e0ea;border-radius:11px;outline:none;background:#f8fafc;color:#27364b;padding:9px 10px;font-size:10px}
.op-compose textarea:focus{border-color:#60a5fa;box-shadow:0 0 0 3px rgba(59,130,246,.09)}
.op-send{width:42px;border:0;border-radius:11px;background:#2563eb;color:white;cursor:pointer;font-size:15px}

.op-notes-view{grid-template-rows:auto 1fr;background:#f1f5f9}
.op-noteform{padding:11px;border-bottom:1px solid #e2e8f0;background:#f8fafc}
.op-sticky-compose{
    position:relative;
    padding:12px;
    border:1px solid #ecc85e;
    border-radius:10px;
    background:linear-gradient(155deg,#fff8bd 0%,#ffef8c 100%);
    box-shadow:0 8px 20px rgba(146,104,16,.10)
}
.op-sticky-compose:before{
    content:"";
    position:absolute;
    top:-1px;
    left:50%;
    width:48px;
    height:7px;
    transform:translateX(-50%);
    border-radius:0 0 5px 5px;
    background:rgba(255,255,255,.58)
}
.op-noteform textarea{
    width:100%;
    min-height:76px;
    resize:vertical;
    border:0;
    outline:none;
    background:transparent;
    color:#493911;
    padding:5px 4px 7px;
    font-size:10px;
    line-height:1.6
}
.op-noteform textarea::placeholder{color:#8b7435}
.op-quick-times{
    display:flex;
    flex-wrap:wrap;
    gap:6px;
    margin-top:3px;
    padding-top:8px;
    border-top:1px dashed rgba(117,88,14,.18)
}
.op-quick-note{
    min-width:42px;
    border:1px solid rgba(117,88,14,.18);
    border-radius:999px;
    background:rgba(255,255,255,.62);
    color:#684f12;
    padding:5px 9px;
    cursor:pointer;
    font-size:7.5px;
    font-weight:900;
    box-shadow:0 1px 2px rgba(96,67,8,.05)
}
.op-quick-note:hover{
    background:#fff;
    transform:translateY(-1px)
}
.op-note-row{
    margin-top:9px;
    display:flex;
    align-items:center;
    justify-content:flex-end;
    gap:7px
}
.op-save{
    border:0;
    border-radius:9px;
    background:#172844;
    color:white;
    padding:8px 12px;
    cursor:pointer;
    font-size:8px;
    font-weight:900;
    box-shadow:0 4px 10px rgba(23,40,68,.16)
}
.op-notes{overflow:auto;padding:11px;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));align-content:start;gap:9px}
.op-note{position:relative;min-height:104px;margin:0;padding:11px 10px 9px;border:1px solid #efcf76;border-radius:3px;background:linear-gradient(145deg,#fff6ad,#ffeb7c);box-shadow:0 5px 12px rgba(146,104,16,.11);transform:rotate(-.35deg)}
.op-note:nth-child(even){background:linear-gradient(145deg,#fff1a4,#ffe477);transform:rotate(.35deg)}
.op-note.pinned{border-color:#e8af43;background:linear-gradient(145deg,#ffe995,#ffd961)}
.op-note:before{content:"";position:absolute;top:-1px;left:50%;width:38px;height:7px;transform:translateX(-50%);border-radius:0 0 4px 4px;background:rgba(255,255,255,.44)}
.op-note-body{color:#4b3a13;font-size:9px;line-height:1.55;white-space:pre-wrap;word-break:break-word}
.op-note-body.is-duration-rendered{white-space:normal}
.op-duration-line{
    display:grid;
    grid-template-columns:auto minmax(0,1fr) auto;
    align-items:center;
    gap:7px;
    margin:0 0 5px;
    min-height:25px
}
.op-duration-line:last-child{margin-bottom:0}
.op-duration-kind{
    min-width:24px;
    height:22px;
    display:inline-grid;
    place-items:center;
    border-radius:7px;
    background:rgba(255,255,255,.72);
    color:#5d4813;
    border:1px solid rgba(96,67,8,.12);
    font-size:7.5px;
    font-weight:950
}
.op-duration-text{
    min-width:0;
    color:#493911;
    white-space:pre-wrap;
    word-break:break-word;
    font-size:8.5px;
    line-height:1.35
}
.op-duration-badge{
    display:inline-flex;
    align-items:center;
    min-height:20px;
    padding:3px 7px;
    border:1px solid rgba(90,67,13,.15);
    border-radius:999px;
    background:rgba(255,255,255,.7);
    color:#5f4a13;
    font-size:6.8px;
    font-weight:950;
    white-space:nowrap
}
.op-duration-badge.future{background:#dbeafe;color:#1d4ed8;border-color:#bfdbfe}
.op-duration-badge.overdue{background:#fee2e2;color:#b91c1c;border-color:#fecaca}
.op-duration-badge.elapsed{background:#fef3c7;color:#92400e;border-color:#fde68a}
.op-duration-badge.now{background:#dcfce7;color:#166534;border-color:#bbf7d0}
.op-live-preview{
    margin-top:9px;
    padding:9px;
    border:1px solid rgba(117,88,14,.12);
    border-radius:9px;
    background:rgba(255,255,255,.34)
}
.op-live-preview:empty{display:none}
.op-live-preview-title{
    margin-bottom:7px;
    color:#7a641f;
    font-size:6.7px;
    font-weight:950;
    text-transform:uppercase;
    letter-spacing:.07em
}
.op-live-preview .op-duration-line{margin-bottom:4px}
.op-note-foot{margin-top:10px;display:flex;justify-content:space-between;align-items:center;gap:6px;color:#8d742f;font-size:7px}
.op-del{border:0;background:transparent;color:#9f3d2f;cursor:pointer;font-size:7px;font-weight:800}
.op-notes .op-empty{grid-column:1/-1;min-height:180px}

.op-notify{animation:notify .72s ease 2}
@keyframes widgetIn{from{opacity:0;transform:translateY(9px) scale(.985)}to{opacity:1;transform:none}}
@keyframes notify{0%,100%{transform:translateY(0) scale(1)}45%{transform:translateY(-3px) scale(1.02)}}

body.mini .op-page,body.mini .op-launcher{display:none!important}
body.mini .op-widget{inset:0;width:100vw;height:100vh;display:grid!important;border:0;border-radius:0;box-shadow:none}

@media(max-width:520px){
.op-page{padding:13px}.op-user{display:none}.op-welcome{margin-top:28px;padding:19px}
.op-widget{right:8px;bottom:8px;width:calc(100vw - 16px);height:min(610px,calc(100vh - 16px))}
.op-launcher{right:10px;bottom:10px}
}
</style>
</head>

<body class="{{ request()->boolean('mini') ? 'mini' : '' }}">
<div class="op-page">
<header class="op-top">
    <div class="op-brand">
        <div class="op-logo">S</div>
        <div><b>SIMOLA Operator</b><small>Komunikasi operasional PC</small></div>
    </div>
    <div class="op-actions">
        <span class="op-user">{{ auth()->user()->name }}</span>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="op-logout">Keluar</button></form>
    </div>
</header>

<section class="op-welcome">
    <h1>Portal Operator</h1>
    <p>
        Gunakan mini chat di kanan bawah untuk bertanya kepada pengawas atau menyimpan catatan pribadi.
        Panel dapat diminimalkan dan dibuka sebagai jendela kecil terpisah.
    </p>
    <div class="op-pills">
        <span class="op-pill">Armada: <strong>{{ $assignment?$assignment->displayFleetType():'-' }}</strong></span>
        <span class="op-pill">PC: <strong>{{ $assignment?'PC '.$assignment->pc_number:'-' }}</strong></span>
        <span class="op-pill">Chat: <strong>{{ $thread?->status==='resolved'?'Selesai':'Aktif' }}</strong></span>
        <span class="op-pill">Catatan: <strong>{{ $notes->count() }}</strong></span>
    </div>
</section>
</div>

<button id="opLauncher" class="op-launcher" type="button">
    <span class="op-launcher-icon">💬</span>
    <span class="op-launcher-copy">
        <b>Chat Pengawas</b>
        <small>{{ $assignment?'PC '.$assignment->pc_number:'Belum ditempatkan' }}</small>
    </span>
    <span id="opBadge" class="op-launcher-badge"></span>
</button>

<section id="opWidget" class="op-widget {{ request()->boolean('mini')?'open':'' }}">
<header class="op-head">
    <div class="op-title">
        <div class="op-avatar">💬</div>
        <div>
            <b>Chat Operasional</b>
            <small>
                @if($assignment)
                    {{ $assignment->displayFleetType() }} · PC {{ $assignment->pc_number }}
                @else
                    Belum ditempatkan
                @endif
            </small>
        </div>
    </div>

    <div class="op-head-actions">
        @unless(request()->boolean('mini'))
            <button id="opPopout" class="op-icon" type="button" title="Jendela mini">↗</button>
            <button id="opMinimize" class="op-icon" type="button" title="Minimalkan">—</button>
        @endunless
        @if(request()->boolean('mini'))
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="op-icon" type="submit" title="Keluar">⏻</button></form>
        @endif
    </div>
</header>

<nav class="op-tabs">
    <button class="op-tab active" type="button" data-tab="chat">💬 Chat</button>
    <button class="op-tab" type="button" data-tab="notes">📝 Catatan</button>
</nav>

@if($assignment)
<div class="op-view op-chat-view active" data-view="chat">
    <div id="chat" class="op-chat">
        @forelse($messages as $m)
        <div class="op-row {{ $m->sender_user_id===auth()->id()?'mine':'' }}" data-id="{{ $m->id }}">
            <div class="op-msg">
                <div class="op-meta">{{ $m->sender_user_id===auth()->id()?'Anda':($m->sender?->name??'Pengawas') }} · {{ $m->created_at?->format('H:i') }}</div>
                <div class="op-bubble">{{ $m->body }}</div>
                @if($m->sender_user_id===auth()->id())
                    <div class="op-read">{{ $m->read_at?'Dibaca':'Terkirim' }}</div>
                @endif
            </div>
        </div>
        @empty
        <div class="op-empty">Belum ada percakapan.<br>Tulis pertanyaan untuk pengawas.</div>
        @endforelse
    </div>

    <form id="chatForm" class="op-compose" action="{{ route('operator-chat.portal.send') }}">
        @csrf
        <textarea id="chatInput" rows="2" maxlength="2000" placeholder="Tulis pesan..." required></textarea>
        <button class="op-send">➤</button>
    </form>
</div>
@else
<div class="op-view op-chat-view active" data-view="chat">
    <div class="op-empty">Akun belum ditempatkan ke PC.<br>Hubungi pengawas / Developer.</div><div></div>
</div>
@endif

<div class="op-view op-notes-view" data-view="notes">
    <form class="op-noteform" method="POST" action="{{ route('operator-chat.notes.store') }}">
        @csrf

        <div class="op-sticky-compose">
            <textarea
                id="stickyNoteInput"
                name="body"
                rows="4"
                maxlength="4000"
                placeholder="Contoh: G P 9114 UG : 13:30&#10;R P 9114 UG : 14:00&#10;PJ P 9114 UG : 13:00"
                required
            ></textarea>

            <div class="op-quick-times">
                <button class="op-quick-note" type="button" data-note-template="G">+ G</button>
                <button class="op-quick-note" type="button" data-note-template="R">+ R</button>
                <button class="op-quick-note" type="button" data-note-template="PJ">+ PJ</button>
            </div>

            <div id="stickyDurationPreview" class="op-live-preview"></div>

            <div class="op-note-row">
                <button class="op-save" type="submit">Tempel Catatan</button>
            </div>
        </div>
    </form>

    <div class="op-notes">
        @forelse($notes as $n)
        <article class="op-note {{ $n->is_pinned?'pinned':'' }}">
            <div class="op-note-body">{{ $n->body }}</div>
            <div class="op-note-foot">
                <span>{{ $n->updated_at?->format('d-m H:i') }}</span>
                <form method="POST" action="{{ route('operator-chat.notes.destroy',$n) }}">
                    @csrf @method('DELETE')
                    <button class="op-del" type="submit">× Hapus</button>
                </form>
            </div>
        </article>
        @empty
        <div class="op-empty">
            Belum ada sticky note.<br>
            Gunakan G / R / PJ untuk catatan waktu.
        </div>
        @endforelse
    </div>
</div>
</section>

<script>
(()=>{
const mini=@json(request()->boolean('mini'));
const widget=document.getElementById('opWidget'),launcher=document.getElementById('opLauncher'),badge=document.getElementById('opBadge');
const minBtn=document.getElementById('opMinimize'),pop=document.getElementById('opPopout');
const stateKey='simola-operator-floating-open-v118',tabKey='simola-operator-floating-tab-v118';
const tabs=[...document.querySelectorAll('.op-tab')],views=[...document.querySelectorAll('[data-view]')];

const switchTab=n=>{tabs.forEach(t=>t.classList.toggle('active',t.dataset.tab===n));views.forEach(v=>v.classList.toggle('active',v.dataset.view===n));try{localStorage.setItem(tabKey,n)}catch{}};

const stickyInput=document.getElementById('stickyNoteInput');
const stickyPreview=document.getElementById('stickyDurationPreview');
const quickNotes=[...document.querySelectorAll('[data-note-template]')];
const noteBodies=[...document.querySelectorAll('.op-note-body')];
const two=n=>String(n).padStart(2,'0');
const currentClock=()=>{const d=new Date();return `${two(d.getHours())}:${two(d.getMinutes())}`};

const durationConfig={
    greeting:{mode:'future',label:'Greeting'},
    remind:{mode:'future',label:'Remind'},
    parkir:{mode:'elapsed',label:'Parkir'},
    pj:{mode:'elapsed',label:'Parkir'}
};

const escNote=v=>{
    const d=document.createElement('div');
    d.textContent=v??'';
    return d.innerHTML;
};

const formatMinutes=minutes=>{
    const total=Math.max(0,Math.round(minutes));
    if(total<60)return `${total} mnt`;
    const hours=Math.floor(total/60);
    const mins=total%60;
    return mins?`${hours}j ${mins}m`:`${hours}j`;
};

const normalizeDurationKey=value=>{
    const key=String(value||'').trim().toLowerCase();

    if(key==='g'||key==='greeting')return'greeting';
    if(key==='r'||key==='remind')return'remind';
    if(key==='pj'||key==='parkir')return'pj';

    return key;
};

const parseTimedLine=line=>{
    const raw=String(line||'').trim();

    if(!raw)return null;

    let match=raw.match(
        /^\s*(G|Greeting|R|Remind|PJ|Parkir)\s*(.*?)\s*[:=-]\s*([01]?\d|2[0-3])[:.]([0-5]\d)\s*$/i
    );

    if(match){
        const key=normalizeDurationKey(match[1]);
        const cfg=durationConfig[key];

        if(!cfg)return null;

        return{
            original:raw,
            type:key,
            mode:cfg.mode,
            label:cfg.label,
            shortLabel:key==='greeting'?'G':key==='remind'?'R':'PJ',
            subject:String(match[2]||'').trim(),
            hour:Number(match[3]),
            minute:Number(match[4]),
            rest:''
        };
    }

    match=raw.match(
        /^\s*(G|Greeting|R|Remind|PJ|Parkir)\s+([01]?\d|2[0-3])[:.]([0-5]\d)\b(.*)$/i
    );

    if(!match)return null;

    const key=normalizeDurationKey(match[1]);
    const cfg=durationConfig[key];

    if(!cfg)return null;

    return{
        original:raw,
        type:key,
        mode:cfg.mode,
        label:cfg.label,
        shortLabel:key==='greeting'?'G':key==='remind'?'R':'PJ',
        subject:'',
        hour:Number(match[2]),
        minute:Number(match[3]),
        rest:match[4]||''
    };
};

const calcDuration=item=>{
    const now=new Date();
    const point=new Date(now);
    point.setHours(item.hour,item.minute,0,0);

    if(item.mode==='future'){
        const diff=(point-now)/60000;

        if(diff>0.5){
            return{
                className:'future',
                text:`${formatMinutes(diff)} lagi`
            };
        }

        if(diff>=-0.5){
            return{
                className:'now',
                text:'sekarang'
            };
        }

        return{
            className:'overdue',
            text:`lewat ${formatMinutes(Math.abs(diff))}`
        };
    }

    // Parkir/PJ adalah jam mulai parkir.
    // Bila jam yang dimasukkan lebih besar dari waktu sekarang,
    // diasumsikan parkir dimulai pada hari sebelumnya.
    if(point>now){
        point.setDate(point.getDate()-1);
    }

    const elapsed=(now-point)/60000;

    return{
        className:'elapsed',
        text:`${formatMinutes(elapsed)} parkir`
    };
};

const renderDurationLines=(raw,preview=false)=>{
    const lines=String(raw||'').split(/\r?\n/);
    let found=false;

    const rendered=lines.map(line=>{
        const item=parseTimedLine(line);

        if(!item){
            if(preview)return '';

            return `<div class="op-duration-line"><span class="op-duration-kind">•</span><span class="op-duration-text">${escNote(line)||'&nbsp;'}</span><span></span></div>`;
        }

        found=true;

        const state=calcDuration(item);
        const clock=`${two(item.hour)}:${two(item.minute)}`;

        let detail='';

        if(item.subject){
            detail=`${item.subject} · ${clock}`;
        }else if(item.rest&&String(item.rest).trim()){
            detail=`${clock} ${String(item.rest).trim()}`;
        }else{
            detail=clock;
        }

        return `<div class="op-duration-line"><span class="op-duration-kind">${escNote(item.shortLabel||item.label)}</span><span class="op-duration-text">${escNote(detail)}</span><span class="op-duration-badge ${state.className}">${escNote(state.text)}</span></div>`;
    }).filter(Boolean).join('');

    if(preview&&!found)return '';

    return rendered;
};

const refreshSavedDurations=()=>{
    noteBodies.forEach(body=>{
        const raw=body.dataset.rawNote??body.textContent??'';
        body.dataset.rawNote=raw;
        body.classList.add('is-duration-rendered');
        body.innerHTML=renderDurationLines(raw,false);
    });
};

const refreshPreview=()=>{
    if(!stickyPreview||!stickyInput)return;
    const rendered=renderDurationLines(stickyInput.value,true);

    stickyPreview.innerHTML=rendered
        ? `<div class="op-live-preview-title">Status Waktu</div>${rendered}`
        : '';
};

quickNotes.forEach(btn=>{
    btn.addEventListener('click',()=>{
        if(!stickyInput)return;

        const label=btn.dataset.noteTemplate||'G';
        const current=stickyInput.value.trim();
        const template=`${label}  : ${currentClock()}`;

        stickyInput.value=current
            ? `${current}\n${template}`
            : template;

        stickyInput.focus();

        const position=stickyInput.value.lastIndexOf(' : ');

        if(position>=0){
            stickyInput.setSelectionRange(
                position,
                position
            );
        }

        refreshPreview();
    });
});

stickyInput?.addEventListener('input',refreshPreview);

refreshSavedDurations();
refreshPreview();

setInterval(()=>{
    refreshSavedDurations();
    refreshPreview();
},15000);
const open=()=>{widget.classList.add('open');if(!mini){launcher.style.display='none';try{localStorage.setItem(stateKey,'1')}catch{}}};
const close=()=>{if(mini)return;widget.classList.remove('open');launcher.style.display='inline-flex';try{localStorage.setItem(stateKey,'0')}catch{}};

launcher?.addEventListener('click',()=>{badge.style.display='none';badge.textContent='';open()});
minBtn?.addEventListener('click',close);
tabs.forEach(t=>t.addEventListener('click',()=>switchTab(t.dataset.tab)));
pop?.addEventListener('click',()=>window.open(@json(route('operator-chat.portal',['mini'=>1])),'simolaOperatorMini','width=390,height=650,resizable=yes,scrollbars=no'));

if(!mini){try{const st=localStorage.getItem(tabKey);if(st==='chat'||st==='notes')switchTab(st);if(localStorage.getItem(stateKey)==='1')open()}catch{}}

@if($assignment)
const box=document.getElementById('chat'),form=document.getElementById('chatForm'),input=document.getElementById('chatInput');
const csrf=document.querySelector('meta[name=csrf-token]').content;
let last=Number(box.querySelector('[data-id]:last-of-type')?.dataset.id||0),first=true,ctx=null,armed=false;

const esc=v=>{const d=document.createElement('div');d.textContent=v??'';return d.innerHTML};
const arm=()=>{armed=true;try{const A=window.AudioContext||window.webkitAudioContext;if(A&&!ctx)ctx=new A();if(ctx?.state==='suspended')ctx.resume().catch(()=>{})}catch{}};
const ding=()=>{if(!armed||!ctx)return;try{const n=ctx.currentTime,o=ctx.createOscillator(),g=ctx.createGain();o.type='sine';o.frequency.setValueAtTime(735,n);o.frequency.exponentialRampToValueAtTime(930,n+.12);g.gain.setValueAtTime(.0001,n);g.gain.exponentialRampToValueAtTime(.07,n+.02);g.gain.exponentialRampToValueAtTime(.0001,n+.3);o.connect(g);g.connect(ctx.destination);o.start(n);o.stop(n+.32)}catch{}};

const render=a=>{
 const stick=box.scrollHeight-box.scrollTop-box.clientHeight<110;
 box.innerHTML=a.length?a.map(m=>`<div class="op-row ${m.is_mine?'mine':''}" data-id="${Number(m.id)}"><div class="op-msg"><div class="op-meta">${esc(m.is_mine?'Anda':m.sender_name)} · ${esc(m.time)}</div><div class="op-bubble">${esc(m.body)}</div>${m.is_mine?`<div class="op-read">${m.read?'Dibaca':'Terkirim'}</div>`:''}</div></div>`).join(''):'<div class="op-empty">Belum ada percakapan.</div>';
 if(stick)box.scrollTop=box.scrollHeight;
};

const notify=()=>{ding();const target=widget.classList.contains('open')?widget:launcher;target.classList.remove('op-notify');void target.offsetWidth;target.classList.add('op-notify');if(!widget.classList.contains('open')){badge.style.display='inline-flex';badge.textContent='1'}};

const poll=async()=>{
 try{
  const r=await fetch(@json(route('operator-chat.portal.messages')),{headers:{Accept:'application/json'},credentials:'same-origin'});
  if(!r.ok)return;
  const d=await r.json(),a=d.messages||[],newest=a.length?Number(a[a.length-1].id):0;
  const incoming=a.some(m=>Number(m.id)>last&&m.sender_type==='supervisor');
  render(a);
  if(!first&&incoming)notify();
  last=Math.max(last,newest);first=false;
 }catch{}
};

form.addEventListener('submit',async e=>{
 e.preventDefault();arm();const body=input.value.trim();if(!body)return;input.disabled=true;
 try{
  const r=await fetch(form.action,{method:'POST',headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrf},credentials:'same-origin',body:JSON.stringify({body})});
  if(r.ok){input.value='';await poll();box.scrollTop=box.scrollHeight}else{alert('Pesan gagal dikirim.')}
 }finally{input.disabled=false;input.focus()}
});
input.addEventListener('keydown',e=>{if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();form.requestSubmit()}});
document.addEventListener('pointerdown',arm,{once:true,passive:true});
box.scrollTop=box.scrollHeight;
setInterval(poll,3000);
@endif
})();
</script>
</body>
</html>
