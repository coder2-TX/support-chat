@extends(config('support_chat.admin_layout', 'layouts.superadmin'))

@section('content')
<div class="p-4 sm:p-6 max-w-5xl mx-auto space-y-4">
  <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 sm:gap-3 mb-6">
    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 break-words">
      @tr('Conversation —') {{ $business->name ?? ('#'.$business->id) }}
    </h1>
    <a href="{{ route('admin.conversations') }}"
      class="inline-flex items-center gap-1 text-blue-600 hover:underline w-full sm:w-auto">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.78 4.22a.75.75 0 010 1.06L8.56 9.5H16a.75.75 0 010 1.5H8.56l4.22 4.22a.75.75 0 11-1.06 1.06l-5.5-5.5a.75.75 0 010-1.06l5.5-5.5a.75.75 0 011.06 0z" clip-rule="evenodd"/></svg>
      <span>@tr('Back to Conversations')</span>
    </a>
  </div>

  {{-- Users list: each item is a collapsible card with the full thread --}}
  <div class="space-y-6">
    @forelse($items as $it)
      <div class="bg-white rounded-lg shadow-md">
        {{-- Card header (click to toggle + ACK) --}}
        <button
          type="button"
          class="w-full flex flex-wrap items-center justify-between gap-3 p-4 sm:p-6 hover:bg-gray-50"
          data-user-id="{{ $it->user?->id }}"
          data-ack-url="{{ route('admin.conversations.ack_user', [$business->id, $it->user?->id]) }}"
          onclick="toggleAndAck(this)"
          aria-expanded="false"
        >
          <div class="flex items-center gap-4 min-w-0">
            @php
              $avatar  = $it->user?->profile_photo_path ?? null;
              $initial = \Illuminate\Support\Str::upper(mb_substr($it->user?->name ?? 'U',0,1));
            @endphp

            @if($avatar)
              <img src="{{ \Illuminate\Support\Facades\Storage::url($avatar) }}"
                   class="w-10 h-10 rounded-full object-cover" alt="user">
            @else
              <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-700 font-semibold">
                {{ $initial }}
              </div>
            @endif

            @php
                $userRole = $it->user?->roles->first()?->name ?? tr('Unknown Role');
            @endphp

            <div class="min-w-0">
            <div class="font-semibold text-gray-900">
                {{ $it->user?->name ?? tr('Unknown User') }}
                <span class="text-xs text-gray-500">({{ $userRole }})</span>
            </div>

              {{-- Latest message preview (auto-updated) --}}
              <div class="text-sm text-gray-500 truncate max-w-[70ch]"
                   data-user-preview="{{ $it->user?->id }}">
                {{ $it->last?->body ?? tr('No messages yet.') }}
              </div>
            </div>
          </div>

          {{-- Unread badge per user --}}
          <div class="ml-auto flex items-center gap-3" data-right>
            @if($it->unread > 0)
              <span id="badge-{{ $it->user?->id }}"
                    class="inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-semibold text-white bg-red-600">
                {{ $it->unread }}
              </span>
            @endif

            <svg xmlns="http://www.w3.org/2000/svg"
                class="w-5 h-5 text-gray-500 shrink-0"
                viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path fill-rule="evenodd"
                    d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z"
                    clip-rule="evenodd"/>
            </svg>
          </div>

        </button>

        {{-- Collapsible body: full chat UI for this user --}}
        <div class="hidden border-t border-gray-200 p-4 sm:p-6">
          @php $lastId = optional($it->messages->last())->id ?? 0; @endphp

          {{-- Stream container (poll target) --}}
          <div id="chatStream-{{ $it->user?->id }}"
               data-last-id="{{ $lastId }}"
               data-stream-url="{{ route('admin.conversations.stream', [$business->id, $it->user?->id]) }}"
               class="flex-1 overflow-y-auto space-y-3 max-h-[60vh] pb-4"
               style="scroll-behavior:smooth;">
            @foreach($it->messages as $m)
              @php
                $isMe = $m->sender_role === 'admin';
                $rtl  = app()->isLocale('ar');
                $rowClass = $isMe ? ($rtl ? 'justify-start' : 'justify-end')
                                  : ($rtl ? 'justify-end' : 'justify-start');
              @endphp
              <div class="flex {{ $rowClass }}">
                <div class="rounded-2xl px-3 py-2 shadow-sm max-w-[75%] break-words {{ $isMe ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-900' }}">
                  <div class="text-sm">{{ $m->body }}</div>
                  <div class="text-[10px] mt-1 {{ $isMe ? 'text-white/80' : 'text-gray-500' }}">
                    {{ $m->created_at->format('Y-m-d H:i') }}
                  </div>
                </div>
              </div>
            @endforeach
          </div>

          {{-- Reply form (admin → this user) --}}
          <form method="post"
                action="{{ route('admin.conversations.reply_user', [$business->id, $it->user?->id]) }}"
                class="mt-3 flex flex-col sm:flex-row gap-2 reply-form"
                data-user-id="{{ $it->user?->id }}">
            @csrf
            <input name="body"
                  class="w-full sm:flex-1 border border-gray-300 rounded-md px-3 py-2
                          focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                   placeholder="@tr('Type reply...')">
            <button
              class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded w-full sm:w-auto transition duration-300 ease-in-out">
              @tr('Send')
            </button>
          </form>
        </div>
      </div>
    @empty
      <div class="bg-white p-6 rounded-lg shadow-md text-sm text-gray-500">
        @tr('No user messages yet.')
      </div>
    @endforelse
  </div>
