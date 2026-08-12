@if (config('simola-help.enabled', true))
<div
    id="simola-help-root"
    data-endpoint="{{ route('simola-help.ask') }}"
    data-page-title="{{ e(trim($__env->yieldContent('title')) ?: config('app.name', 'SIMOLA')) }}"
>
    <button
        type="button"
        id="simola-help-bubble"
        aria-label="Buka SIMOLA Help Assistant"
        aria-expanded="false"
        title="Bantuan SIMOLA"
    >
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 3C6.9 3 3 6.5 3 11c0 2.3 1 4.4 2.8 5.9L5 21l4.2-2.1c.9.2 1.8.3 2.8.3 5.1 0 9-3.5 9-8.1S17.1 3 12 3Zm.1 13.2a1 1 0 1 1 0-2 1 1 0 0 1 0 2Zm1.8-5.3c-.7.5-.9.8-.9 1.5v.2h-1.8v-.4c0-1.1.4-1.8 1.3-2.4.7-.5 1-.8 1-1.3 0-.7-.5-1.1-1.4-1.1-.9 0-1.6.4-2.2 1.1L8.7 7.3C9.5 6.3 10.6 5.8 12 5.8c2 0 3.4 1 3.4 2.7 0 1-.4 1.7-1.5 2.4Z"/>
        </svg>
        <span>Bantuan</span>
    </button>

    <section
        id="simola-help-panel"
        aria-label="SIMOLA Help Assistant"
        aria-hidden="true"
    >
        <header class="sha-header">
            <div class="sha-brand">
                <div class="sha-logo">✦</div>
                <div>
                    <strong>SIMOLA Help Assistant</strong>
                    <small>AI + Panduan SIMOLA</small>
                </div>
            </div>
            <div class="sha-header-actions">
                <button
                    type="button"
                    id="simola-help-sound-toggle"
                    aria-label="Matikan suara notifikasi AI"
                    title="Suara balasan AI aktif"
                >🔔</button>
                <button
                type="button"
                id="simola-help-close"
                class="sha-icon-button"
                aria-label="Tutup bantuan"
            >×</button>
            </div>
        </header>

        <div id="simola-help-messages" class="sha-messages" aria-live="polite">
            <div class="sha-message sha-assistant">
                <div class="sha-message-label">SIMOLA</div>
                <div class="sha-message-bubble">
                    Halo {{ auth()->user()?->name ?? 'Pengguna' }}. Saya bisa membantu menjelaskan cara menggunakan fitur SIMOLA. Pilih topik cepat atau tulis pertanyaan Anda.
                </div>
            </div>
        </div>

        <div id="simola-help-quick" class="sha-quick">
            <button type="button" data-question="Bagaimana cara menggunakan Upload Terpadu?">Upload Terpadu</button>
            <button type="button" data-question="Jelaskan status pada Crosscheck K3.2.">Crosscheck K3.2</button>
            <button type="button" data-question="Bagaimana cara menggunakan Draft Grouping dan mengubah jumlah PC?">Draft Grouping</button>
            <button type="button" data-question="Bagaimana cara sinkronisasi Errorlog Spreadsheet?">Errorlog</button>
        </div>

        <div id="simola-help-typing" class="sha-typing" hidden>
            <span></span><span></span><span></span>
            <em>Sedang mencari jawaban…</em>
        </div>

        <form id="simola-help-form" class="sha-form">
            <textarea
                id="simola-help-input"
                rows="1"
                maxlength="800"
                placeholder="Tulis pertanyaan tentang SIMOLA…"
                aria-label="Pertanyaan bantuan"
            ></textarea>
            <button
                type="submit"
                id="simola-help-send"
                aria-label="Kirim pertanyaan"
            >
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="m3.4 20.4 17.4-7.5c1.1-.5 1.1-1.3 0-1.7L3.4 3.6c-.9-.4-1.5.1-1.2 1l2.1 6.1 9.2 1.3-9.2 1.3-2.1 6.1c-.3.9.3 1.4 1.2 1Z"/>
                </svg>
            </button>
        </form>

        <footer class="sha-footer">
            <span>Jawaban bersifat bantuan read-only.</span>
            @can('users.access')
                <a href="{{ route('simola-help.admin.index') }}">Kelola Help Center</a>
            @endcan
        </footer>
    </section>
