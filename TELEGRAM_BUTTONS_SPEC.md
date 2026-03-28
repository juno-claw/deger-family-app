# Telegram Shortcuts — Detail-Ansichten mit Inline-Buttons

## Überblick

Einige Shortcuts (Notizen, Rezepte) sollen klickbare Inline-Buttons in Telegram anzeigen. Beim Klick wird der Detail-Inhalt geladen.

## Flow

```
User tippt /notizen
        ↓
GET /api/v1/shortcuts/notizen?user=olli&format=buttons
        ↓
Response:
{
  "text": "📝 Notizen",
  "items": [
    {"id": 1, "label": "📌 Wichtig"},
    {"id": 2, "label": "📝 Neue Notiz"}
  ]
}
        ↓
Juno sendet Telegram-Nachricht mit Inline-Buttons
        ↓
User klickt [📌 Wichtig] → callback "/notiz 1"
        ↓
GET /api/v1/shortcuts/notiz/1?user=olli
        ↓
Response: { "text": "📝 Wichtig\n\nHier steht der Inhalt..." }
        ↓
Juno sendet Inhalt als normale Nachricht
```

## Neue/Geänderte Endpoints

### 1. `GET /api/v1/shortcuts/notizen?user=olli&format=buttons`

Mit `format=buttons`: JSON mit `text` + `items`-Array.

**Response:**
```json
{
  "text": "📝 Notizen",
  "items": [
    {"id": 1, "label": "📌 Wichtig"},
    {"id": 2, "label": "📝 Neue Notiz"},
    {"id": 3, "label": "📝 Einkaufstipps"}
  ]
}
```

- Gepinnte zuerst (📌), dann normale (📝)
- `label`: Emoji + Titel
- **Ohne** `format=buttons`: Verhalten wie bisher (plain text mit `"text"` Feld)

### 2. `GET /api/v1/shortcuts/notiz/{id}?user=olli` (NEU)

Einzelne Notiz im Detail.

**Response:**
```json
{
  "text": "📝 Wichtig\n\nHier steht der vollständige Inhalt der Notiz."
}
```

- Titel als Überschrift, dann Inhalt
- 404 wenn Notiz nicht existiert oder User keinen Zugriff hat: `{"text": "⚠️ Notiz nicht gefunden."}`

### 3. `GET /api/v1/shortcuts/rezepte?user=olli&format=buttons`

Wie Notizen — mit `format=buttons` gibt's `items`-Array.

**Response:**
```json
{
  "text": "👨‍🍳 Rezepte",
  "items": [
    {"id": 1, "label": "⭐ Spaghetti Bolognese"},
    {"id": 2, "label": "📖 Pfannkuchen"}
  ]
}
```

- Favoriten zuerst (⭐), dann normale (📖)

### 4. `GET /api/v1/shortcuts/rezept/{id}?user=olli` (NEU)

Einzelnes Rezept im Detail.

**Response:**
```json
{
  "text": "👨‍🍳 Spaghetti Bolognese\n\n⏱ 45 Min. (15 Vorbereitung + 30 Kochen)\n🍽 4 Portionen\n\n📋 Zutaten:\n• 500g Spaghetti\n• 400g Hackfleisch\n• 1 Dose Tomaten\n\n👩‍🍳 Zubereitung:\n1. Wasser kochen\n2. Spaghetti kochen\n3. Soße zubereiten"
}
```

### Routes

```php
// Erweiterte Shortcuts mit Detail-Ansicht
Route::get('shortcuts/notiz/{id}', [ShortcutApiController::class, 'notiz']);
Route::get('shortcuts/rezept/{id}', [ShortcutApiController::class, 'rezept']);
```

(Die bestehenden `/shortcuts/notizen` und `/shortcuts/rezepte` Endpoints bekommen nur die `format=buttons` Variante dazu.)

## Welche Shortcuts bekommen Buttons?

| Shortcut | Buttons? | Detail-Endpoint | Begründung |
|---|---|---|---|
| `/notizen` | ✅ | `/notiz/{id}` | Notiz-Inhalt ist relevant |
| `/rezepte` | ✅ | `/rezept/{id}` | Rezept-Details sind lang |
| `/einkauf` | ❌ | — | Items direkt sichtbar |
| `/todo` | ❌ | — | Items direkt sichtbar |
| `/kalender` | ❌ | — | Termine direkt sichtbar |

## Juno-Seite (baut Juno)

- Skill `/notizen` ruft API mit `format=buttons` auf, parsed das JSON, sendet Nachricht mit Inline-Buttons
- Callback-Data der Buttons: `/notiz {id}` → neuer Skill `/notiz` ruft Detail-Endpoint auf
- Gleiches für `/rezepte` → `/rezept {id}`
