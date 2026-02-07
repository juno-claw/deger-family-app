# Agent 4C: Frontend -- Notizen

Du arbeitest am Projekt "Deger Family App" unter `/var/www/html/deger-family-app`.
Laravel 12, React 19/Inertia 2/TypeScript/Tailwind 4/ShadCN. Git: `git@github.com:juno-claw/deger-family-app.git`

**ZUERST:** `cd /var/www/html/deger-family-app && git pull`

**DEINE AUFGABE:** Erstelle die Frontend-UI fuer Notizen.

Backend existiert bereits:

- `GET /notes` -> `notes/index` (Props: `ownNotes`, `sharedNotes`, `users`)
- `POST /notes` -> Neue Notiz
- `GET /notes/{id}` -> `notes/show` (Props: `note`, `users`)
- `PUT /notes/{id}` -> Update (auch als JSON fuer Autosave)
- `DELETE /notes/{id}` -> Delete
- `PATCH /notes/{id}/pin` -> Toggle Pin
- `POST /notes/{id}/share` -> Share
- `DELETE /notes/{id}/unshare` -> Unshare

**WICHTIG:** Aendere NUR Dateien unter `resources/js/pages/notes/` und `resources/js/components/notes/`.

---

## TypeScript Types

```typescript
export interface Note {
    id: number;
    title: string;
    content: string | null;
    owner_id: number;
    is_pinned: boolean;
    color: string | null;
    owner?: User;
    shared_with?: (User & { pivot: { permission: string } })[];
    created_at: string;
    updated_at: string;
}
```

---

## Seiten und Komponenten

### `resources/js/pages/notes/index.tsx`

- AppLayout
- Head title "Notizen"
- Masonry-aehnliches Grid (CSS columns oder Grid mit auto-rows)
  - 1 Spalte Mobile, 2 Tablet, 3 Desktop
- Pinned-Notizen zuerst (mit kleinem Pin-Icon auf der Karte)
- Section-Trenner: "Meine Notizen" / "Geteilt mit mir"
- FAB zum Erstellen
- Leerer State: "Erstelle deine erste Notiz!"

### `resources/js/components/notes/note-card.tsx`

- ShadCN Card
- Optionale Hintergrundfarbe (`note.color` als bg)
- Titel (fett), Content-Vorschau (max 3 Zeilen, truncated)
- Pin-Icon oben rechts (wenn gepinnt)
- Shared-Avatare unten (kleine Kreise mit Initialen)
- Klick navigiert zu `/notes/{id}`
- Kontextmenue (Long-Press oder 3-Dots): Pin/Unpin, Teilen, Loeschen

### `resources/js/pages/notes/show.tsx`

- AppLayout mit Breadcrumbs `[Notizen > {note.title}]`
- Titel: grosser editierbarer Input
- Content: Textarea (autosize, waechst mit Inhalt)
- Autosave: nach 2 Sekunden Inaktivitaet, sende PUT via fetch (nicht router, um Seite nicht neu zu laden)
- Toolbar oben: Zurueck, Pin-Toggle, Share-Button, Farbe, Delete
- Share-Dialog (wie bei Listen)
- "Gespeichert"-Indikator (kleiner Text der kurz erscheint nach Autosave)

### `resources/js/components/notes/share-dialog.tsx`

- ShadCN Dialog
- Liste der Familienmitglieder (aus `users` Prop)
- Fuer jeden: Toggle ob geteilt + Permission (View/Edit) Select
- Bereits geteilte User hervorgehoben
- Unshare-Button fuer bereits geteilte

### `resources/js/hooks/use-autosave.ts`

- Custom Hook: nimmt `data` und `endpoint`
- Debounced save (2 Sekunden nach letzter Aenderung)
- Returns: `{ isSaving, lastSaved }`
- Verwendet `fetch()` mit CSRF Token (nicht Inertia router)
- CSRF Token aus `<meta name="csrf-token">` oder aus Cookie

---

## Design

- Google Keep Stil: bunte Karten, clean, minimal
- Vorgegebene Farben zum Auswaehlen:
  - `null` (weiss/default)
  - `#fef3c7` (gelb)
  - `#dbeafe` (blau)
  - `#dcfce7` (gruen)
  - `#fce7f3` (pink)
  - `#f3e8ff` (lila)
- Mobile-First
- Smooth transitions

---

## Build & Git

```bash
npm run build

git add -A && git commit -m "Add notes frontend: masonry grid, editor with autosave, sharing

- Notes overview with masonry-style card grid
- Note cards with color, pin indicator, and shared avatars
- Full-screen note editor with autosave (2s debounce)
- Color picker and pin toggle in toolbar
- Share dialog with family member selection
- Google Keep inspired design" && git push
```
