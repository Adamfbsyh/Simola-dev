<x-app-layout>
<x-slot name="header">
<div style="max-width:1100px;margin:auto;display:flex;justify-content:space-between;gap:12px;align-items:center">
    <div>
        <h2 style="font-weight:800">Chat Operator PC</h2>
        <p style="font-size:10px;color:#94a3b8">
            Device Mode: operator tidak perlu login.
        </p>
    </div>
    <div style="display:flex;gap:7px">
        <a href="{{ route('operator-chat.transfers.index') }}" style="padding:9px 11px;border-radius:9px;background:#f59e0b;color:#fff;font-size:9px;font-weight:900;text-decoration:none">
            Approval Catatan
            @if($pendingTransfers)
                ({{ $pendingTransfers }})
            @endif
        </a>
        <a href="{{ route('operator-chat.devices.index') }}" style="padding:9px 11px;border-radius:9px;background:#2563eb;color:#fff;font-size:9px;font-weight:900;text-decoration:none">
            Kelola Perangkat
        </a>
    </div>
</div>
</x-slot>

<style>
.oc{max-width:1100px;margin:auto;padding:18px 16px}.toprow{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:11px}.flt select{padding:8px 10px;border:1px solid #cbd5e1;border-radius:9px;background:transparent;color:inherit;font-size:9px}.hint{font-size:9px;color:#64748b}.dark .hint{color:#94a3b8}.list{border:1px solid #dbe3ed;border-radius:14px;overflow:hidden;background:#fff}.dark .list{background:#101c30;border-color:#2d405d}.item{display:grid;grid-template-columns:58px 1fr auto;gap:12px;padding:13px;border-bottom:1px solid #e8edf4;text-decoration:none;color:inherit}.dark .item{border-color:#263853}.item:last-child{border-bottom:0}.pc{width:50px;height:50px;border-radius:13px;display:grid;place-items:center;background:#eaf2ff;color:#1d4ed8;font-size:10px;font-weight:900}.dark .pc{background:#192b4a;color:#93c5fd}.ttl{font-size:11px;font-weight:800}.sub,.prev,.time{font-size:9px;color:#64748b}.dark .sub,.dark .prev,.dark .time{color:#94a3b8}.prev{margin-top:5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:720px}.badge{display:inline-flex;margin-top:5px;border-radius:999px;padding:4px 7px;font-size:7px;font-weight:900}.unread{background:#2563eb;color:#fff}.online{background:#dcfce7;color:#166534}.offline{background:#f1f5f9;color:#64748b}.dark .offline{background:#243247;color:#cbd5e1}.empty{padding:40px;text-align:center;color:#94a3b8;font-size:10px}
</style>

<div class="oc">
    <div class="toprow">
        <form class="flt">
            <select name="fleet_type" onchange="this.form.submit()">
                <option value="">Semua Armada</option>
                <option value="MT_LPG" @selected($fleet === 'MT_LPG')>MT LPG</option>
                <option value="MT_PERTASHOP" @selected($fleet === 'MT_PERTASHOP')>MT PERTASHOP</option>
            </select>
        </form>
        <span class="hint">Identitas chat mengikuti nomor PC, bukan akun operator.</span>
    </div>

    <div class="list">
        @forelse($devices as $device)
            @php($key = $device->fleet_type.'|'.$device->pc_number)
            @php($thread = $threadMap->get($key))
            @php($last = $thread ? $latest->get($thread->id) : null)

            @if($thread)
            <a class="item" href="{{ route('operator-chat.supervisor.show', $thread) }}">
                <div class="pc">PC {{ $device->pc_number }}</div>
                <div>
                    <div class="ttl">{{ $device->displayName() }}</div>
                    <div class="sub">
                        {{ $device->label ?: 'Perangkat Operator' }}
                        ·
                        {{ $device->is_active ? 'Terikat ke perangkat' : 'Belum aktif' }}
                    </div>
                    <div class="prev">
                        @if($last)
                            {{ $last->sender_type === 'operator' ? 'PC '.$device->pc_number : ($last->sender?->name ?? 'Pengawas') }}:
                            {{ $last->body }}
                        @else
                            Belum ada percakapan.
                        @endif
                    </div>
                </div>
                <div style="text-align:right">
                    <div class="time">{{ $thread->last_message_at?->format('d-m H:i') ?? '-' }}</div>
                    @if($thread->unread_count)
                        <span class="badge unread">{{ $thread->unread_count }} baru</span>
                    @elseif($device->is_active)
                        <span class="badge online">Aktif</span>
                    @else
                        <span class="badge offline">Tidak aktif</span>
                    @endif
                </div>
            </a>
            @endif
        @empty
            <div class="empty">
                Belum ada PC Operator yang disiapkan.<br>
                Buka <b>Kelola Perangkat</b> untuk membuat PC 1, PC 2, dan seterusnya.
            </div>
        @endforelse
    </div>
</div>
</x-app-layout>
