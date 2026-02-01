<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;

class ChatController extends Controller
{
    // 1. START CHAT (Check if exists, or create new)
    public function startChat(Request $request)
    {
        $request->validate(['receiver_id' => 'required|exists:users,id']);
        
        $myId = Auth::id();
        $otherId = $request->receiver_id;

        if ($myId == $otherId) {
            return response()->json(['message' => 'You cannot chat with yourself'], 400);
        }

        // 1. Find or Create Conversation
        $conversation = Conversation::where(function($q) use ($myId, $otherId) {
                            $q->where('user1_id', $myId)->where('user2_id', $otherId);
                        })->orWhere(function($q) use ($myId, $otherId) {
                            $q->where('user1_id', $otherId)->where('user2_id', $myId);
                        })->first();

        if (!$conversation) {
            $conversation = Conversation::create([
                'user1_id' => min($myId, $otherId),
                'user2_id' => max($myId, $otherId)
            ]);
        }

        // ✅ 2. FORMAT DATA FOR FRONTEND (Fixes Empty Inbox Issue)
        // We manually construct the response to match 'getConversations' format
        $otherUser = User::find($otherId);
        
        return response()->json([
            'id' => $conversation->id,
            'other_user' => $otherUser,
            'last_message' => 'New conversation',
            'updated_at' => $conversation->updated_at
        ]);
    }

    // 2. GET INBOX (List of people I'm talking to)
   public function getConversations()
    {
        $userId = Auth::id();

        $conversations = Conversation::where('user1_id', $userId)
            ->orWhere('user2_id', $userId)
            ->orderBy('updated_at', 'desc')
            ->with(['messages' => function($q) {
                $q->latest()->limit(1); 
            }])
            ->get()
            ->map(function ($conv) use ($userId) {
                $otherUserId = ($conv->user1_id == $userId) ? $conv->user2_id : $conv->user1_id;
                $otherUser = User::find($otherUserId);

                // ✅ FIX: Check for Image/File if text is empty
                $lastMsgText = 'Start chatting...';
                if ($lastMsg = $conv->messages->first()) {
                    if ($lastMsg->text) {
                        $lastMsgText = $lastMsg->text;
                    } elseif ($lastMsg->attachment_type === 'image') {
                        $lastMsgText = '📷 Sent an image';
                    } elseif ($lastMsg->attachment_type === 'file') {
                        $lastMsgText = '📎 Sent a file';
                    }
                }

                return [
                    'id' => $conv->id,
                    'other_user' => $otherUser, 
                    'last_message' => $lastMsgText, // ✅ Now shows "📷 Sent an image"
                    'updated_at' => $conv->updated_at
                ];
            });

        return response()->json($conversations);
    }




    // ... existing getConversations ...

    public function sendMessage(Request $request)
    {
        // 1. Validation
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'text' => 'nullable|string',
            'attachment' => 'nullable|file|max:10240', // 10MB Limit
        ]);

        $attachmentUrl = null;
        $attachmentType = null;

        // 2. Safe File Upload
        if ($request->hasFile('attachment')) {
            try {
                $file = $request->file('attachment');
                
                // Clean filename to prevent issues
                $originalName = preg_replace('/[^a-zA-Z0-9._-]/', '', $file->getClientOriginalName());
                $filename = time() . '_' . $originalName;
                
                // Save to 'storage/app/public/chat_attachments'
                // This returns relative path: "chat_attachments/filename.jpg"
                $path = $file->storeAs('chat_attachments', $filename, 'public');
                
                // Save the RELATIVE path in DB. We will fix the URL on the frontend.
                // This prevents "double URL" bugs and 500 errors.
                $attachmentUrl = $path; 
                
                $mime = $file->getMimeType();
                $attachmentType = str_starts_with($mime, 'image/') ? 'image' : 'file';
            } catch (\Exception $e) {
                return response()->json(['error' => 'File upload failed: ' . $e->getMessage()], 500);
            }
        }

        // 3. Create Message
        $message = Message::create([
            'conversation_id' => $request->conversation_id,
            'sender_id' => Auth::id(),
            'text' => $request->text,
            'attachment_url' => $attachmentUrl, // Stored as "chat_attachments/image.jpg"
            'attachment_type' => $attachmentType,
        ]);

        // 4. Update Conversation Timestamp
        $message->conversation()->touch();

        return response()->json($message->load('sender'));
    }

    public function getMessages($id)
    {
        // Simple, safe fetch
        $messages = Message::where('conversation_id', $id)
            ->orderBy('created_at', 'asc')
            ->get();
            
        return response()->json($messages);
    }
    

    public function editMessage(Request $request, $id)
    {
        $message = Message::findOrFail($id);

        // Check ownership
        if ($message->sender_id != Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check Time Limit (15 Minutes)
        if ($message->created_at->diffInMinutes(now()) > 15) {
            return response()->json(['message' => 'Edit time limit exceeded'], 400);
        }

        $request->validate(['text' => 'required|string']);

        $message->text = $request->text;
        $message->is_edited = true;
        $message->save();

        return response()->json($message);
    }

    // ✅ 6. DELETE MESSAGE
    public function deleteMessage(Request $request, $id)
    {
        $message = Message::findOrFail($id);
        $userId = Auth::id();
        $type = $request->input('type'); // 'me' or 'everyone'

        if ($type === 'everyone') {
            // Only sender can delete for everyone
            if ($message->sender_id != $userId) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            $message->deleted_everyone = true;
            $message->text = null; // Wipe content
            $message->attachment_url = null;
            $message->save();
        } else {
            // Delete for ME (Add ID to JSON array)
            $deletedFor = $message->deleted_for ?? [];
            if (!in_array($userId, $deletedFor)) {
                $deletedFor[] = $userId;
                $message->deleted_for = $deletedFor;
                $message->save();
            }
        }

        return response()->json(['success' => true]);
    }
}