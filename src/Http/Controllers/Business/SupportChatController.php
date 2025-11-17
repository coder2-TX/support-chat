<?php

namespace khdija\SupportChat\Http\Controllers\Business;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use khdija\SupportChat\Models\SupportMessage;

class SupportChatController extends Controller
{
    protected function userModel(): string
    {
        return config('support_chat.user_model', \App\Models\User::class);
    }

    /** GET /business/support */
    public function index(Request $r)
    {
        $user   = $r->user();
        $bizId  = $user->business_id;
        $userId = $user->id;

        // تعليم رسائل الأدمن إلى هذا المستخدم كـ "مقروءة" من جهة البزنس
        SupportMessage::where('business_id', $bizId)
            ->where('sender_role', 'admin')
            ->where('context_user_id', $userId)
            ->whereNull('read_by_business_at')
            ->update(['read_by_business_at' => now()]);

        // الخيط: رسائل البزنس من هذا المستخدم + ردود الأدمن الموجهة له
        $messages = SupportMessage::where('business_id', $bizId)
            ->where(function ($q) use ($userId) {
                $q->where(function ($q2) use ($userId) {
                    $q2->where('sender_role', 'business')
                       ->where('sender_id', $userId);
                })->orWhere(function ($q3) use ($userId) {
                    $q3->where('sender_role', 'admin')
                       ->where('context_user_id', $userId);
                });
            })
            ->orderBy('id')
            ->get();

        // أول سوبر أدمن (كما في طابور)
        $userModel = $this->userModel();
        $admin     = $userModel::where('is_superadmin', 1)->orderBy('id')->first();

        return view('business.support', compact('messages', 'admin'));
    }

    /** POST /business/support */
    public function store(Request $r)
    {
        $r->validate(['body' => 'required|string|min:1']);

        $user  = $r->user();
        $bizId = $user->business_id;

        $msg = SupportMessage::create([
            'business_id' => $bizId,
            'sender_role' => 'business',
            'sender_id'   => $user->id,
            'body'        => trim($r->body),
        ]);

        if ($r->expectsJson()) {
            return response()->json([
                'ok'   => true,
                'item' => [
                    'id'          => $msg->id,
                    'sender_role' => $msg->sender_role,
                    'body'        => $msg->body,
                    'at'          => $msg->created_at->format('Y-m-d H:i'),
                ],
            ]);
        }

        return back();
    }
}
