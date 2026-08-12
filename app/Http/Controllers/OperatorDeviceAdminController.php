<?php

namespace App\Http\Controllers;

use App\Models\OperatorChatMessage;
use App\Models\OperatorChatThread;
use App\Models\OperatorDevice;
use App\Models\OperatorDeviceNote;
use App\Models\OperatorNoteTransferRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OperatorDeviceAdminController extends Controller
{
    public function index(Request $request): View
    {
        $fleet = trim(
            (string) $request->input(
                'fleet_type',
                ''
            )
        );

        $devices = OperatorDevice::query()
            ->when(
                $fleet !== '',
                fn ($query) =>
                    $query->where(
                        'fleet_type',
                        $fleet
                    )
            )
            ->orderBy('fleet_type')
            ->orderBy('pc_number')
            ->get();

        $threadMap = OperatorChatThread::query()
            ->withCount([
                'messages as unread_count' =>
                    fn ($query) =>
                        $query
                            ->where(
                                'sender_type',
                                'operator'
                            )
                            ->whereNull(
                                'read_at'
                            ),
            ])
            ->get()
            ->keyBy(
                fn (
                    OperatorChatThread $thread
                ) =>
                    $thread->fleet_type
                    . '|'
                    . $thread->pc_number
            );

        $threadIds = $threadMap
            ->pluck('id')
            ->filter();

        $latest = OperatorChatMessage::query()
            ->with('sender:id,name')
            ->whereIn(
                'thread_id',
                $threadIds
            )
            ->orderByDesc('id')
            ->get()
            ->unique('thread_id')
            ->keyBy('thread_id');

        $pendingTransfers =
            OperatorNoteTransferRequest::query()
                ->where('status', 'pending')
                ->count();

        return view(
            'operator-chat.supervisor-index',
            compact(
                'devices',
                'threadMap',
                'latest',
                'fleet',
                'pendingTransfers'
            )
        );
    }

    public function show(
        OperatorChatThread $thread
    ): View {
        $this->markRead(
            $thread,
            'operator'
        );

        $messages = $this->messagesFor(
            $thread
        );

        $device = OperatorDevice::query()
            ->where(
                'fleet_type',
                $thread->fleet_type
            )
            ->where(
                'pc_number',
                $thread->pc_number
            )
            ->first();

        return view(
            'operator-chat.supervisor-show',
            compact(
                'thread',
                'messages',
                'device'
            )
        );
    }

    public function messages(
        OperatorChatThread $thread
    ): JsonResponse {
        $this->markRead(
            $thread,
            'operator'
        );

        return response()->json([
            'messages' =>
                $this->serialize(
                    $this->messagesFor(
                        $thread
                    ),
                    $thread
                ),
            'status' =>
                $thread
                    ->fresh()
                    ->status,
        ]);
    }

    public function send(
        Request $request,
        OperatorChatThread $thread
    ): JsonResponse {
        $validated = $request->validate([
            'body' => [
                'required',
                'string',
                'max:2000',
            ],
        ]);

        $message = DB::transaction(
            function () use (
                $request,
                $thread,
                $validated
            ) {
                $message =
                    OperatorChatMessage::query()
                        ->create([
                            'thread_id' =>
                                $thread->id,
                            'sender_user_id' =>
                                $request->user()->id,
                            'sender_type' =>
                                'supervisor',
                            'body' =>
                                trim(
                                    $validated['body']
                                ),
                        ]);

                $thread->forceFill([
                    'status' => 'open',
                    'last_message_at' =>
                        $message->created_at,
                    'last_message_user_id' =>
                        $request->user()->id,
                ])->save();

                return $message;
            }
        );

        return response()->json([
            'ok' => true,
            'message_id' => $message->id,
        ]);
    }

    public function resolve(
        OperatorChatThread $thread
    ): RedirectResponse {
        $thread->forceFill([
            'status' =>
                $thread->status
                === 'resolved'
                    ? 'open'
                    : 'resolved',
        ])->save();

        return back()->with(
            'success',
            'Status percakapan diperbarui.'
        );
    }

    public function unread(): JsonResponse
    {
        return response()->json([
            'count' =>
                OperatorChatMessage::query()
                    ->where(
                        'sender_type',
                        'operator'
                    )
                    ->whereNull('read_at')
                    ->count(),
        ]);
    }

    public function devices(): View
    {
        $devices = OperatorDevice::query()
            ->orderBy('fleet_type')
            ->orderBy('pc_number')
            ->get();

        return view(
            'operator-chat.devices',
            compact('devices')
        );
    }

    public function deviceStore(
        Request $request
    ): RedirectResponse {
        $validated = $request->validate([
            'fleet_type' => [
                'required',
                Rule::in([
                    'MT_LPG',
                    'MT_PERTASHOP',
                ]),
            ],
            'pc_number' => [
                'required',
                'integer',
                'min:1',
                'max:99',
            ],
            'label' => [
                'nullable',
                'string',
                'max:100',
            ],
        ]);

        $device = OperatorDevice::query()
            ->firstOrNew([
                'fleet_type' =>
                    $validated['fleet_type'],
                'pc_number' =>
                    $validated['pc_number'],
            ]);

        $device->label = filled(
            $validated['label']
            ?? null
        )
            ? trim(
                $validated['label']
            )
            : null;

        $device->save();

        OperatorChatThread::query()
            ->firstOrCreate(
                [
                    'fleet_type' =>
                        $device->fleet_type,
                    'pc_number' =>
                        $device->pc_number,
                ],
                [
                    'status' => 'open',
                ]
            );

        return back()->with(
            'success',
            'PC operator berhasil disiapkan.'
        );
    }

    public function activationCode(
        OperatorDevice $device
    ): RedirectResponse {
        if ($device->is_active) {
            return back()->with(
                'error',
                $device->displayName()
                . ' masih terikat ke perangkat. Lepas akses terlebih dahulu.'
            );
        }

        $code = (string) random_int(
            100000,
            999999
        );

        $device->forceFill([
            'activation_code' => $code,
            'activation_expires_at' =>
                now()->addMinutes(15),
        ])->save();

        return back()->with(
            'success',
            'Kode aktivasi '
            . $device->displayName()
            . ' dibuat: '
            . $code
        );
    }

    public function release(
        OperatorDevice $device
    ): RedirectResponse {
        $name = $device->displayName();

        $device->forceFill([
            'device_token_hash' => null,
            'activation_code' => null,
            'activation_expires_at' => null,
            'is_active' => false,
            'released_at' => now(),
        ])->save();

        return back()->with(
            'success',
            'Akses '
            . $name
            . ' sudah dilepas. PC dapat diaktifkan pada perangkat lain.'
        );
    }

    public function transfers(): View
    {
        $transfers =
            OperatorNoteTransferRequest::query()
                ->with([
                    'sourceDevice',
                    'targetDevice',
                    'reviewer:id,name',
                    'items',
                ])
                ->orderByRaw(
                    "CASE WHEN status = 'pending' THEN 0 ELSE 1 END"
                )
                ->orderByDesc('requested_at')
                ->limit(100)
                ->get();

        return view(
            'operator-chat.transfers',
            compact('transfers')
        );
    }

    public function approve(
        Request $request,
        OperatorNoteTransferRequest $transfer
    ): RedirectResponse {
        if ($transfer->status !== 'pending') {
            return back()->with(
                'error',
                'Permintaan ini sudah diproses.'
            );
        }

        $validated = $request->validate([
            'approved_items' => [
                'required',
                'array',
                'min:1',
            ],
            'approved_items.*' => [
                'required',
                'integer',
            ],
        ]);

        $items = $transfer
            ->items()
            ->whereIn(
                'id',
                $validated['approved_items']
            )
            ->get();

        if ($items->isEmpty()) {
            return back()->with(
                'error',
                'Pilih minimal satu catatan.'
            );
        }

        DB::transaction(
            function () use (
                $request,
                $transfer,
                $items
            ) {
                $transfer
                    ->items()
                    ->update([
                        'is_approved' => false,
                    ]);

                foreach ($items as $item) {
                    $item->forceFill([
                        'is_approved' => true,
                    ])->save();

                    OperatorDeviceNote::query()
                        ->create([
                            'device_id' =>
                                $transfer
                                    ->target_device_id,
                            'body' =>
                                $item->snapshot_body,
                            'source_note_id' =>
                                $item->source_note_id,
                            'source_device_id' =>
                                $transfer
                                    ->source_device_id,
                            'delivered_from_transfer_id' =>
                                $transfer->id,
                        ]);
                }

                $transfer->forceFill([
                    'status' => 'approved',
                    'reviewed_by_user_id' =>
                        $request->user()->id,
                    'reviewed_at' => now(),
                ])->save();
            }
        );

        return back()->with(
            'success',
            $items->count()
            . ' catatan disetujui dan dikirim ke '
            . $transfer->targetDevice->displayName()
            . '.'
        );
    }

    public function reject(
        Request $request,
        OperatorNoteTransferRequest $transfer
    ): RedirectResponse {
        if ($transfer->status !== 'pending') {
            return back()->with(
                'error',
                'Permintaan ini sudah diproses.'
            );
        }

        $transfer->items()->update([
            'is_approved' => false,
        ]);

        $transfer->forceFill([
            'status' => 'rejected',
            'reviewed_by_user_id' =>
                $request->user()->id,
            'reviewed_at' => now(),
        ])->save();

        return back()->with(
            'success',
            'Permintaan transfer ditolak.'
        );
    }

    private function markRead(
        OperatorChatThread $thread,
        string $senderType
    ): void {
        OperatorChatMessage::query()
            ->where(
                'thread_id',
                $thread->id
            )
            ->where(
                'sender_type',
                $senderType
            )
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
            ]);
    }

    private function messagesFor(
        OperatorChatThread $thread
    ): Collection {
        return OperatorChatMessage::query()
            ->with('sender:id,name')
            ->where(
                'thread_id',
                $thread->id
            )
            ->orderByDesc('id')
            ->limit(150)
            ->get()
            ->sortBy('id')
            ->values();
    }

    private function serialize(
        Collection $messages,
        OperatorChatThread $thread
    ): array {
        return $messages
            ->map(
                fn (
                    OperatorChatMessage $message
                ) => [
                    'id' => $message->id,
                    'body' => $message->body,
                    'sender_type' =>
                        $message->sender_type,
                    'sender_name' =>
                        $message->sender_type
                        === 'operator'
                            ? 'PC '
                                . $thread
                                    ->pc_number
                            : (
                                $message
                                    ->sender
                                    ?->name
                                ?? 'Pengawas'
                            ),
                    'is_mine' =>
                        $message->sender_type
                        === 'supervisor',
                    'read' =>
                        $message->read_at
                        !== null,
                    'time' =>
                        $message
                            ->created_at
                            ?->format('H:i'),
                ]
            )
            ->values()
            ->all();
    }
}
