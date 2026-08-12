<x-app-layout>
<x-slot name="header">
<div style="max-width:1050px;margin:auto">
    <a href="{{ route('operator-chat.supervisor.index') }}" style="font-size:9px;color:#2563eb">← Chat Operator</a>
    <h2 style="font-weight:800;margin-top:4px">Kelola Perangkat Operator</h2>
    <p style="font-size:9px;color:#94a3b8">
        Satu nomor PC hanya dapat aktif pada satu browser/perangkat sampai akses dilepas.
    </p>
</div>
</x-slot>

<style>
.dw{max-width:1050px;margin:auto;padding:18px}.notice{padding:10px 12px;border:1px solid #bfdbfe;background:#eff6ff;color:#1e40af;border-radius:10px;font-size:9px;margin-bottom:11px}.dark .notice{background:#102442;color:#bfdbfe;border-color:#294a78}.err{border-color:#fecaca;background:#fef2f2;color:#b91c1c}.dark .err{background:#3b1720;color:#fecaca;border-color:#7f1d1d}.setup{display:grid;grid-template-columns:160px 100px 1fr auto;gap:8px;padding:11px;margin-bottom:12px;border:1px solid #dbe3ed;border-radius:13px;background:#fff}.dark .setup{background:#101c30;border-color:#2d405d}.setup input,.setup select{width:100%;padding:8px;border:1px solid #cbd5e1;border-radius:8px;background:transparent;color:inherit;font-size:9px}.btn{border:0;border-radius:8px;background:#2563eb;color:#fff;padding:8px 10px;font-size:8px;font-weight:900;cursor:pointer}.secondary{background:#f59e0b}.danger{background:#ef4444}.list{border:1px solid #dbe3ed;border-radius:13px;overflow:hidden;background:#fff}.dark .list{background:#101c30;border-color:#2d405d}.row{display:grid;grid-template-columns:72px 1fr 145px 145px auto;gap:10px;align-items:center;padding:11px;border-bottom:1px solid #e8edf4}.dark .row{border-color:#263853}.row:last-child{border-bottom:0}.pc{font-size:10px;font-weight:900}.meta{font-size:8px;color:#64748b}.dark .meta{color:#94a3b8}.status{display:inline-flex;border-radius:999px;padding:4px 7px;font-size:7px;font-weight:900}.active{background:#dcfce7;color:#166534}.idle{background:#f1f5f9;color:#64748b}.code{background:#fef3c7;color:#92400e}.dark .idle{background:#243247;color:#cbd5e1}.actions{display:flex;gap:5px;justify-content:flex-end}.codebox{font-size:11px;font-weight:950;letter-spacing:.12em}.small{font-size:7px;color:#94a3b8}
@media(max-width:800px){.setup{grid-template-columns:1fr 1fr}.setup input{grid-column:1/-1}.row{grid-template-columns:70px 1fr}.row>*:nth-child(n+3){grid-column:2}.actions{justify-content:flex-start}}
</style>

<div class="dw">
    <div class="notice">
        Operator tidak memakai username/password. Siapkan PC, buat kode aktivasi 6 digit,
        lalu masukkan kode tersebut sekali pada browser operator di <b>/operator</b>.
    </div>

    @if(session('success'))<div class="notice">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="notice err">{{ session('error') }}</div>@endif

    <form class="setup" method="POST" action="{{ route('operator-chat.devices.store') }}">
        @csrf
        <select name="fleet_type" required>
            <option value="MT_LPG">MT LPG</option>
            <option value="MT_PERTASHOP">MT PERTASHOP</option>
        </select>
        <input type="number" name="pc_number" min="1" max="99" value="1" required>
        <input type="text" name="label" maxlength="100" placeholder="Label opsional, mis. Ruang Operator 1">
        <button class="btn" type="submit">Siapkan PC</button>
    </form>

    <div class="list">
        @forelse($devices as $device)
        <div class="row">
            <div class="pc">PC {{ $device->pc_number }}</div>

            <div>
                <b style="font-size:9px">{{ $device->displayFleetType() }}</b>
                <div class="meta">{{ $device->label ?: 'Perangkat Operator' }}</div>
            </div>

            <div>
                @if($device->is_active)
                    <span class="status active">AKTIF</span>
                    <div class="small">
                        {{ $device->last_seen_at ? 'Terlihat '.$device->last_seen_at->format('d-m H:i') : 'Belum ada heartbeat' }}
                    </div>
                @elseif($device->activation_code && $device->activation_expires_at?->isFuture())
                    <span class="status code">KODE AKTIVASI</span>
                    <div class="codebox">{{ $device->activation_code }}</div>
                    <div class="small">sampai {{ $device->activation_expires_at->format('H:i') }}</div>
                @else
                    <span class="status idle">TERSEDIA</span>
                @endif
            </div>

            <div class="meta">
                @if($device->activated_at)
                    Aktivasi terakhir<br>{{ $device->activated_at->format('d-m-Y H:i') }}
                @else
                    Belum pernah diaktifkan
                @endif
            </div>

            <div class="actions">
                @if(!$device->is_active)
                <form method="POST" action="{{ route('operator-chat.devices.code', $device) }}">
                    @csrf
                    <button class="btn secondary" type="submit">Buat Kode</button>
                </form>
                @else
                <form method="POST" action="{{ route('operator-chat.devices.release', $device) }}" onsubmit="return confirm('Lepas akses {{ $device->displayName() }} dari perangkat saat ini?')">
                    @csrf
                    @method('PATCH')
                    <button class="btn danger" type="submit">Lepas Akses</button>
                </form>
                @endif
            </div>
        </div>
        @empty
        <div style="padding:35px;text-align:center;color:#94a3b8;font-size:9px">
            Belum ada PC. Gunakan form di atas.
        </div>
        @endforelse
    </div>
</div>
</x-app-layout>
