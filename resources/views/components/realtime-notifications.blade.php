{{-- Realtime notification center (auth only) --}}
<div
    class="relative"
    x-data="ntRealtime({
        feedUrl: @js(route('notifications.feed')),
        readUrl: @js(url('/notifications/__ID__/read')),
        readAllUrl: @js(route('notifications.read-all')),
        csrf: @js(csrf_token()),
        intervalMs: 15000,
        flashSuccess: @js(session('success')),
        flashError: @js(session('error')),
    })"
    @keydown.escape.window="open = false"
>
    <button
        type="button"
        class="relative inline-flex h-10 w-10 items-center justify-center rounded-xl border border-[var(--nt-line)] bg-white/80 transition hover:bg-nt-mist"
        @click="open = !open"
        :aria-expanded="open.toString()"
        aria-label="Notifications"
    >
        <svg class="h-5 w-5 text-nt-ink" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0a3 3 0 11-6 0"/>
        </svg>
        <span
            x-cloak
            x-show="unread > 0"
            class="absolute -right-1 -top-1 inline-flex min-w-[1.15rem] items-center justify-center rounded-full bg-nt-deep px-1 text-[10px] font-bold text-white"
            x-text="unread > 9 ? '9+' : unread"
        ></span>
    </button>

    <div
        x-cloak
        x-show="open"
        x-transition.origin.top.right
        @click.outside="open = false"
        class="absolute right-0 z-50 mt-2 w-[22rem] overflow-hidden rounded-2xl border border-[var(--nt-line)] bg-white shadow-nt-lg sm:w-[24rem]"
    >
        <div class="flex items-center justify-between border-b border-[var(--nt-line)] px-4 py-3">
            <div>
                <p class="font-display text-lg text-nt-ink">Notifications</p>
                <p class="text-xs text-nt-muted" x-text="unread + ' non lue(s)'"></p>
            </div>
            <button type="button" class="nt-link text-xs" @click="markAllRead()" x-show="unread > 0">Tout lire</button>
        </div>

        <div class="max-h-80 overflow-y-auto">
            <template x-if="items.length === 0">
                <p class="px-4 py-8 text-center text-sm text-nt-muted">Aucune notification pour le moment.</p>
            </template>
            <template x-for="item in items" :key="item.id">
                <button
                    type="button"
                    class="flex w-full gap-3 border-b border-[var(--nt-line)] px-4 py-3 text-left transition hover:bg-nt-mist/60"
                    :class="item.is_unread ? 'bg-emerald-50/40' : ''"
                    @click="markRead(item); open = false"
                >
                    <span
                        class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full"
                        :class="{
                            'bg-emerald-500': item.level === 'success',
                            'bg-rose-500': item.level === 'error',
                            'bg-amber-400': item.level === 'warning',
                            'bg-sky-400': !['success','error','warning'].includes(item.level),
                            'opacity-30': !item.is_unread,
                        }"
                    ></span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-semibold text-nt-ink" x-text="item.title"></span>
                        <span class="mt-0.5 block text-xs leading-relaxed text-nt-muted line-clamp-2" x-text="item.body"></span>
                    </span>
                </button>
            </template>
        </div>
    </div>

    <div class="pointer-events-none fixed bottom-4 right-4 z-[60] flex w-[min(24rem,calc(100vw-2rem))] flex-col gap-2">
        <template x-for="toast in toasts" :key="toast.id">
            <div
                class="pointer-events-auto nt-toast"
                :class="levelClass(toast.level)"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="translate-y-3 opacity-0"
                x-transition:enter-end="translate-y-0 opacity-100"
            >
                <div class="flex items-start gap-3">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold" x-text="toast.title"></p>
                        <p class="mt-0.5 text-xs leading-relaxed opacity-90" x-text="toast.body"></p>
                        <a x-show="toast.url" :href="toast.url" class="mt-2 inline-block text-xs font-semibold underline underline-offset-2">Ouvrir</a>
                    </div>
                    <button type="button" class="opacity-70 hover:opacity-100" @click="dismissToast(toast.id)" aria-label="Fermer">✕</button>
                </div>
            </div>
        </template>
    </div>
</div>
