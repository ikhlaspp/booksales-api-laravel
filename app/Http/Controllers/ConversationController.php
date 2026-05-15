<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ConversationController extends Controller
{
    private function getOrCreateConversation(int $userId): Conversation
    {
        return Conversation::firstOrCreate(['user_id' => $userId]);
    }

    private function formatMessage(Message $msg): array
    {
        $data = [
            'id'             => $msg->id,
            'sender_type'    => $msg->sender_type,
            'type'           => $msg->type,
            'body'           => $msg->body,
            'is_read'        => $msg->is_read,
            'created_at'     => $msg->created_at,
            'attachment_url' => null,
            'transaction'    => null,
        ];
        if ($msg->attachment_path) {
            $data['attachment_url'] = Storage::url($msg->attachment_path);
        }
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

    public function show(Request $request): JsonResponse
    {
        $conversation = $this->getOrCreateConversation($request->user()->id);
        $messages = $conversation->messages()->get()->map(fn($m) => $this->formatMessage($m));
        return response()->json(['conversation_id' => $conversation->id, 'messages' => $messages]);
    }

    public function sendMessage(Request $request): JsonResponse
    {
        $request->validate([
            'type'           => 'required|in:text,image,transaction',
            'body'           => 'required_if:type,text|nullable|string',
            'attachment'     => 'required_if:type,image|nullable|image|max:2048',
            'transaction_id' => 'required_if:type,transaction|nullable|integer|exists:transactions,id',
        ]);

        if ($request->type === 'transaction') {
            $tx = Transaction::find($request->transaction_id);
            if ($tx->customer_id !== $request->user()->id) {
                return response()->json(['message' => 'Transaksi tidak ditemukan'], 403);
            }
        }

        $conversation = $this->getOrCreateConversation($request->user()->id);
        $attachmentPath = null;

        if ($request->type === 'image' && $request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('chat-images', 'public');
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $request->user()->id,
            'sender_type'     => 'user',
            'type'            => $request->type,
            'body'            => $request->body,
            'attachment_path' => $attachmentPath,
            'transaction_id'  => $request->transaction_id,
            'is_read'         => false,
        ]);

        $conversation->update(['last_message_at' => now()]);

        return response()->json($this->formatMessage($message), 201);
    }

    public function poll(Request $request): JsonResponse
    {
        $conversation = $this->getOrCreateConversation($request->user()->id);
        $after = (int) $request->query('after', 0);
        $messages = $conversation->messages()
            ->when($after > 0, fn($q) => $q->where('id', '>', $after))
            ->get()
            ->map(fn($m) => $this->formatMessage($m));
        return response()->json(['messages' => $messages]);
    }

    public function markRead(Request $request): JsonResponse
    {
        $conversation = $this->getOrCreateConversation($request->user()->id);
        $conversation->messages()
            ->where('sender_type', 'admin')
            ->where('is_read', false)
            ->update(['is_read' => true]);
        return response()->json(['message' => 'Dibaca']);
    }
}
