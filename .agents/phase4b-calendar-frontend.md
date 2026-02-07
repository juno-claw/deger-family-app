# Agent 4B: Frontend -- Kalender

Du arbeitest am Projekt "Deger Family App" unter `/var/www/html/deger-family-app`.
Laravel 12, React 19/Inertia 2/TypeScript/Tailwind 4/ShadCN. Git: `git@github.com:juno-claw/deger-family-app.git`

**ZUERST:** `cd /var/www/html/deger-family-app && git pull`

**DEINE AUFGABE:** Erstelle die Frontend-UI fuer den Kalender.

Backend existiert bereits:

- `GET /calendar?month=X&year=Y` -> `calendar/index` (Props: `events`, `users`, `month`, `year`)
- `POST /calendar/events` -> Neues Event
- `GET /calendar/events/{id}` -> `calendar/show` (Props: `event`)
- `PUT /calendar/events/{id}` -> Update
- `DELETE /calendar/events/{id}` -> Delete
- `POST /calendar/events/{id}/share` -> Share

**WICHTIG:** Aendere NUR Dateien unter `resources/js/pages/calendar/` und `resources/js/components/calendar/`.

---

## TypeScript Types

```typescript
export interface CalendarEvent {
    id: number;
    title: string;
    description: string | null;
    start_at: string;
    end_at: string | null;
    all_day: boolean;
    recurrence: 'none' | 'daily' | 'weekly' | 'monthly' | 'yearly';
    color: string | null;
    owner_id: number;
    owner?: User;
    shared_with?: (User & { pivot: { status: string } })[];
}
```

---

## Seiten und Komponenten

### `resources/js/pages/calendar/index.tsx`

- AppLayout
- Head title "Kalender"
- Monatsnavigation: `< Monat Jahr >` (Pfeile zum Navigieren, `router.get` mit month/year Params)
- MonthView-Komponente
- FAB zum Event erstellen

### `resources/js/components/calendar/month-view.tsx`

- 7-Spalten-Grid (Mo-So)
- Kopfzeile: Mo Di Mi Do Fr Sa So
- Tage des Monats als Zellen
- Tage mit Events: kleine farbige Dots unter der Zahl
- Heutiger Tag hervorgehoben (Ring oder Hintergrund)
- Tage ausserhalb des Monats ausgegraut
- Tap/Click auf einen Tag oeffnet DayDetailSheet
- Kompakte Mobile-Ansicht (Zellen nicht zu gross)

### `resources/js/components/calendar/day-detail-sheet.tsx`

- ShadCN Sheet (von unten aufgleitend = Bottom Sheet)
- Zeigt: Datum als Titel, Liste aller Events des Tages
- Jedes Event: farbiger Punkt + Titel + Uhrzeit (oder "Ganztaegig")
- Klick auf Event oeffnet EventForm zum Bearbeiten
- "+ Neues Event" Button am Ende
- Swipe-down zum Schliessen (Sheet default behaviour)

### `resources/js/components/calendar/event-form.tsx`

- ShadCN Dialog oder Sheet
- Felder:
  - Titel (Input)
  - Beschreibung (Textarea)
  - Datum Start (Date Picker)
  - Datum Ende (Date Picker)
  - Ganztaegig-Toggle (Switch/Checkbox)
  - Wiederholung (Select: Keine/Taeglich/Woechentlich/Monatlich/Jaehrlich)
  - Farbe (kleine Farbpunkte zum Auswaehlen)
- Erstellen: `router.post('/calendar/events', data)`
- Bearbeiten: `router.put('/calendar/events/{id}', data)`
- Loeschen: `router.delete` (mit Bestaetigung)

### `resources/js/components/calendar/event-badge.tsx`

- Kleiner farbiger Badge fuer Events in der Monatsansicht
- Nur Dot (Punkt) wenn wenig Platz, erweitert zu kleinem Text auf Desktop

---

## Farben

- Default Farben pro User: Olli=`#3b82f6`, Sabsy=`#ec4899`, Juno=`#22c55e`
- Nutzer koennen eigene Farbe pro Event waehlen

---

## Design

- Mobile-First, Monatsansicht soll auf 320px breite Screens passen
- Touch-friendly Date Picker
- Smooth Sheet-Animation

---

## Build & Git

```bash
npm run build

git add -A && git commit -m "Add calendar frontend: month view, day details, event CRUD

- Monthly calendar grid with event dots and color coding
- Day detail bottom sheet with event list
- Event creation/edit form with date pickers
- Month navigation with Inertia routing
- User-based color coding (Olli=blue, Sabsy=pink, Juno=green)
- Mobile-optimized responsive design" && git push
```
