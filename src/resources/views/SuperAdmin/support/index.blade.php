@extends(config('support_chat.admin_layout', 'layouts.superadmin'))

@section('content')
<div class="p-6">
  <h1 class="text-2xl font-bold mb-6 text-gray-800">@tr('Conversations')</h1>

  {{-- Search bar (same look & feel as Appointments page) --}}
  <x-search-bar
    :action="route('admin.conversations')"
    name="q"
    placeholder="{{ __('Search businesses...') }}"
  />

  @php
    use Illuminate\Support\Str;
  @endphp

  {{-- Card stack, same spacing and shadow style as Settings page --}}
  <div class="space-y-6">
    @forelse($items as $row)
      {{-- Single conversation card --}}
      <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition">
        {{-- Click → Business users list (collapsible cards) --}}
        <a href="{{ route('admin.conversations.show', $row->business->id) }}"
           class="flex items-center justify-between gap-4">
          <div class="flex items-center gap-4 min-w-0">
            {{-- Business avatar (logo or initials) --}}
            @php
              // Compute initial (fallback if no logo)
              $initial = Str::upper(Str::substr($row->business->name ?? 'B', 0, 1));

              // Raw logo path from DB (may be absolute URL or storage path)
              $logoPath = (string) ($row->business->logo ?? '');

              // Resolve final URL to be used in <img src="...">
              $logoUrl = \Illuminate\Support\Str::startsWith($logoPath, ['http://','https://'])
                  ? $logoPath
                  : (\Illuminate\Support\Str::startsWith($logoPath, ['storage/','/storage/'])
                      ? asset(ltrim($logoPath,'/'))
                      : ($logoPath !== '' ? asset('storage/'.ltrim($logoPath,'/')) : ''));
            @endphp

            {{-- Show logo if available, otherwise a rounded initials placeholder --}}
            @if($logoUrl)
              <img
                src="{{ $logoUrl }}"
                alt="{{ $row->business->name }}"
                class="w-10 h-10 rounded-full object-cover"
                style="border:3px solid var(--sidebar, #1F2937);">
            @else
              <div
                class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-700 font-semibold"
                style="border:3px solid var(--sidebar, #1F2937);">
                {{ $initial }}
              </div>
            @endif

            {{-- Title + last message preview --}}
            <div class="min-w-0">
              {{-- Business name (fallback to ID if name is missing) --}}
              <div class="font-semibold text-gray-900">
                {{ $row->business->name ?? ('Business #'.$row->business->id) }}
              </div>

              {{-- Last message preview (auto-updated by polling) --}}
              <div
                class="text-sm text-gray-500 truncate max-w-[70ch]"
                data-biz-preview="{{ $row->business->id }}">
                {{ $row->last->body ?? tr('No messages yet.') }}
              </div>
            </div>
          </div>

          {{-- Unread badge (hidden if zero) --}}
          <span
            data-biz-badge="{{ $row->business->id }}"
            class="inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-semibold text-white bg-red-600"
            style="{{ ($row->unread ?? 0) > 0 ? '' : 'display:none' }}">
            {{ $row->unread ?? 0 }}
          </span>
        </a>
      </div>
    @empty
      {{-- Empty state card --}}
      <div class="bg-white p-6 rounded-lg shadow-md text-sm text-gray-500">
        @tr('No conversations.')
      </div>
    @endforelse
  </div>
</div>

@push('scripts')
<script>
/**
 * IIFE bootstrapping the page behavior.
 * - Periodically refreshes per-business unread badges.
 * - Updates sidebar total if API returns it.
 * - Listens for a custom event to trigger an immediate refresh.
 *
 * NOTE: Logic intentionally unchanged—only comments/formatting improved.
 */
(async function(){
  /**
   * Fetch unread counters map and update badges.
   * - GET admin.conversations.counters_map (JSON)
   * - For each business: update badge value & visibility.
   * - If total_unread exists: update sidebar bubble.
   */
  async function refreshBizBadges(){
    try{
      const r = await fetch(
        @json(route('admin.conversations.counters_map')),
        { headers:{ 'Accept':'application/json' } }
      );
      const j = await r.json();

      if (j && j.businesses){
        // Update each business badge individually
        Object.entries(j.businesses).forEach(([bizId, n])=>{
          const el = document.querySelector(`[data-biz-badge="${bizId}"]`);
          if (!el) return;

          n = parseInt(n||0,10);
          if (n > 0) {
            el.textContent = n;
            el.style.display = 'inline-flex';
          } else {
            el.textContent = '0';
            el.style.display = 'none';
          }
        });

        // حدّث شارة السايدبار أيضًا لو رجعت (Update sidebar total if provided)
        if (typeof j.total_unread !== 'undefined'){
          const side = document.querySelector('[data-admin-badge]');
          if (side){
            const n = parseInt(j.total_unread||0,10);
            if (n > 0) {
              side.textContent = n;
              side.style.display = 'inline-flex';
            } else {
              side.textContent = '0';
              side.style.display = 'none';
            }
          }
        }
      }
    }catch(e){
      // Silently ignore network/parse errors to avoid UI disruption
    }
  }

  // Initial load, then poll every 5s
  refreshBizBadges();
  setInterval(refreshBizBadges, 5000);

  // Internal event hook to force-refresh from other views
  window.addEventListener('counters:refresh', refreshBizBadges);
})();
</script>
@endpush

@endsection
