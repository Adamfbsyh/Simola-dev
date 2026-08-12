<x-app-layout>
<x-slot name="header">
<div style="max-width:1050px;margin:auto">
    <a href="{{ route('operator-chat.supervisor.index') }}" style="font-size:9px;color:#2563eb">← Chat Operator</a>
    <h2 style="font-weight:800;margin-top:4px">Approval Transfer Catatan</h2>
    <p style="font-size:9px;color:#94a3b8">
        Hanya sticky note yang dipilih operator yang muncul di sini.
    </p>
</div>
</x-slot>

<style>
.tw{max-width:1050px;margin:auto;padding:18px}.notice{padding:10px 12px;border:1px solid #bfdbfe;background:#eff6ff;color:#1e40af;border-radius:10px;font-size:9px;margin-bottom:11px}.dark .notice{background:#102442;color:#bfdbfe;border-color:#294a78}.err{border-color:#fecaca;background:#fef2f2;color:#b91c1c}.card{margin-bottom:11px;border:1px solid #dbe3ed;border-radius:14px;background:#fff;overflow:hidden}.dark .card{background:#101c30;border-color:#2d405d}.head{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:11px 12px;border-bottom:1px solid #e8edf4}.dark .head{border-color:#263853}.route{font-size:10px;font-weight:900}.meta{font-size:8px;color:#64748b}.dark .meta{color:#94a3b8}.state{display:inline-flex;border-radius:999px;padding:4px 7px;font-size:7px;font-weight:900}.pending{background:#fef3c7;color:#92400e}.approved{background:#dcfce7;color:#166534}.rejected{background:#fee2e2;color:#b91c1c}.items{padding:10px 12px}.item{display:flex;align-items:flex-start;gap:8px;margin-bottom:7px;padding:9px;border:1px solid #e8edf4;border-radius:10px;background:#f8fafc}.dark .item{background:#172640;border-color:#304562}.item input{margin-top:2px;accent-color:#2563eb}.body{white-space:pre-wrap;font-size:9px;line-height:1.5}.actions{display:flex;gap:6px;justify-content:flex-end;padding:0 12px 11px}.btn{border:0;border-radius:8px;padding:8px 10px;font-size:8px;font-weight:900;cursor:pointer}.ok{background:#2563eb;color:#fff}.no{background:#ef4444;color:#fff}.empty{padding:40px;text-align:center;color:#94a3b8;font-size:9px}
</style>

<div class="tw">
    @if(session('success'))<div class="notice">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="notice err">{{ session('error') }}</div>@endif

    @forelse($transfers as $transfer)
    <section class="card">
        <div class="head">
            <div>
                <div class="route">
                    {{ $transfer->sourceDevice?->displayName() ?? 'PC sumber' }}
                    →
                    {{ $transfer->targetDevice?->displayName() ?? 'PC tujuan' }}
                </div>
                <div class="meta">
                    {{ $transfer->requested_at?->format('d-m-Y H:i') }}
                    · {{ $transfer->items->count() }} catatan diajukan
                    @if($transfer->reviewer)
                        · diproses {{ $transfer->reviewer->name }}
                    @endif
                </div>
            </div>
            <span class="state {{ $transfer->status }}">
                {{ strtoupper($transfer->status) }}
            </span>
        </div>

        @if($transfer->status === 'pending')
        <form method="POST" action="{{ route('operator-chat.transfers.approve', $transfer) }}">
            @csrf
            @method('PATCH')

            <div class="items">
                @foreach($transfer->items as $item)
                <label class="item">
                    <input
                        type="checkbox"
                        name="approved_items[]"
                        value="{{ $item->id }}"
                        checked
                    >
                    <span class="body">{{ $item->snapshot_body }}</span>
                </label>
                @endforeach
            </div>

            <div class="actions">
                <button
                    class="btn no"
                    type="submit"
                    form="reject-{{ $transfer->id }}"
                >
                    Tolak Semua
                </button>
                <button class="btn ok" type="submit">
                    Setujui yang Dicentang
                </button>
            </div>
        </form>

        <form
            id="reject-{{ $transfer->id }}"
            method="POST"
            action="{{ route('operator-chat.transfers.reject', $transfer) }}"
        >
            @csrf
            @method('PATCH')
        </form>
        @else
        <div class="items">
            @foreach($transfer->items as $item)
            <div class="item" style="{{ $item->is_approved ? '' : 'opacity:.55' }}">
                <span class="body">
                    {{ $item->is_approved ? '✓ ' : '× ' }}{{ $item->snapshot_body }}
                </span>
            </div>
            @endforeach
        </div>
        @endif
    </section>
    @empty
    <div class="card empty">Belum ada permintaan transfer catatan.</div>
    @endforelse
</div>
</x-app-layout>
