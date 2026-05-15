<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminConversationController extends Controller
{
    private function formatMessage(Message $msg): array
    {
        $data = [
            'id'             => $msg->id,
            'sender_type'    => $msg->sender_type,
            'type'           => $msg->type,
            'body'           => $msg->body,
            'is_read'        => $msg->is_read,
            'created_at'     => $msg->created_at,
            'attachment_url' => $msg->attachment_path ? Storage::url($msg->attachment_path) : null,
            'transaction'    => null,
        ];
        if ($msg->type === 'transaction' && $msg->transaction_id) {
            $tx = Transaction::find($msg->transaction_id);
            $data['transaction'] = $tx ? [
                'id'           => $tx->id,
                'order_number' => $tx->order_number,
                'total_amount' => $tx->total_amount,
                'status'       => $tx->status,
            ] : null;
        }
        return $data;
    }

    public function index(): JsonResponse
    {
        $conversations = Conversation::with('user')
            ->orderByDesc('last_message_at')
            ->get()
            ->map(function ($conv) {
                $last = $conv->messages()->latest()->first();
                return [
                    'id'           => $conv->id,
                    'user'         => ['id' => $conv->user->id, 'name' => $conv->user->name, 'email' => $conv->user->email],
                    'last_message' => $last?->body ?? '',
                    'last_at'      => $conv->last_message_at,
                    'unread_count' => $conv->unreadCountForAdmin(),
                ];
            });
        return response()->json($conversations);
    }

    public function show(int $id): JsonResponse
    {
        $conversation = Conversation::with('user')->findOrFail($id);
        $messages = $conversation->messages()->get()->map(fn($m) => $this->formatMessage($m));
        return response()->json([
            'id'       => $conversation->id,
            'user'     => ['id' => $conversation->user->id, 'name' => $conversation->user->name],
            'messages' => $messages,
        ]);
    }

    public function reply(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'type'           => 'required|in:text,image,transaction',
            'body'           => 'required_if:type,text|nullable|string',
            'attachment'     => 'required_if:type,image|nullable|image|max:2048',
            'transaction_id' => 'required_if:type,transaction|nullable|integer|exists:transactions,id',
        ]);

        $conversation = Conversation::findOrFail($id);
        $attachmentPath = null;

        if ($request->type === 'image' && $request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('chat-images', 'public');
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $request->user()->id,
            'sender_type'     => 'admin',
            'type'            => $request->type,
            'body'            => $request->body,
            'attachment_path' => $attachmentPath,
            'transaction_id'  => $request->transaction_id,
            'is_read'         => false,
        ]);

        $conversation->update(['last_message_at' => now()]);

        return response()->json($this->formatMessage($message), 201);
    }

    public function markRead(int $id): JsonResponse
    {
        $conversation = Conversation::findOrFail($id);
        $conversation->messages()
            ->where('sender_type', 'user')
            ->where('is_read', false)
            ->update(['is_read' => true]);
        return response()->json(['message' => 'Dibaca']);
    }
}