</div>

@push('scripts')
<script>
/* ============================================
 * Utilities
 * ============================================ */
const CSRF = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

async function fetchJSON(url, opts = {}) {
  const res = await fetch(url, {
    headers: { 'Accept': 'application/json', ...(opts.headers || {}) },
    ...opts
  });
  return res.ok ? res.json() : {};
}

function renderMsg({ id, body, at, sender_role }) {
  const rtl     = document.documentElement.dir === 'rtl';
  const isMe    = (sender_role === 'admin');
  const rowClass= isMe ? (rtl ? 'justify-start' : 'justify-end')
                       : (rtl ? 'justify-end' : 'justify-start');

  const wrap = document.createElement('div');
  wrap.className = 'flex ' + rowClass;
  wrap.setAttribute('data-mid', id);
  wrap.innerHTML =
    `<div class="rounded-2xl px-3 py-2 shadow-sm max-w-[75%] break-words ${isMe?'bg-blue-600 text-white':'bg-gray-100 text-gray-900'}">
       <div class="text-sm">${body}</div>
       <div class="text-[10px] mt-1 ${isMe?'text-white/80':'text-gray-500'}">${at}</div>
     </div>`;
  return wrap;
}

function updateSidebarBadge(total) {
  const side = document.querySelector('[data-admin-badge]');
  if (!side) return;
  const n = parseInt(total || 0, 10);
  if (n > 0) { side.textContent = n; side.style.display = 'inline-flex'; }
  else { side.textContent = '0'; side.style.display = 'none'; }
}

function updateUserBadge(uid, count) {
  const desired = parseInt(count || 0, 10);
  const id = 'badge-'+uid;
  let badge = document.getElementById(id);

  if (desired > 0) {
    if (!badge) {
      badge = document.createElement('span');
      badge.id = id;
      badge.className = 'inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-semibold text-white bg-red-600';
      const headerBtn = document.querySelector(`button[data-user-id="${uid}"]`);
      const rightWrap = headerBtn?.querySelector('[data-right]');
    if (rightWrap) rightWrap.insertBefore(badge, rightWrap.firstChild);
    }
    badge.textContent = String(desired);
    badge.style.display = 'inline-flex';
  } else if (badge) {
    badge.textContent = '0';
    badge.style.display = 'none';
  }
}

/* ============================================
 * Toggle + ACK on open
 * ============================================ */
async function toggleAndAck(btn){
  const body = btn.nextElementSibling;
  const goingToOpen = body.classList.contains('hidden');
  body.classList.toggle('hidden');
  btn.setAttribute('aria-expanded', goingToOpen ? 'true' : 'false');

  if (!goingToOpen) return;

  // 1) Load messages fresh
  const stream = body.querySelector('[id^="chatStream-"]');
  if (stream){
    const data = await fetchJSON(stream.dataset.streamUrl + '?after=0');
    if (Array.isArray(data.items)) {
      stream.innerHTML = '';
      data.items.forEach(m => stream.appendChild(renderMsg(m)));
      stream.dataset.lastId = data.max_id || stream.dataset.lastId;
      stream.scrollTop = stream.scrollHeight;

      // Update preview text
      const uid = btn.dataset.userId;
      const last = data.items[data.items.length-1];
      const prevEl = document.querySelector(`[data-user-preview="${uid}"]`);
      if (prevEl && last) prevEl.textContent = last.body || prevEl.textContent;
    }
  }

  // 2) ACK now to clear badges
  const ackRes = await fetchJSON(btn.dataset.ackUrl, {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': CSRF(), 'X-Requested-With': 'XMLHttpRequest' }
  });

  // Remove this user badge + update sidebar
  updateUserBadge(btn.dataset.userId, 0);
  if ('total_unread' in ackRes) updateSidebarBadge(ackRes.total_unread);
}

