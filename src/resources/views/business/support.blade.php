@extends(config('support_chat.business_layout', 'layouts.app'))

@section('content')
<div class="p-4 sm:p-6 max-w-5xl mx-auto space-y-6">
  <h1 class="text-2xl sm:text-3xl font-bold break-words">@tr('Contact System Admin')</h1>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
    {{-- Admin data card (values come from $admin; managed via Super Admin later) --}}
    @php
      use Illuminate\Support\Facades\Storage;
      use Illuminate\Support\Str;
      // Normalize WhatsApp phone: strip spaces and leading '+'
      $wa = !empty($admin?->phone) ? ltrim(preg_replace('/\s+/', '', $admin->phone), '+') : null;
    @endphp

    {{-- ===== Admin info card ===== --}}
    <div class="md:col-span-1 bg-white p-4 sm:p-6 rounded-lg shadow-md min-w-0">
      {{-- Admin avatar + name/role --}}
      <div class="flex items-center gap-3 mb-4 min-w-0">
        @if(!empty($admin?->profile_photo_path))
          <img
            src="{{ Storage::url($admin->profile_photo_path) }}"
            alt="@tr('Admin')"
            class="w-14 h-14 rounded-full object-cover"
            style="border:3px solid var(--sidebar);">
        @else
          <div
            class="w-14 h-14 rounded-full bg-gray-200 flex items-center justify-center text-gray-600 font-semibold"
            style="border:3px solid var(--sidebar);">
            {{ Str::upper(Str::substr($admin->name ?? 'S', 0, 1)) }}
          </div>
        @endif

        <div>
          <div class="font-semibold text-gray-800">
            {{ $admin->name ?? tr('System Owner') }}
          </div>
          <div class="text-xs text-gray-500">@tr('Super Admin')</div>
        </div>
      </div>

      {{-- ===== Quick actions (email / call / WhatsApp) ===== --}}
      <div class="flex flex-col sm:flex-row flex-wrap gap-2 mb-4">
        @if(!empty($admin?->email))
          {{-- Email button --}}
          <a
            href="mailto:{{ $admin->email }}"
                class="inline-flex items-center justify-center gap-2 border border-gray-300 bg-white rounded-md px-3 py-1.5 text-sm w-full sm:w-auto transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-blue-500">
            {{-- Icon: mail --}}
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-width="2" d="M4 6h16v12H4z"/><path stroke-width="2" d="m22 6-10 7L2 6"/>
            </svg>
            @tr('Email')
          </a>
        @endif

        @if(!empty($admin?->phone))
          {{-- Call button --}}
          <a
            href="tel:{{ $admin->phone }}"
            class="inline-flex items-center justify-center gap-2 border border-gray-300 bg-white rounded-md px-3 py-1.5 text-sm w-full sm:w-auto transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-blue-500">
            {{-- Icon: phone --}}
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
              <path d="M6.62 10.79a15.05 15.05 0 006.59 6.59l2.2-2.2a1 1 0 011.01-.24 11.36 11.36 0 003.56.57 1 1 0 011 1V20a1 1 0 01-1 1A17 17 0 013 4a1 1 0 011-1h2.5a1 1 0 011 1 11.36 11.36 0 00.57 3.56 1 1 0 01-.24 1.01l-2.2 2.2z"/>
            </svg>
            @tr('Call')
          </a>

          {{-- WhatsApp button (opens wa.me in new tab) --}}
          <a
            target="_blank" rel="noopener"
            href="https://wa.me/{{ $wa }}"
            class="inline-flex items-center justify-center gap-2 border border-gray-300 bg-white rounded-md px-3 py-1.5 text-sm w-full sm:w-auto transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-blue-500"
            aria-label="@tr('Chat on WhatsApp')">
            {{-- Icon: WhatsApp (brand green) --}}
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 32 32">
              <path fill="#22C55E" d="M26.7 5.3A13.9 13.9 0 0016 2 14 14 0 002 16c0 2.46.65 4.77 1.79 6.77L2 30l7.4-1.94A14 14 0 1016 2z"/>
              <path fill="#fff" d="M19.11 17.02c-.3-.15-1.77-.87-2.04-.97-.27-.1-.47-.15-.67.15l-.94 1.17c-.17.2-.35.22-.65.07-1.62-.8-2.71-1.89-3.32-2.86-.17-.3-.02-.46.13-.61l.45-.52c.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52l-.92-2.22c-.24-.58-.49-.5-.67-.51h-1.2c-.2 0-.52.07-.8.37-.27.3-1.04 1.02-1.04 2.48s1.22 3.08 1.22 3.08 2.11 3.22 5.1 4.52c1.72.74 2.57.79 3.56.64.57-.09 1.77-.72 2.02-1.42.25-.7.25-1.29.17-1.42-.07-.13-.27-.2-.57-.35z"/>
            </svg>
            @tr('WhatsApp')
          </a>
        @endif
      </div>

      {{-- Small textual details under buttons --}}
      <div class="space-y-1 text-sm">
        {{-- Email row --}}
        <div>
          @tr('Email'):
          @if(!empty($admin?->email))
            <a href="mailto:{{ $admin->email }}" class="text-blue-600">{{ $admin->email }}</a>
          @else
            <span class="text-gray-500">@tr('N/A')</span>
          @endif
        </div>

        {{-- Phone row --}}
        <div>
          @tr('Phone'):
          @if(!empty($admin?->phone))
            <a href="tel:{{ $admin->phone }}" class="text-blue-600">{{ $admin->phone }}</a>
          @else
            <span class="text-gray-500">@tr('N/A')</span>
          @endif
        </div>

        {{-- WhatsApp row --}}
        <div>
          @tr('WhatsApp'):
          @if(!empty($admin?->phone))
            <a
              target="_blank" rel="noopener"
              href="https://wa.me/{{ $wa }}"
              class="inline-flex items-center gap-1 text-sm hover:underline"
              style="color:#1A9E51;">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 32 32" fill="currentColor" aria-hidden="true">
                <path d="M26.7 5.3A13.93 13.93 0 0016 2C8.28 2 2 8.28 2 16c0 2.46.65 4.77 1.79 6.77L2 30l7.4-1.94A13.93 13.93 0 0016 30c7.72 0 14-6.28 14-14 0-3.74-1.46-7.15-3.3-9.7z"/>
                <path d="M19.11 17.02c-.3-.15-1.77-.87-2.04-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.47-.89-.79-1.49-1.76-1.66-2.06-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.07-.15-.67-1.62-.92-2.22-.24-.58-.49-.5-.67-.51l-.57-.01c-.2 0-.52.07-.8.37-.27.3-1.04 1.02-1.04 2.48 0 1.46 1.07 2.88 1.22 3.08.15.2 2.11 3.22 5.1 4.52.71.31 1.27.5 1.7.64.71.22 1.35.19 1.86.12.57-.09 1.77-.72 2.02-1.42.25-.7.25-1.29.17-1.42-.07-.13-.27-.2-.57-.35z"/>
              </svg>
              @tr('Open in WhatsApp')
            </a>
          @else
            <span class="text-gray-500">@tr('N/A')</span>
          @endif
        </div>
      </div>
    </div>

    {{-- ===== Conversation card (business-side chat) ===== --}}
    <div class="md:col-span-2 bg-white p-4 sm:p-6 rounded-lg shadow-md flex flex-col">      {{-- Messages stream (server-rendered list; new items appended by JS after send) --}}
      <div
        id="chatStream"
        class="flex-1 overflow-y-auto space-y-3 max-h-[50vh] sm:max-h-[60vh] border-b border-gray-200 pb-4 rounded"        style="scroll-behavior:smooth;">
        @foreach ($messages as $m)
          @php
            // Who sent the message?
            $isMe = $m->sender_role === 'business';
            $rtl  = app()->isLocale('ar');

            /**
             * Horizontal alignment:
             * - LTR: my messages → right, others → left
             * - RTL: my messages → left,  others → right
             */
            $rowClass = $isMe
                        ? ($rtl ? 'justify-start' : 'justify-end')
                        : ($rtl ? 'justify-end' : 'justify-start');
          @endphp

          <div class="flex {{ $rowClass }}">
            <div class="rounded-2xl px-3 py-2 shadow-sm max-w-[75%] break-words
                        {{ $isMe ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-900' }}">
              <div class="text-sm">{{ $m->body }}</div>
              <div class="text-[10px] mt-1 {{ $isMe ? 'text-white/80' : 'text-gray-500' }}">
                {{ $m->created_at->format('Y-m-d H:i') }}
              </div>
            </div>
          </div>
        @endforeach
      </div>

      {{-- Send message form (AJAX submit; no page reload) --}}
      <form
        method="post"
        action="{{ route('business.support.store') }}"
        class="mt-3 flex flex-col sm:flex-row gap-2 biz-reply-form">
        @csrf
        <input
          name="body"
          class="w-full sm:flex-1 border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
          placeholder="@tr('Type your message...')">
        <button
          class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded w-full sm:w-auto transition duration-300 ease-in-out">
          @tr('Send')
        </button>
      </form>
    </div>
  </div>
</div>

@push('scripts')
<script>
/**
 * Page bootstrap:
 * - Auto-scroll chat to bottom on load and resize.
 * - Intercept form submit to send via fetch (AJAX) and append the new bubble.
 *
 * NOTE: Business logic stays the same; only comments/formatting improved.
 */
document.addEventListener('DOMContentLoaded', function () {
  const box = document.getElementById('chatStream');

  // Keep the latest message in view initially and when window resizes
  if (box) box.scrollTop = box.scrollHeight;
  window.addEventListener('resize', () => { if (box) box.scrollTop = box.scrollHeight; });

  // Handle "Send" without full page refresh
  document.addEventListener('submit', async (e) => {
    const form = e.target.closest('.biz-reply-form');
    if (!form) return;
    e.preventDefault();

    // Read & validate input
    const input = form.querySelector('input[name="body"]');
    const text  = (input.value || '').trim();
    if (!text) return;

    try{
      // POST JSON to store endpoint (expects { ok, item:{id, body, at} })
      const r = await fetch(form.action, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ body: text })
      });

      const j = await r.json();

      // On success, append a "my message" bubble and scroll down
      if (j && j.ok && j.item){
        const rtl = document.documentElement.dir === 'rtl';
        const rowClass = rtl ? 'justify-start' : 'justify-end';

        const wrap = document.createElement('div');
        wrap.className = 'flex ' + rowClass;
        wrap.setAttribute('data-mid', j.item.id); // guard against duplicates if needed
        wrap.innerHTML =
          `<div class="rounded-2xl px-3 py-2 shadow-sm max-w-[75%] break-words bg-blue-600 text-white">
             <div class="text-sm">${j.item.body}</div>
             <div class="text-[10px] mt-1 text-white/80">${j.item.at}</div>
           </div>`;

        box.appendChild(wrap);
        box.scrollTop = box.scrollHeight;
        input.value = '';
      }
    }catch(_){
      // Silently ignore network/parse errors to keep UX simple
    }
  });
});
</script>
@endpush

@endsection
