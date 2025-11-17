<?php

namespace khdija\SupportChat\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use khdija\SupportChat\Models\SupportMessage;

/**
 * Super Admin inbox:
 *  - index()   : list businesses with last msg + unread
 *  - users()   : list users per business + their threads
 *  - ackUser() : mark user's messages as read
 *  - replyToUser(): send admin reply
 *  - counters() / countersMap()
 *  - stream()  : polling endpoint
 */
class SupportInboxController extends Controller
{
    protected function businessModel(): string
    {
        return config('support_chat.business_model', \App\Models\Business::class);
    }

    protected function userModel(): string
    {
        return config('support_chat.user_model', \App\Models\User::class);
    }

    /** GET /superadmin/conversations */
    public function index(Request $r)
    {
        $q = trim((string) $r->input('q'));

        $rows = SupportMessage::select(
                'business_id',
                DB::raw('MAX(id) as last_id'),
                DB::raw('SUM(CASE WHEN sender_role="business" AND read_by_admin_at IS NULL THEN 1 ELSE 0 END) as unread')
            )
            ->groupBy('business_id')
            ->orderByDesc('last_id')
            ->get();

        $businessModel = $this->businessModel();

        $items = $rows->map(function ($r) use ($businessModel) {
            return (object) [
                'business' => $businessModel::find($r->business_id),
                'last'     => SupportMessage::find($r->last_id),
                'unread'   => (int) $r->unread,
            ];
        });

        if ($q !== '') {
            $qLower = mb_strtolower($q);
            $items = $items->filter(function ($it) use ($qLower) {
                $name = mb_strtolower($it->business->name ?? '');
                $slug = mb_strtolower($it->business->slug ?? '');
                return str_contains($name, $qLower) || str_contains($slug, $qLower);
            })->values();
        }

        return view('SuperAdmin.support.index', compact('items'));
    }

    /** GET /superadmin/conversations/{business} */
    public function users($businessId)
    {
        $businessModel = $this->businessModel();
        $userModel     = $this->userModel();

        $business = $businessModel::findOrFail($businessId);

        $userRows = SupportMessage::where('business_id', $business->id)
            ->where('sender_role', 'business')
            ->select(
                'sender_id',
                DB::raw('MAX(id) AS last_id'),
                DB::raw('SUM(CASE WHEN read_by_admin_at IS NULL THEN 1 ELSE 0 END) AS unread')
            )
            ->groupBy('sender_id')
            ->orderByDesc('last_id')
            ->get();

        $items = $userRows->map(function ($r) use ($business, $userModel) {
            $user = $userModel::find($r->sender_id);

            $thread = SupportMessage::where('business_id', $business->id)
                ->where(function ($q) use ($r) {
                    $q->where(function ($q2) use ($r) {
                        $q2->where('sender_role', 'business')
                           ->where('sender_id', $r->sender_id);
                    })->orWhere(function ($q3) use ($r) {
                        $q3->where('sender_role', 'admin')
                           ->where('context_user_id', $r->sender_id);
                    });
                })
                ->orderBy('id')
                ->get();

            return (object) [
                'user'     => $user,
                'last'     => SupportMessage::find($r->last_id),
                'unread'   => (int) $r->unread,
                'messages' => $thread,
            ];
        });

        return view('SuperAdmin.support.users', compact('business', 'items'));
    }

    /** POST /superadmin/conversations/{business}/user/{user}/ack */
    public function ackUser($businessId, $userId)
    {
        SupportMessage::where('business_id', $businessId)
            ->where('sender_role', 'business')
            ->where('sender_id', $userId)
            ->whereNull('read_by_admin_at')
            ->update(['read_by_admin_at' => now()]);

        $total = SupportMessage::where('sender_role', 'business')
            ->whereNull('read_by_admin_at')
            ->count();

        return response()->json(['ok' => true, 'total_unread' => $total]);
    }

    /** POST /superadmin/conversations/{business}/user/{user}/reply */
    public function replyToUser(Request $r, $businessId, $userId)
    {
        $r->validate(['body' => 'required|string|min:1']);

        $msg = SupportMessage::create([
            'business_id'     => $businessId,
            'sender_role'     => 'admin',
            'sender_id'       => $r->user()->id,
            'context_user_id' => $userId,
            'body'            => trim($r->body),
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

    /** GET /superadmin/conversations/counters */
    public function counters()
    {
        $total = SupportMessage::where('sender_role', 'business')
            ->whereNull('read_by_admin_at')
            ->count();

        return response()->json(['total_unread' => $total]);
    }

    /** GET /superadmin/conversations/counters-map */
    public function countersMap()
    {
        $rows = SupportMessage::where('sender_role', 'business')
            ->whereNull('read_by_admin_at')
            ->select('business_id', DB::raw('COUNT(*) as unread'))
            ->groupBy('business_id')
            ->get();

        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r->business_id] = (int) $r->unread;
        }

        $total = array_sum($map);

        return response()->json([
            'total_unread' => $total,
            'businesses'   => $map,
        ]);
    }

    /** GET /superadmin/conversations/{business}/user/{user}/stream?after=<id> */
    public function stream(Request $r, $businessId, $userId)
    {
        $after = (int) $r->query('after', 0);

        $messages = SupportMessage::where('business_id', $businessId)
            ->where(function ($q) use ($userId) {
                $q->where(function ($q2) use ($userId) {
                    $q2->where('sender_role', 'business')
                       ->where('sender_id', $userId);
                })->orWhere(function ($q3) use ($userId) {
                    $q3->where('sender_role', 'admin')
                       ->where('context_user_id', $userId);
                });
            })
            ->when($after > 0, fn ($q) => $q->where('id', '>', $after))
            ->orderBy('id')
            ->get(['id', 'sender_role', 'body', 'created_at']);

        $userUnread = SupportMessage::where('business_id', $businessId)
            ->where('sender_role', 'business')
            ->where('sender_id', $userId)
            ->whereNull('read_by_admin_at')
            ->count();

        $totalUnread = SupportMessage::where('sender_role', 'business')
            ->whereNull('read_by_admin_at')
            ->count();

        return response()->json([
            'items' => $messages->map(fn ($m) => [
                'id'          => $m->id,
                'sender_role' => $m->sender_role,
                'body'        => $m->body,
                'at'          => $m->created_at->format('Y-m-d H:i'),
            ]),
            'max_id'       => $messages->max('id') ?? $after,
            'user_unread'  => $userUnread,
            'total_unread' => $totalUnread,
        ]);
    }
}