/* ============================================
 * Background polling (every 5s)
 * - Refresh global counter
 * - Stream new messages per open card
 * ============================================ */
setInterval(async () => {
  // 1) Global sidebar counter
  try{
    const j = await fetchJSON(@json(route('admin.conversations.counters')));
    if ('total_unread' in j) updateSidebarBadge(j.total_unread);
  }catch(_){}

  // 2) Per-user streams
  document.querySelectorAll('[id^="chatStream-"]').forEach(async (el) => {
    const body     = el.closest('.border-t');
    const isClosed = body?.classList.contains('hidden');
    const after    = parseInt(el.dataset.lastId || '0', 10);
    const data     = await fetchJSON(el.dataset.streamUrl + '?after=' + after);

    if (!data || !Array.isArray(data.items) || data.items.length === 0) {
      if ('total_unread' in data) updateSidebarBadge(data.total_unread);
      return;
    }

    let newBizCount = 0;

    if (!isClosed) {
      // Card open → append to DOM
      data.items.forEach(m => {
        if (el.querySelector(`[data-mid="${m.id}"]`)) return;
        if (m.sender_role !== 'admin') newBizCount++;
        el.appendChild(renderMsg(m));
      });
      el.dataset.lastId = data.max_id || el.dataset.lastId;
      el.scrollTop = el.scrollHeight;
    } else {
      // Card closed → just count business msgs
      data.items.forEach(m => { if (m.sender_role !== 'admin') newBizCount++; });
      el.dataset.lastId = data.max_id || el.dataset.lastId;
    }

    // Update preview text always
    const headerBtn = body?.previousElementSibling;
    const uid       = headerBtn?.dataset.userId;
    const lastMsg   = data.items[data.items.length - 1];
    if (uid) {
      const prevEl = document.querySelector(`[data-user-preview="${uid}"]`);
      if (prevEl && lastMsg) prevEl.textContent = lastMsg.body || prevEl.textContent;
    }

    // Closed card → update per-user badge (prefer server count if provided)
    if (isClosed && uid) {
      const desired = ('user_unread' in data) ? data.user_unread : newBizCount;
      updateUserBadge(uid, desired);
    }

    // Open card + new business messages → ACK immediately
    if (!isClosed && newBizCount > 0 && headerBtn?.dataset.ackUrl) {
      const ack = await fetchJSON(headerBtn.dataset.ackUrl, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF(), 'X-Requested-With': 'XMLHttpRequest' }
      });

      // Remove user badge; sidebar drops automatically by server total or by delta
      if (uid) updateUserBadge(uid, 0);
      if ('total_unread' in ack) updateSidebarBadge(ack.total_unread);

      // Let other widgets refresh if needed
      window.dispatchEvent(new CustomEvent('counters:refresh'));
    }

    if ('total_unread' in data) updateSidebarBadge(data.total_unread);
  });
}, 5000);

/* ============================================
 * Send admin reply (AJAX, no collapse)
 * ============================================ */
document.addEventListener('submit', async (e) => {
  const form = e.target.closest('.reply-form');
  if (!form) return;

  e.preventDefault();
  const uid   = form.dataset.userId;
  const input = form.querySelector('input[name="body"]');
  const text  = (input.value || '').trim();
  if (!text) return;

  try{
    const j = await fetchJSON(form.action, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': CSRF(),
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({ body: text })
    });

    if (j && j.ok && j.item) {
      const stream = document.querySelector(`#chatStream-${uid}`);
      if (stream) {
        stream.appendChild(renderMsg({
          id: j.item.id, body: j.item.body, at: j.item.at, sender_role: 'admin'
        }));
        stream.dataset.lastId = j.item.id;   // Keep poll pointer accurate
        stream.scrollTop = stream.scrollHeight;
      }

      // Update preview text now
      const prevEl = document.querySelector(`[data-user-preview="${uid}"]`);
      if (prevEl) prevEl.textContent = j.item.body || prevEl.textContent;

      input.value = '';
    }
  }catch(_){}
});
</script>
@endpush

@endsection
