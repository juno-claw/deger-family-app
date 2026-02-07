# Agent 4A: Frontend -- Listen

Du arbeitest am Projekt "Deger Family App" unter `/var/www/html/deger-family-app`.
Laravel 12, React 19/Inertia 2/TypeScript/Tailwind 4/ShadCN. Git: `git@github.com:juno-claw/deger-family-app.git`

**ZUERST:** `cd /var/www/html/deger-family-app && git pull`

**DEINE AUFGABE:** Erstelle die komplette Frontend-UI fuer Listen (Todo & Einkauf).

Backend existiert bereits:

- `GET /lists` -> `lists/index` (Props: `ownLists`, `sharedLists`)
- `POST /lists` -> Neue Liste
- `GET /lists/{id}` -> `lists/show` (Props: `list` mit items/owner/sharedWith, `users`)
- `PUT /lists/{id}` -> Update
- `DELETE /lists/{id}` -> Delete
- `POST /lists/{id}/share` -> Share
- `DELETE /lists/{id}/unshare` -> Unshare
- `POST /lists/{id}/items` -> Neues Item
- `PUT /lists/{id}/items/{itemId}` -> Update Item
- `DELETE /lists/{id}/items/{itemId}` -> Delete Item
- `POST /lists/{id}/items/reorder` -> Reorder

**WICHTIG:** Aendere NUR Dateien unter `resources/js/pages/lists/` und `resources/js/components/lists/`.

---

## TypeScript Types

Erstelle `resources/js/types/models.ts` (oder erweitere bestehende types):

```typescript
export interface User {
    id: number;
    name: string;
    email: string;
}

export interface ListItem {
    id: number;
    list_id: number;
    content: string;
    is_completed: boolean;
    position: number;
    created_by: number | null;
    creator?: User;
}

export interface FamilyList {
    id: number;
    title: string;
    type: 'todo' | 'shopping';
    icon: string | null;
    owner_id: number;
    owner?: User;
    items?: ListItem[];
    shared_with?: (User & { pivot: { permission: string } })[];
    created_at: string;
    updated_at: string;
}
```

---

## Seiten und Komponenten

### `resources/js/pages/lists/index.tsx`

- AppLayout mit Breadcrumbs
- Head title "Listen"
- Zwei Sections: "Meine Listen" und "Geteilt mit mir"
- Grid: 1 Spalte Mobile, 2 Spalten Tablet, 3 Spalten Desktop
- Jede Liste als Card:
  - Icon: `CheckSquare` fuer todo, `ShoppingCart` fuer shopping (lucide-react)
  - Titel, Fortschrittsanzeige ("3/7 erledigt")
  - Badge mit Listentyp
  - Avatar-Badges fuer geteilte User
  - Klick navigiert zu `/lists/{id}`
- Floating Action Button (FAB): rundes + Button, fixed bottom-right, ueber der Bottom-Nav
  - Oeffnet CreateListDialog
- Leerer State: "Erstelle deine erste Liste!" mit CTA Button

### `resources/js/components/lists/create-list-dialog.tsx`

- ShadCN Dialog
- Form: Titel (Input), Typ (Select: Todo/Einkaufsliste)
- Submit via `router.post('/lists', data)`

### `resources/js/pages/lists/show.tsx`

- AppLayout mit Breadcrumbs `[Listen > {list.title}]`
- Header: Titel (editierbar via Inline-Edit), Typ-Badge, Share-Button, Delete-Button
- Items-Liste:
  - Jedes Item: Checkbox + Text + Delete-Button (X)
  - Checkbox toggle via `router.put`
  - Erledigte Items durchgestrichen und grau
  - Erledigte Items unten gruppiert
- Add-Item Input am Ende (fixed am unteren Rand oder inline)
  - Enter-Taste submitted
- Share-Dialog Button im Header

### `resources/js/components/lists/share-dialog.tsx`

- ShadCN Dialog
- Liste der Familienmitglieder (aus `users` Prop)
- Fuer jeden: Toggle ob geteilt + Permission (View/Edit) Select
- Bereits geteilte User hervorgehoben
- Unshare-Button fuer bereits geteilte

### `resources/js/components/lists/add-item-input.tsx`

- Input mit Placeholder "Neues Element hinzufuegen..."
- Enter = Submit via `router.post`
- Autofocus nach Submit (Input leeren, Fokus behalten)

---

## Design-Richtlinien

- Mobile-First, touch-friendly (min 44px Tap Targets)
- ShadCN Card, Checkbox, Dialog, Button, Badge, Input verwenden
- Sanfte Animationen beim Abhaken (optional)
- Leere States: Wenn keine Listen -> "Erstelle deine erste Liste!" mit CTA

---

## Build & Git

```bash
npm run build  # muss fehlerfrei sein

git add -A && git commit -m "Add lists frontend: overview, detail, CRUD, sharing UI

- Lists overview with card grid (own + shared sections)
- List detail with checkbox items and inline add
- Create list dialog (todo/shopping type selection)
- Share dialog with family member selection
- Floating action button for quick list creation
- Mobile-optimized touch-friendly design" && git push
```
