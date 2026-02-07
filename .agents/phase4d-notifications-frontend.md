# Agent 4D: Frontend -- Notifications

Du arbeitest am Projekt "Deger Family App" unter `/var/www/html/deger-family-app`.
Laravel 12, React 19/Inertia 2/TypeScript/Tailwind 4/ShadCN. Git: `git@github.com:juno-claw/deger-family-app.git`

**ZUERST:** `cd /var/www/html/deger-family-app && git pull`

**DEINE AUFGABE:** Erstelle die Notifications-UI und den Polling-Mechanismus.

Backend existiert bereits:

- `GET /notifications` -> `notifications/index` (Props: `notifications` paginiert)
- `PATCH /notifications/{id}/read` -> Als gelesen markieren
- `POST /notifications/read-all` -> Alle gelesen
- `GET /notifications/unread-count` -> JSON `{ count: N }`
- SharedData enthaelt: `notificationCount` (via Inertia Middleware)

**WICHTIG:** Aendere NUR Dateien unter `resources/js/pages/notifications/`, `resources/js/components/notifications/`,
und `resources/js/components/notification-bell.tsx` und `resources/js/hooks/use-notifications.ts`.

---

## TypeScript Types

```typescript
export interface AppNotification {
    id: number;
    user_id: number;
    from_user_id: number | null;
    type: string;
    title: string;
    message: string;
    data: Record<string, any> | null;
    read_at: string | null;
    from_user?: User;
    created_at: string;
}
```

---

## Polling Hook

### `resources/js/hooks/use-notifications.ts`

- Pollt `GET /notifications/unread-count` alle 30 Sekunden
- Verwendet `fetch()` mit `credentials: 'same-origin'`
- Returns: `{ unreadCount, refresh }`
- Aktualisiert nur wenn sich der Count geaendert hat
- Cleanup: Interval bei Unmount clearen

---

## Notification Bell aktualisieren

### `resources/js/components/notification-bell.tsx`

- Falls bereits von Phase 2B erstellt: erweitern
- Verwende `usePage().props.notificationCount` fuer initialen Wert
- Verwende `useNotifications()` Hook fuer Polling-Updates
- Badge: roter Kreis mit Zahl, nur wenn > 0
- Klick: navigiere zu `/notifications`
- Verwende Link von `@inertiajs/react`

---

## Notifications Seite

### `resources/js/pages/notifications/index.tsx`

- AppLayout
- Head title "Benachrichtigungen"
- "Alle gelesen" Button im Header (`router.post('/notifications/read-all')`)
- Gruppiert nach Zeitraum:
  - "Heute" (created_at = heute)
  - "Gestern"
  - "Aelter"
- Leerer State: "Keine Benachrichtigungen" mit Bell-Off Icon
- Pagination falls mehr als 30

### `resources/js/components/notifications/notification-item.tsx`

- Card-aehnlich oder List-Item
- Links: Avatar/Icon des Absenders (Initiale in Kreis)
- Mitte: Titel (fett), Message, Zeitangabe (relativ: "vor 5 Min", "gestern")
- Rechts: blauer Punkt wenn ungelesen
- Klick: Markiere als gelesen + navigiere zum Objekt:
  - `type='list_shared'` -> `/lists/{data.list_id}`
  - `type='event_shared'` -> `/calendar` (mit dem Event-Datum)
  - `type='note_shared'` -> `/notes/{data.note_id}`
  - Default: markiere nur als gelesen
- Hintergrund: ungelesene leicht hervorgehoben (`bg-muted/50` oder aehnlich)

---

## Hilfsfunktion fuer relative Zeit

Erstelle oder erweitere `resources/js/lib/utils.ts`:

```typescript
export function formatRelativeTime(dateString: string): string {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMin = Math.floor(diffMs / 60000);
    const diffHrs = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMin < 1) return 'gerade eben';
    if (diffMin < 60) return `vor ${diffMin} Min`;
    if (diffHrs < 24) return `vor ${diffHrs} Std`;
    if (diffDays === 1) return 'gestern';
    if (diffDays < 7) return `vor ${diffDays} Tagen`;

    return date.toLocaleDateString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
}
```

---

## Design

- Clean, iOS-Notification-Center Stil
- Ungelesene visuell hervorgehoben
- Touch-friendly (min 44px Tap Targets)
- Smooth transitions

---

## Build & Git

```bash
npm run build

git add -A && git commit -m "Add notifications frontend: list, polling, bell badge

- Notifications page grouped by date (today, yesterday, older)
- Notification items with type-based navigation
- Notification bell with unread count badge
- Polling hook for real-time unread count updates (30s interval)
- Relative time formatting (German)
- Mark as read and mark all as read functionality" && git push
```