</div>

<style>
#simola-help-root{position:fixed;right:20px;bottom:20px;z-index:9998;font-family:inherit}
#simola-help-root *{box-sizing:border-box}
#simola-help-bubble{height:48px;display:flex;align-items:center;gap:8px;border:1px solid #c7d2fe;border-radius:999px;padding:0 16px;background:#1d4ed8;color:#fff;font:600 13px/1 inherit;box-shadow:0 14px 36px rgba(15,23,42,.2);cursor:pointer;transition:.18s ease}
#simola-help-bubble:hover{transform:translateY(-1px);background:#1e40af}
#simola-help-bubble svg{width:20px;height:20px;fill:currentColor}
#simola-help-panel{position:absolute;right:0;bottom:60px;width:min(370px,calc(100vw - 24px));height:min(560px,calc(100vh - 100px));display:none;grid-template-rows:auto 1fr auto auto auto auto;overflow:hidden;border:1px solid #dbe3ef;border-radius:18px;background:#fff;color:#0f172a;box-shadow:0 24px 70px rgba(15,23,42,.24)}
#simola-help-panel.sha-open{display:grid}
.sha-header{display:flex;align-items:center;justify-content:space-between;padding:14px 15px;border-bottom:1px solid #e5e7eb;background:linear-gradient(135deg,#172554,#1e3a8a);color:#fff}
.sha-brand{display:flex;align-items:center;gap:10px;min-width:0}
.sha-logo{width:34px;height:34px;display:grid;place-items:center;border-radius:11px;background:rgba(255,255,255,.14);font-size:17px}
.sha-brand strong{display:block;font-size:13px;line-height:1.25}
.sha-brand small{display:block;margin-top:2px;color:#bfdbfe;font-size:10px}
.sha-icon-button{width:32px;height:32px;border:0;border-radius:9px;background:transparent;color:#dbeafe;font:400 24px/1 inherit;cursor:pointer}
.sha-icon-button:hover{background:rgba(255,255,255,.1);color:#fff}
.sha-messages{overflow-y:auto;padding:14px;background:#f8fafc;scroll-behavior:smooth}
.sha-message{display:flex;flex-direction:column;margin:0 0 12px}
.sha-message-label{margin:0 5px 4px;color:#94a3b8;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.04em}
.sha-user{align-items:flex-end}
.sha-user .sha-message-label{text-align:right}
.sha-message-bubble{max-width:88%;white-space:pre-wrap;word-break:break-word;border:1px solid #e2e8f0;border-radius:13px 13px 13px 4px;padding:9px 11px;background:#fff;color:#334155;font-size:12px;line-height:1.55;box-shadow:0 2px 8px rgba(15,23,42,.04)}
.sha-user .sha-message-bubble{border-color:#2563eb;border-radius:13px 13px 4px 13px;background:#2563eb;color:#fff}
.sha-source{margin-top:5px;color:#94a3b8;font-size:9px}
.sha-quick{display:flex;gap:6px;overflow-x:auto;padding:9px 12px;border-top:1px solid #eef2f7;background:#fff}
.sha-quick button{flex:0 0 auto;border:1px solid #dbe3ef;border-radius:999px;padding:6px 9px;background:#f8fafc;color:#475569;font:600 10px/1.2 inherit;cursor:pointer}
.sha-quick button:hover{border-color:#93c5fd;background:#eff6ff;color:#1d4ed8}
.sha-typing{display:flex;align-items:center;gap:4px;padding:7px 14px;color:#64748b;font-size:10px;background:#fff}
.sha-typing span{width:5px;height:5px;border-radius:50%;background:#60a5fa;animation:sha-bounce 1.1s infinite ease-in-out}
.sha-typing span:nth-child(2){animation-delay:.15s}.sha-typing span:nth-child(3){animation-delay:.3s}
.sha-typing em{margin-left:4px;font-style:normal}
@keyframes sha-bounce{0%,60%,100%{transform:translateY(0);opacity:.45}30%{transform:translateY(-3px);opacity:1}}
.sha-form{display:grid;grid-template-columns:1fr 38px;gap:7px;align-items:end;padding:10px 12px;border-top:1px solid #e5e7eb;background:#fff}
.sha-form textarea{width:100%;min-height:38px;max-height:92px;resize:none;overflow-y:auto;border:1px solid #cbd5e1!important;border-radius:11px!important;padding:9px 10px!important;background:#fff!important;color:#0f172a!important;font:400 12px/1.4 inherit!important;box-shadow:none!important}
.sha-form textarea:focus{outline:none!important;border-color:#60a5fa!important;box-shadow:0 0 0 3px rgba(59,130,246,.12)!important}
.sha-form button{width:38px;height:38px;display:grid;place-items:center;border:0;border-radius:11px;background:#2563eb;color:#fff;cursor:pointer}
.sha-form button:disabled{opacity:.5;cursor:not-allowed}
.sha-form button svg{width:17px;height:17px;fill:currentColor}
.sha-footer{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:7px 12px;border-top:1px solid #eef2f7;background:#fff;color:#94a3b8;font-size:9px}
.sha-footer a{color:#2563eb;text-decoration:none;font-weight:600}
html.dark #simola-help-panel{border-color:#334155;background:#111827;color:#f8fafc;box-shadow:0 26px 75px rgba(2,6,23,.55)}
html.dark .sha-header{border-color:#334155;background:linear-gradient(135deg,#0f172a,#172554)}
html.dark .sha-messages{background:#0f172a}
html.dark .sha-message-bubble{border-color:#334155;background:#172033;color:#dbe5f1}
html.dark .sha-user .sha-message-bubble{border-color:#3b82f6;background:#1d4ed8;color:#fff}
html.dark .sha-quick,html.dark .sha-typing,html.dark .sha-form,html.dark .sha-footer{border-color:#27364c;background:#111827}
html.dark .sha-quick button{border-color:#334155;background:#172033;color:#cbd5e1}
html.dark .sha-quick button:hover{border-color:#60a5fa;background:#1e293b;color:#bfdbfe}
html.dark .sha-form textarea{border-color:#334155!important;background:#0f172a!important;color:#f8fafc!important}
html.dark .sha-form textarea::placeholder{color:#64748b!important}
html.dark .sha-footer a{color:#93c5fd}
@media(max-width:520px){#simola-help-root{right:12px;bottom:12px}#simola-help-panel{position:fixed;right:12px;bottom:70px;left:12px;width:auto;height:min(620px,calc(100vh - 90px))}#simola-help-bubble span{display:none}#simola-help-bubble{width:48px;padding:0;justify-content:center}}

/* === SIMOLA AI REPLY NOTIFICATION v1.6 CSS START === */
.sha-header-actions{
    display:flex;
    align-items:center;
    gap:4px;
}
#simola-help-sound-toggle{
    width:32px;
    height:32px;
    display:grid;
    place-items:center;
    border:0;
    border-radius:9px;
    background:transparent;
    color:#dbeafe;
    font:600 15px/1 inherit;
    cursor:pointer;
    transition:background .16s ease,transform .16s ease,color .16s ease;
}
#simola-help-sound-toggle:hover{
    background:rgba(255,255,255,.1);
    color:#fff;
    transform:translateY(-1px);
}
#simola-help-sound-toggle.sha-muted{
    opacity:.6;
}
.sha-ai-reply-new .sha-message-bubble{
    animation:sha-ai-reply-pop .58s cubic-bezier(.2,.8,.2,1);
}
#simola-help-bubble.sha-ai-notify{
    animation:sha-help-bubble-notify .72s cubic-bezier(.2,.8,.2,1) 2;
}
#simola-help-bubble.sha-ai-notify::after{
    content:'';
    position:absolute;
    top:-2px;
    right:-2px;
    width:10px;
    height:10px;
    border-radius:999px;
    background:#22c55e;
    border:2px solid #fff;
    box-shadow:0 0 0 4px rgba(34,197,94,.16);
}
html.dark #simola-help-bubble.sha-ai-notify::after{
    border-color:#0f172a;
}
.sha-ai-reply-new .sha-message-label::after{
    content:'';
    display:inline-block;
    width:5px;
    height:5px;
    margin-left:5px;
    vertical-align:middle;
    border-radius:999px;
    background:#22c55e;
    box-shadow:0 0 0 3px rgba(34,197,94,.13);
    animation:sha-ai-status-dot 1.15s ease-out;
}
@keyframes sha-ai-reply-pop{
    0%{
        opacity:.35;
        transform:translateY(8px) scale(.985);
        box-shadow:0 0 0 rgba(37,99,235,0);
    }
    55%{
        opacity:1;
        transform:translateY(-1px) scale(1.006);
        box-shadow:0 8px 24px rgba(37,99,235,.12);
    }
    100%{
        opacity:1;
        transform:none;
        box-shadow:0 2px 8px rgba(15,23,42,.04);
    }
}
@keyframes sha-help-bubble-notify{
    0%,100%{transform:translateY(0) scale(1)}
    35%{transform:translateY(-4px) scale(1.045)}
    65%{transform:translateY(0) scale(.99)}
}
@keyframes sha-ai-status-dot{
    0%{transform:scale(.4);opacity:.2}
    35%{transform:scale(1.45);opacity:1}
    100%{transform:scale(1);opacity:1}
}
@media (prefers-reduced-motion: reduce){
    .sha-ai-reply-new .sha-message-bubble,
    #simola-help-bubble.sha-ai-notify,
    .sha-ai-reply-new .sha-message-label::after{
        animation:none!important;
    }
}
/* === SIMOLA AI REPLY NOTIFICATION v1.6 CSS END === */
</style>

<script>
(() => {
    if (window.__simolaHelpAssistantBooted) return;
    window.__simolaHelpAssistantBooted = true;

    const root = document.getElementById('simola-help-root');
    if (!root) return;

    const endpoint = root.dataset.endpoint;
    const panel = document.getElementById('simola-help-panel');
    const bubble = document.getElementById('simola-help-bubble');
    const close = document.getElementById('simola-help-close');
    const form = document.getElementById('simola-help-form');
    const input = document.getElementById('simola-help-input');
    const send = document.getElementById('simola-help-send');
    const messages = document.getElementById('simola-help-messages');
    const typing = document.getElementById('simola-help-typing');
    const quick = document.getElementById('simola-help-quick');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

/* === SIMOLA AI REPLY NOTIFICATION v1.6 JS START === */
    const soundToggle = document.getElementById('simola-help-sound-toggle');
    const soundStorageKey = 'simola-help-sound-enabled-v1';
    let helpAudioContext = null;
    let soundEnabled = true;

    try {
        const savedSound = localStorage.getItem(soundStorageKey);
        soundEnabled = savedSound === null ? true : savedSound === '1';
    } catch (_) {
        soundEnabled = true;
    }

    function updateSoundToggle() {
        if (!soundToggle) return;

        soundToggle.textContent = soundEnabled ? '🔔' : '🔕';
        soundToggle.classList.toggle('sha-muted', !soundEnabled);
        soundToggle.setAttribute(
            'aria-label',
            soundEnabled
                ? 'Matikan suara notifikasi AI'
                : 'Aktifkan suara notifikasi AI'
        );
        soundToggle.setAttribute(
            'title',
            soundEnabled
                ? 'Suara balasan AI aktif'
                : 'Suara balasan AI nonaktif'
        );
    }

    function armNotificationAudio() {
        if (!soundEnabled) return null;

        try {
            const AudioContext =
                window.AudioContext
                || window.webkitAudioContext;

            if (!AudioContext) return null;

            if (!helpAudioContext) {
                helpAudioContext = new AudioContext();
            }

            if (helpAudioContext.state === 'suspended') {
                helpAudioContext.resume().catch(() => {});
            }

            return helpAudioContext;
        } catch (_) {
            return null;
        }
    }

    function playAiReplySound() {
        if (!soundEnabled) return;

        const ctx = armNotificationAudio();
        if (!ctx || ctx.state === 'closed') return;

        try {
            const now = ctx.currentTime;
            const master = ctx.createGain();
            master.connect(ctx.destination);

            master.gain.setValueAtTime(0.0001, now);
            master.gain.exponentialRampToValueAtTime(0.075, now + 0.018);
            master.gain.exponentialRampToValueAtTime(0.0001, now + 0.34);

            const first = ctx.createOscillator();
            first.type = 'sine';
            first.frequency.setValueAtTime(740, now);
            first.frequency.exponentialRampToValueAtTime(880, now + 0.13);
            first.connect(master);
            first.start(now);
            first.stop(now + 0.18);

            const second = ctx.createOscillator();
            const secondGain = ctx.createGain();
            second.type = 'sine';
            second.frequency.setValueAtTime(1046, now + 0.12);
            secondGain.gain.setValueAtTime(0.0001, now + 0.1);
            secondGain.gain.exponentialRampToValueAtTime(0.48, now + 0.14);
            secondGain.gain.exponentialRampToValueAtTime(0.0001, now + 0.31);
            second.connect(secondGain);
            secondGain.connect(master);
            second.start(now + 0.1);
            second.stop(now + 0.32);
        } catch (_) {}
    }

    function animateAiReply() {
        const latestMessage = messages?.lastElementChild;

        if (latestMessage) {
            latestMessage.classList.add('sha-ai-reply-new');

            window.setTimeout(() => {
                latestMessage.classList.remove('sha-ai-reply-new');
            }, 1500);
        }

        if (!panel.classList.contains('sha-open')) {
            bubble.classList.add('sha-ai-notify');

            window.setTimeout(() => {
                bubble.classList.remove('sha-ai-notify');
            }, 2400);
        }
    }

    function notifyAiReply() {
        playAiReplySound();
        animateAiReply();

        if (
            navigator.vibrate
            && document.visibilityState !== 'visible'
        ) {
            try {
                navigator.vibrate(35);
            } catch (_) {}
        }
    }

    if (soundToggle) {
        updateSoundToggle();

        soundToggle.addEventListener('click', () => {
            soundEnabled = !soundEnabled;

            try {
                localStorage.setItem(
                    soundStorageKey,
                    soundEnabled ? '1' : '0'
                );
            } catch (_) {}

            updateSoundToggle();

            if (soundEnabled) {
                armNotificationAudio();

                window.setTimeout(() => {
                    playAiReplySound();
                }, 40);
            }
        });
    }

    bubble.addEventListener(
        'pointerdown',
        armNotificationAudio,
        {passive:true}
    );

    form.addEventListener(
        'pointerdown',
        armNotificationAudio,
        {passive:true}
    );

    quick.addEventListener(
        'pointerdown',
        armNotificationAudio,
        {passive:true}
    );
/* === SIMOLA AI REPLY NOTIFICATION v1.6 JS END === */
    const sessionKey = 'simola-help-chat-v15';

    let history = [];
    let busy = false;

    const save = () => {
        try {
            sessionStorage.setItem(sessionKey, JSON.stringify(history.slice(-20)));
        } catch (_) {}
    };

    const restore = () => {
        try {
            const parsed = JSON.parse(sessionStorage.getItem(sessionKey) || '[]');
            if (!Array.isArray(parsed)) return;
            history = parsed.filter(m =>
                m && ['user','assistant'].includes(m.role) && typeof m.content === 'string'
            ).slice(-20);
            history.forEach(m => appendMessage(m.role, m.content, m.source_label || null, false));
        } catch (_) {}
    };

    function appendMessage(role, content, sourceLabel = null, persist = true) {
        const wrap = document.createElement('div');
        wrap.className = 'sha-message ' + (role === 'user' ? 'sha-user' : 'sha-assistant');

        const label = document.createElement('div');
        label.className = 'sha-message-label';
        label.textContent = role === 'user' ? 'Anda' : 'SIMOLA';

        const text = document.createElement('div');
        text.className = 'sha-message-bubble';
        text.textContent = content;

        wrap.append(label, text);

        if (sourceLabel && role === 'assistant') {
            const source = document.createElement('div');
            source.className = 'sha-source';
            source.textContent = sourceLabel;
            wrap.appendChild(source);
        }

        messages.appendChild(wrap);
        messages.scrollTop = messages.scrollHeight;

        if (persist) {
            history.push({
                role,
                content,
                source_label: sourceLabel || undefined
            });
            history = history.slice(-20);
            save();
        }
    }

    const setOpen = (open) => {
        panel.classList.toggle('sha-open', open);
        panel.setAttribute('aria-hidden', open ? 'false' : 'true');
        bubble.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) {
            setTimeout(() => input.focus(), 80);
            messages.scrollTop = messages.scrollHeight;
        }
    };

    const setBusy = (value) => {
        busy = value;
        typing.hidden = !value;
        send.disabled = value;
        input.disabled = value;
        if (value) messages.scrollTop = messages.scrollHeight;
    };

    async function ask(question) {
        const clean = String(question || '').trim();
        if (!clean || busy) return;

        const historyForRequest = history
            .slice(-16)
            .map(({role, content}) => ({role, content}));

        appendMessage('user', clean);
        input.value = '';
        input.style.height = 'auto';
        setBusy(true);

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    question: clean,
                    page_title: document.title,
                    page_url: location.pathname + location.search,
                    history: historyForRequest
                })
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                const validation = data?.errors
                    ? Object.values(data.errors).flat().join(' ')
                    : null;

                throw new Error(validation || data?.message || 'Help Center gagal merespons.');
            }

            appendMessage(
                'assistant',
                data.answer || 'Belum ada jawaban.',
                data.source_label || 'Help Center'
            );

            if (
                data.source === 'ai'
                || data.source === 'conversation'
                || String(data.source_label || '').toLowerCase().includes('ai')
            ) {
                notifyAiReply();
            }
        } catch (error) {
            appendMessage(
                'assistant',
                'Maaf, bantuan otomatis sedang tidak tersedia. Coba lagi sebentar atau hubungi Developer/Admin SIMOLA.',
                'Help Center'
            );
            console.error('SIMOLA Help:', error);
        } finally {
            setBusy(false);
            input.focus();
        }
    }

    bubble.addEventListener('click', () => setOpen(!panel.classList.contains('sha-open')));
    close.addEventListener('click', () => setOpen(false));

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && panel.classList.contains('sha-open')) {
            setOpen(false);
        }
    });

    quick.addEventListener('click', event => {
        const button = event.target.closest('[data-question]');
        if (!button) return;
        ask(button.dataset.question);
    });

    form.addEventListener('submit', event => {
        event.preventDefault();
        ask(input.value);
    });

    input.addEventListener('keydown', event => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            form.requestSubmit();
        }
    });

    input.addEventListener('input', () => {
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 92) + 'px';
    });

    restore();
})();
</script>
@endif
