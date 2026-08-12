<?php

namespace App\Http\Controllers;

use App\Models\OperatorChatMessage;
use App\Models\OperatorChatThread;
use App\Models\OperatorDevice;
use App\Models\OperatorDeviceNote;
use App\Models\OperatorNoteTransferItem;
use App\Models\OperatorNoteTransferRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OperatorDevicePortalController extends Controller
{
    private const COOKIE_NAME = 'simola_operator_device';

    public function portal(Request $request): View|Response
    {
        $device = $this->resolveDevice($request);

        if (!$device) {
            return response()->view(
                'operator-chat.device-activate'
            );
        }

        $thread = $this->threadFor($device);
        $this->markRead($thread, 'supervisor');

        $messages = $this->messagesFor($thread);
        $notes = OperatorDeviceNote::query()
            ->with('sourceDevice')
            ->where('device_id', $device->id)
            ->orderByDesc('updated_at')
            ->get();

        $targets = OperatorDevice::query()
            ->where('is_active', true)
            ->where('id', '!=', $device->id)
            ->orderBy('fleet_type')
            ->orderBy('pc_number')
            ->get();

        $outgoing = OperatorNoteTransferItem::query()
            ->with([
                'transfer.targetDevice',
            ])
            ->whereIn(
                'source_note_id',
                $notes->pluck('id')
            )
            ->orderByDesc('id')
            ->get()
            ->unique('source_note_id')
            ->keyBy('source_note_id');

        return view(
            'operator-chat.device-portal',
            compact(
                'device',
                'thread',
                'messages',
                'notes',
                'targets',
                'outgoing'
            )
        );
    }

    public function activate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'regex:/^\d{6}$/',
            ],
        ]);

        $device = OperatorDevice::query()
            ->where('activation_code', $validated['code'])
            ->where('is_active', false)
            ->whereNotNull('activation_expires_at')
            ->where('activation_expires_at', '>=', now())
            ->first();

        if (!$device) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Kode aktivasi tidak valid, sudah dipakai, atau kedaluwarsa.'
                );
        }

        $rawToken = bin2hex(
            random_bytes(32)
        );

        $device->forceFill([
            'device_token_hash' => hash('sha256', $rawToken),
            'activation_code' => null,
            'activation_expires_at' => null,
            'activated_at' => now(),
            'last_seen_at' => now(),
            'released_at' => null,
            'is_active' => true,
        ])->save();

        $this->threadFor($device);

        return redirect()
            ->route('operator-device.portal')
            ->withCookie(
                cookie(
                    self::COOKIE_NAME,
                    $rawToken,
                    525600,
                    null,
                    null,
                    false,
                    true,
                    false,
                    'Lax'
                )
            );
    }

    public function messages(Request $request): JsonResponse
    {
        $device = $this->requireDevice($request);
        $thread = $this->threadFor($device);

        $this->markRead($thread, 'supervisor');

        return response()->json([
            'device' => [
                'id' => $device->id,
                'name' => $device->displayName(),
            ],
            'messages' => $this->serialize(
                $this->messagesFor($thread)
            ),
        ]);
    }

    public function send(Request $request): JsonResponse
    {
        $device = $this->requireDevice($request);

        $validated = $request->validate([
            'body' => [
                'required',
                'string',
                'max:2000',
            ],
        ]);

        $thread = $this->threadFor($device);

        $message = DB::transaction(
            function () use (
                $thread,
                $validated
            ) {
                $message = OperatorChatMessage::query()
                    ->create([
                        'thread_id' => $thread->id,
                        'sender_user_id' => null,
                        'sender_type' => 'operator',
                        'body' => trim(
                            $validated['body']
                        ),
                    ]);

                $thread->forceFill([
                    'status' => 'open',
                    'last_message_at' => $message->created_at,
                    'last_message_user_id' => null,
                ])->save();

                return $message;
            }
        );

        return response()->json([
            'ok' => true,
            'message_id' => $message->id,
        ]);
    }

    public function noteStore(Request $request): RedirectResponse
    {
        $device = $this->requireDevice($request);

        $validated = $request->validate([
            'body' => [
                'required',
                'string',
                'max:4000',
            ],
        ]);

        $lines = $this->noteLines(
            $validated['body']
        );

        if ($lines->isEmpty()) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Catatan masih kosong.'
                );
        }

        if ($lines->count() > 30) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Maksimal 30 catatan dalam sekali tempel.'
                );
        }

        DB::transaction(
            function () use (
                $device,
                $lines
            ): void {
                foreach ($lines as $line) {
                    OperatorDeviceNote::query()->create([
                        'device_id' => $device->id,
                        'body' => $line,
                    ]);
                }
            }
        );

        $count = $lines->count();

        return back()->with(
            'success',
            $count === 1
                ? '1 sticky note ditempel.'
                : $count
                    . ' sticky note ditempel terpisah. '
                    . 'Setiap catatan sekarang dapat dipilih sendiri.'
        );
    }

    public function noteSplit(
        Request $request,
        OperatorDeviceNote $note
    ): RedirectResponse {
        $device = $this->requireDevice($request);

        abort_unless(
            $note->device_id === $device->id,
            403
        );

        if ($note->source_device_id !== null) {
            return back()->with(
                'error',
                'Catatan kiriman dari PC lain tidak dapat dipisahkan.'
            );
        }

        $hasTransfer = OperatorNoteTransferItem::query()
            ->where('source_note_id', $note->id)
            ->exists();

        if ($hasTransfer) {
            return back()->with(
                'error',
                'Catatan ini sudah memiliki riwayat transfer dan tidak dapat dipisahkan.'
            );
        }

        $lines = $this->noteLines(
            $note->body
        );

        if ($lines->count() <= 1) {
            return back()->with(
                'error',
                'Catatan ini sudah terdiri dari satu bagian.'
            );
        }

        DB::transaction(
            function () use (
                $device,
                $note,
                $lines
            ): void {
                foreach ($lines as $line) {
                    OperatorDeviceNote::query()->create([
                        'device_id' => $device->id,
                        'body' => $line,
                    ]);
                }

                $note->delete();
            }
        );

        return back()->with(
            'success',
            $lines->count()
                . ' bagian berhasil dipisahkan menjadi sticky note tersendiri.'
        );
    }

    public function noteDestroy(
        Request $request,
        OperatorDeviceNote $note
    ): RedirectResponse {
        $device = $this->requireDevice($request);

        abort_unless(
            $note->device_id === $device->id,
            403
        );

        $note->delete();

        return back()->with(
            'success',
            'Sticky note dihapus.'
        );
    }

    public function transferStore(Request $request): JsonResponse
    {
        $device = $this->requireDevice($request);

        $validated = $request->validate([
            'target_device_id' => [
                'required',
                'integer',
                'exists:operator_devices,id',
            ],
            'note_ids' => [
                'required',
                'array',
                'min:1',
                'max:20',
            ],
            'note_ids.*' => [
                'required',
                'integer',
            ],
        ]);

        $target = OperatorDevice::query()
            ->whereKey(
                $validated['target_device_id']
            )
            ->where('is_active', true)
            ->first();

        if (
            !$target
            || $target->id === $device->id
        ) {
            return response()->json([
                'message' =>
                    'PC tujuan tidak tersedia.',
            ], 422);
        }

        $ids = collect(
            $validated['note_ids']
        )
            ->map(
                fn ($id) => (int) $id
            )
            ->unique()
            ->values();

        $notes = OperatorDeviceNote::query()
            ->where('device_id', $device->id)
            ->whereIn('id', $ids)
            ->get();

        if ($notes->count() !== $ids->count()) {
            return response()->json([
                'message' =>
                    'Ada catatan yang tidak valid.',
            ], 422);
        }

        $transfer = DB::transaction(
            function () use (
                $device,
                $target,
                $notes
            ) {
                $transfer =
                    OperatorNoteTransferRequest::query()
                        ->create([
                            'source_device_id' =>
                                $device->id,
                            'target_device_id' =>
                                $target->id,
                            'status' =>
                                'pending',
                            'requested_at' =>
                                now(),
                        ]);

                foreach ($notes as $note) {
                    $transfer->items()->create([
                        'source_note_id' =>
                            $note->id,
                        'snapshot_body' =>
                            $note->body,
                        'is_approved' =>
                            null,
                    ]);
                }

                return $transfer;
            }
        );

        return response()->json([
            'ok' => true,
            'transfer_id' => $transfer->id,
            'message' =>
                'Permintaan dikirim ke pengawas untuk disetujui.',
        ]);
    }

    private function noteLines(
        string $body
    ): Collection {
        return collect(
            preg_split(
                '/\R/u',
                trim($body)
            ) ?: []
        )
            ->map(
                fn ($line) =>
                    trim((string) $line)
            )
            ->filter(
                fn ($line) =>
                    $line !== ''
            )
            ->values();
    }

    private function resolveDevice(
        Request $request
    ): ?OperatorDevice {
        $rawToken = trim(
            (string) $request->cookie(
                self::COOKIE_NAME,
                ''
            )
        );

        if ($rawToken === '') {
            return null;
        }

        $device = OperatorDevice::query()
            ->where(
                'device_token_hash',
                hash('sha256', $rawToken)
            )
            ->where('is_active', true)
            ->first();

        if (!$device) {
            return null;
        }

        if (
            !$device->last_seen_at
            || $device->last_seen_at->lt(
                now()->subMinute()
            )
        ) {
            $device->forceFill([
                'last_seen_at' => now(),
            ])->save();
        }

        return $device;
    }

    private function requireDevice(
        Request $request
    ): OperatorDevice {
        $device = $this->resolveDevice(
            $request
        );

        abort_unless(
            $device,
            401,
            'Akses perangkat sudah tidak aktif.'
        );

        return $device;
    }

    private function threadFor(
        OperatorDevice $device
    ): OperatorChatThread {
        return OperatorChatThread::query()
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
        Collection $messages
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
                            ? 'PC Operator'
                            : (
                                $message
                                    ->sender
                                    ?->name
                                ?? 'Pengawas'
                            ),
                    'is_mine' =>
                        $message->sender_type
                        === 'operator',
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
