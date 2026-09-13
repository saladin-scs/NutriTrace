import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('ntRealtime', (config = {}) => ({
    open: false,
    unread: 0,
    items: [],
    toasts: [],
    serverTime: null,
    knownIds: new Set(),
    pollTimer: null,
    toastSeq: 0,

    init() {
        this.poll();
        this.pollTimer = setInterval(() => this.poll(true), config.intervalMs || 3000);

        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.poll(true);
            }
        });

        // Convert server flash messages into toasts once.
        if (config.flashSuccess) {
            this.pushToast({
                id: 'flash-ok',
                level: 'success',
                title: 'Succès',
                body: config.flashSuccess,
                url: null,
            });
        }
        if (config.flashError) {
            this.pushToast({
                id: 'flash-err',
                level: 'error',
                title: 'Erreur',
                body: config.flashError,
                url: null,
            });
        }
    },

    destroy() {
        if (this.pollTimer) {
            clearInterval(this.pollTimer);
        }
    },

    async poll(incremental = false) {
        try {
            const url = new URL(config.feedUrl, window.location.origin);
            if (incremental && this.serverTime) {
                url.searchParams.set('since', this.serverTime);
            }

            const res = await fetch(url.toString(), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!res.ok) {
                return;
            }

            const data = await res.json();
            this.unread = data.unread_count ?? 0;
            this.serverTime = data.server_time;

            const incoming = data.notifications ?? [];

            if (!incremental) {
                this.items = incoming;
                incoming.forEach((n) => this.knownIds.add(n.id));
                return;
            }

            // Merge newer notifications to the top and toast unread ones.
            const fresh = [];
            for (const n of incoming) {
                if (this.knownIds.has(n.id)) {
                    continue;
                }
                this.knownIds.add(n.id);
                fresh.push(n);
                if (n.is_unread) {
                    this.pushToast(n);
                }
            }

            if (fresh.length) {
                this.items = [...fresh, ...this.items].slice(0, 20);
            }
        } catch (e) {
            // Silent: keep UI resilient if the tab is offline.
        }
    },

    pushToast(notification) {
        const id = `${notification.id || 'toast'}-${++this.toastSeq}`;
        const toast = {
            id,
            level: notification.level || 'info',
            title: notification.title || 'Notification',
            body: notification.body || '',
            url: notification.url || null,
        };
        this.toasts = [toast, ...this.toasts].slice(0, 4);
        setTimeout(() => this.dismissToast(id), 6500);
    },

    dismissToast(id) {
        this.toasts = this.toasts.filter((t) => t.id !== id);
    },

    async markRead(notification) {
        if (!notification?.id || !notification.is_unread) {
            if (notification?.url) {
                window.location.href = notification.url;
            }
            return;
        }

        try {
            await fetch(config.readUrl.replace('__ID__', notification.id), {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': config.csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });
            notification.is_unread = false;
            this.unread = Math.max(0, this.unread - 1);
        } catch (e) {}

        if (notification.url) {
            window.location.href = notification.url;
        }
    },

    async markAllRead() {
        try {
            await fetch(config.readAllUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': config.csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });
            this.unread = 0;
            this.items = this.items.map((n) => ({ ...n, is_unread: false }));
        } catch (e) {}
    },

    levelClass(level) {
        return {
            success: 'nt-toast-success',
            error: 'nt-toast-error',
            warning: 'nt-toast-warning',
            info: 'nt-toast-info',
        }[level] || 'nt-toast-info';
    },
}));

Alpine.start();
