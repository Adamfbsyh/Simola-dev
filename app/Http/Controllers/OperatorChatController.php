<?php
namespace App\Http\Controllers;

use App\Models\OperatorChatMessage;
use App\Models\OperatorChatThread;
use App\Models\OperatorNote;
use App\Models\OperatorPcAssignment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OperatorChatController extends Controller
{
    public function portal(Request $request): View
    {
        $this->operatorOnly($request);

        $assignment = OperatorPcAssignment::query()
            ->where('user_id', $request->user()->id)
            ->where('is_active', true)
            ->first();

        $thread = $assignment ? $this->threadFor($assignment) : null;

        if ($thread) {
            $this->markRead($thread, 'supervisor');
        }

        $messages = $thread
            ? $this->messagesFor($thread)
            : collect();

        $notes = OperatorNote::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('is_pinned')
            ->orderByDesc('updated_at')
            ->get();

        return view('operator-chat.portal', compact('assignment','thread','messages','notes'));
    }

    public function portalMessages(Request $request): JsonResponse
    {
        $this->operatorOnly($request);

        $assignment = OperatorPcAssignment::query()
            ->where('user_id', $request->user()->id)
            ->where('is_active', true)
            ->first();

        if (!$assignment) {
            return response()->json(['assigned'=>false,'messages'=>[]]);
        }

        $thread = $this->threadFor($assignment);
        $this->markRead($thread, 'supervisor');

        return response()->json([
            'assigned' => true,
            'messages' => $this->serialize($this->messagesFor($thread), $request->user()->id),
        ]);
    }

    public function portalSend(Request $request): JsonResponse
    {
        $this->operatorOnly($request);

        $validated = $request->validate([
            'body' => ['required','string','max:2000'],
        ]);

        $assignment = OperatorPcAssignment::query()
            ->where('user_id', $request->user()->id)
            ->where('is_active', true)
            ->first();

        if (!$assignment) {
            return response()->json(['message'=>'Akun belum ditempatkan ke PC.'], 422);
        }

        $thread = $this->threadFor($assignment);
        $message = $this->createMessage(
            $thread,
            $request->user()->id,
            'operator',
            trim($validated['body'])
        );

        return response()->json(['ok'=>true,'message_id'=>$message->id]);
    }

    public function noteStore(Request $request): RedirectResponse
    {
        $this->operatorOnly($request);

        $validated = $request->validate([
            'body'=>['required','string','max:4000'],
            'is_pinned'=>['nullable','boolean'],
        ]);

        OperatorNote::query()->create([
            'user_id'=>$request->user()->id,
            'body'=>trim($validated['body']),
            'is_pinned'=>$request->boolean('is_pinned'),
        ]);

        return back()->with('success','Catatan disimpan.');
    }

    public function noteDestroy(Request $request, OperatorNote $note): RedirectResponse
    {
        $this->operatorOnly($request);
        abort_unless($note->user_id === $request->user()->id, 403);
        $note->delete();

        return back()->with('success','Catatan dihapus.');
    }

    public function supervisorIndex(Request $request): View
    {
        $fleet = trim((string) $request->input('fleet_type',''));

        $threads = OperatorChatThread::query()
            ->withCount([
                'messages as unread_count' => fn($q) => $q
                    ->where('sender_type','operator')
                    ->whereNull('read_at'),
            ])
            ->when($fleet !== '', fn($q) => $q->where('fleet_type',$fleet))
            ->orderByRaw('CASE WHEN last_message_at IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('last_message_at')
            ->orderBy('fleet_type')
            ->orderBy('pc_number')
            ->get();

        $latest = OperatorChatMessage::query()
            ->with('sender:id,name')
            ->whereIn('thread_id', $threads->pluck('id'))
            ->orderByDesc('id')
            ->get()
            ->unique('thread_id')
            ->keyBy('thread_id');

        $assignments = OperatorPcAssignment::query()
            ->with('user:id,name,email')
            ->where('is_active', true)
            ->get()
            ->groupBy(fn($a) => $a->fleet_type.'|'.$a->pc_number);

        return view('operator-chat.supervisor-index', compact('threads','latest','assignments','fleet'));
    }

    public function supervisorShow(OperatorChatThread $thread): View
    {
        $this->markRead($thread, 'operator');

        $messages = $this->messagesFor($thread);

        $operators = OperatorPcAssignment::query()
            ->with('user:id,name,email')
            ->where('is_active', true)
            ->where('fleet_type', $thread->fleet_type)
            ->where('pc_number', $thread->pc_number)
            ->get();

        return view('operator-chat.supervisor-show', compact('thread','messages','operators'));
    }

    public function supervisorMessages(OperatorChatThread $thread): JsonResponse
    {
        $this->markRead($thread, 'operator');

        return response()->json([
            'messages'=>$this->serialize($this->messagesFor($thread), null),
            'status'=>$thread->fresh()->status,
        ]);
    }

    public function supervisorSend(
        Request $request,
        OperatorChatThread $thread
    ): JsonResponse {
        $validated = $request->validate([
            'body'=>['required','string','max:2000'],
        ]);

        $message = $this->createMessage(
            $thread,
            $request->user()->id,
            'supervisor',
            trim($validated['body'])
        );

        return response()->json(['ok'=>true,'message_id'=>$message->id]);
    }

    public function supervisorResolve(OperatorChatThread $thread): RedirectResponse
    {
        $thread->update([
            'status'=>$thread->status === 'resolved' ? 'open' : 'resolved',
        ]);

        return back()->with('success','Status percakapan diperbarui.');
    }

    public function unread(): JsonResponse
    {
        return response()->json([
            'count'=>OperatorChatMessage::query()
                ->where('sender_type','operator')
                ->whereNull('read_at')
                ->count(),
        ]);
    }

    public function assignments(): View
    {
        $operators = User::query()
            ->role('pc_operator')
            ->orderBy('name')
            ->get();

        $assignments = OperatorPcAssignment::query()
            ->with('user:id,name,email,is_active')
            ->get()
            ->keyBy('user_id');

        return view('operator-chat.assignments', compact('operators','assignments'));
    }

    public function assignmentStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id'=>['required','integer',Rule::exists('users','id')],
            'fleet_type'=>['required',Rule::in(['MT_LPG','MT_PERTASHOP'])],
            'pc_number'=>['required','integer','min:1','max:99'],
            'label'=>['nullable','string','max:100'],
        ]);

        $user = User::query()->findOrFail($validated['user_id']);

        if (!$user->hasRole('pc_operator')) {
            return back()->with('error','User harus memiliki role PC Operator.');
        }

        OperatorPcAssignment::query()->updateOrCreate(
            ['user_id'=>$user->id],
            [
                'fleet_type'=>$validated['fleet_type'],
                'pc_number'=>$validated['pc_number'],
                'label'=>filled($validated['label'] ?? null) ? trim($validated['label']) : null,
                'is_active'=>true,
            ]
        );

        OperatorChatThread::query()->firstOrCreate(
            ['fleet_type'=>$validated['fleet_type'],'pc_number'=>$validated['pc_number']],
            ['status'=>'open']
        );

        return back()->with('success','Penempatan operator disimpan.');
    }

    public function assignmentDestroy(OperatorPcAssignment $assignment): RedirectResponse
    {
        $assignment->delete();
        return back()->with('success','Penempatan operator dilepas.');
    }

    private function operatorOnly(Request $request): void
    {
        abort_unless($request->user()?->hasRole('pc_operator'), 403);
    }

    private function threadFor(OperatorPcAssignment $assignment): OperatorChatThread
    {
        return OperatorChatThread::query()->firstOrCreate(
            ['fleet_type'=>$assignment->fleet_type,'pc_number'=>$assignment->pc_number],
            ['status'=>'open']
        );
    }

    private function createMessage(
        OperatorChatThread $thread,
        int $userId,
        string $senderType,
        string $body
    ): OperatorChatMessage {
        return DB::transaction(function () use ($thread,$userId,$senderType,$body) {
            $message = OperatorChatMessage::query()->create([
                'thread_id'=>$thread->id,
                'sender_user_id'=>$userId,
                'sender_type'=>$senderType,
                'body'=>$body,
            ]);

            $thread->update([
                'status'=>'open',
                'last_message_at'=>$message->created_at,
                'last_message_user_id'=>$userId,
            ]);

            return $message;
        });
    }

    private function markRead(OperatorChatThread $thread, string $senderType): void
    {
        OperatorChatMessage::query()
            ->where('thread_id',$thread->id)
            ->where('sender_type',$senderType)
            ->whereNull('read_at')
            ->update(['read_at'=>now()]);
    }

    private function messagesFor(OperatorChatThread $thread)
    {
        return OperatorChatMessage::query()
            ->with('sender:id,name')
            ->where('thread_id',$thread->id)
            ->latest('id')
            ->limit(150)
            ->get()
            ->reverse()
            ->values();
    }

    private function serialize($messages, ?int $currentUserId): array
    {
        return $messages->map(fn($m) => [
            'id'=>$m->id,
            'body'=>$m->body,
            'sender_type'=>$m->sender_type,
            'sender_name'=>$m->sender?->name ?? ($m->sender_type === 'operator' ? 'Operator' : 'Pengawas'),
            'is_mine'=>$currentUserId ? $m->sender_user_id === $currentUserId : $m->sender_type === 'supervisor',
            'read'=>$m->read_at !== null,
            'time'=>$m->created_at?->format('H:i'),
            'date'=>$m->created_at?->format('d-m-Y'),
        ])->all();
    }
}
