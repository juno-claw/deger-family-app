# Deger Family App - Agentic AI Dokumentation

Diese Dokumentation erklaert einer agentic AI, was die Anwendung tut, wie sie per REST API Eintraege lesen und schreiben kann und welche Skills/Tools/MCPs zur Verfuegung stehen.

## 1) Anwendung im Ueberblick

Die Deger Family App ist eine Familien-Organisations-App mit gemeinsam nutzbaren:
- Listen (Todo- und Shopping-Listen) mit Listeneintraegen
- Kalender-Terminen
- Notizen
- Benachrichtigungen

Alle Inhalte koennen Besitzern gehoeren und mit anderen Usern geteilt werden. Zugriffe werden via Policies autorisiert und es gibt ein Rollenkonzept (z.B. AI Agenten).

## 2) Authentifizierung & Base URL

- Die REST API nutzt Laravel Sanctum mit Bearer Token.
- Header: `Authorization: Bearer {token}` und `Accept: application/json`.
- API Base Path: `{{BASE_URL}}/api/v1`

## 3) Kern-Entities (Datenmodell)

- User: Personen mit Rolle (z.B. AI Agent).
- FamilyList: Listen (Typ `todo` oder `shopping`), mit Items und geteilten Usern.
- ListItem: Eintrag in einer Liste (Text, Position, Erledigt-Status).
- CalendarEvent: Kalendertermine mit Zeitraum, optionaler Wiederholung.
- Note: Notizen mit optionaler Farbe und Pin-Status.
- Notification: Benachrichtigungen von einem User an einen anderen.

## 4) REST API - Endpoints

### Users
- `GET /users` - Alle User (UserResource).
- `GET /users/me` - Der aktuell authentifizierte User.

### Lists
- `GET /lists` - Listen, die fuer den User sichtbar sind.
- `POST /lists` - Neue Liste erstellen.
- `GET /lists/{list}` - Einzelne Liste anzeigen.
- `PUT /lists/{list}` - Liste aktualisieren.
- `DELETE /lists/{list}` - Liste loeschen.

### List Items
- `POST /lists/{list}/items` - Item zu einer Liste hinzufuegen.
- `PUT /lists/{list}/items/{item}` - Item aktualisieren.
- `DELETE /lists/{list}/items/{item}` - Item loeschen.

### Calendar Events
- `GET /calendar/events` - Termine filtern.
- `POST /calendar/events` - Termin erstellen.
- `GET /calendar/events/{event}` - Termin anzeigen.
- `PUT /calendar/events/{event}` - Termin aktualisieren.
- `DELETE /calendar/events/{event}` - Termin loeschen.

### Notes
- `GET /notes` - Notizen, die fuer den User sichtbar sind.
- `POST /notes` - Notiz erstellen.
- `GET /notes/{note}` - Notiz anzeigen.
- `PUT /notes/{note}` - Notiz aktualisieren.
- `DELETE /notes/{note}` - Notiz loeschen.

### Notifications
- `GET /notifications` - Paginiert (30 pro Seite).
- `POST /notifications/push` - Notification an einen anderen User senden (nur AI Agenten).
- `PATCH /notifications/{notification}/read` - Einzelne Notification als gelesen markieren.
- `POST /notifications/read-all` - Alle eigenen Notifications als gelesen markieren.

## 5) REST API - Request Payloads

### Lists
**Create (`POST /lists`)**
```
{
  "title": "Einkauf",
  "type": "shopping",
  "icon": "cart"
}
```
- `title`: required, string, max 255
- `type`: required, `todo` | `shopping`
- `icon`: optional, string, max 50

**Update (`PUT /lists/{list}`)**
```
{
  "title": "Wocheneinkauf",
  "type": "shopping",
  "icon": "basket"
}
```
- Alle Felder optional; gleiche Regeln wie create.

### List Items
**Create (`POST /lists/{list}/items`)**
```
{
  "content": "Milch"
}
```
- `content`: required, string, max 500

**Update (`PUT /lists/{list}/items/{item}`)**
```
{
  "content": "Milch 3,5%",
  "is_completed": true
}
```
- `content`: optional, string, max 500
- `is_completed`: optional, boolean

### Calendar Events
**Create (`POST /calendar/events`)**
```
{
  "title": "Arzttermin",
  "description": "Routine-Check",
  "start_at": "2026-02-10 10:00:00",
  "end_at": "2026-02-10 11:00:00",
  "all_day": false,
  "recurrence": "none",
  "color": "#FF9900"
}
```
- `title`: required, string, max 255
- `description`: optional, string, max 2000
- `start_at`: required, date
- `end_at`: optional, date, >= start_at
- `all_day`: optional, boolean
- `recurrence`: optional, `none|daily|weekly|monthly|yearly`
- `color`: optional, string, max 7

**Update (`PUT /calendar/events/{event}`)**
```
{
  "title": "Arzttermin (verschoben)",
  "start_at": "2026-02-11 10:00:00",
  "end_at": "2026-02-11 11:00:00"
}
```
- Alle Felder optional; gleiche Regeln wie create.

### Notes
**Create (`POST /notes`)**
```
{
  "title": "Wichtig",
  "content": "Hausaufgaben bis Freitag",
  "color": "#FFF3B0"
}
```
- `title`: required, string, max 255
- `content`: optional, string
- `color`: optional, string, max 7

**Update (`PUT /notes/{note}`)**
```
{
  "content": "Hausaufgaben bis Donnerstag",
  "is_pinned": true
}
```
- `title`: optional, string, max 255
- `content`: optional, string
- `color`: optional, string, max 7
- `is_pinned`: optional, boolean

### Notifications
**Push (`POST /notifications/push`)**
```
{
  "user_id": 3,
  "title": "Erinnerung",
  "message": "Bitte an den Termin denken",
  "type": "reminder",
  "data": { "event_id": 12 }
}
```
- Nur AI Agenten duerfen diese Route nutzen.
- `user_id`: required, exists in users
- `title`: required, string, max 255
- `message`: required, string, max 2000
- `type`: optional, string, max 50
- `data`: optional, array

## 6) REST API - Response Fields (Resources)

**UserResource**
```
{
  "id": 1,
  "name": "Juno",
  "email": "juno@deger.family",
  "role": "ai_agent"
}
```

**ListResource**
- `id`, `title`, `type`, `icon`
- `owner` (UserResource)
- `items` (ListItemResource[] wenn geladen)
- `shared_with` (UserResource[])
- `items_count`, `completed_count`
- `created_at`, `updated_at`

**ListItemResource**
- `id`, `content`, `is_completed`, `position`, `created_by`
- `creator` (UserResource wenn geladen)

**CalendarEventResource**
- `id`, `title`, `description`, `start_at`, `end_at`, `all_day`, `recurrence`, `color`
- `owner` (UserResource)
- `shared_with` (UserResource[])
- `created_at`

**NoteResource**
- `id`, `title`, `content`, `is_pinned`, `color`
- `owner` (UserResource)
- `shared_with` (UserResource[])
- `created_at`, `updated_at`

**NotificationResource**
- `id`, `type`, `title`, `message`, `payload`, `read_at`
- `from_user` (UserResource)
- `created_at`

## 7) Filter & Pagination Hinweise

- Kalender: `GET /calendar/events` akzeptiert:
  - `date_from` + `date_to` (ISO-Date/DateTime) fuer einen Zeitraum
  - oder `month` + `year` (Default: aktueller Monat)
- Notifications: Pagination via Laravel Standard (z.B. `?page=2`).

## 8) Fehlerfaelle & Autorisierung

- 401: fehlender/ungueltiger Token.
- 403: keine Berechtigung oder Push-Notification ohne AI Agent Rolle.
- 422: Validierungsfehler; Antwort enthaelt Feldfehler.

## 9) Skills (Agent Skills)

Wenn die AI innerhalb des Repos arbeitet, sind folgende Skills verfuegbar:
- `inertia-react-development` (Inertia v2 + React Pages/Forms)
- `tailwindcss-development` (Tailwind CSS v4 Styles)
- `wayfinder-development` (Laravel Wayfinder Routes im Frontend)
- `developing-with-fortify` (Fortify Auth)
- `create-rule` (Cursor Rules erstellen)
- `create-skill` (Agent Skills erstellen)
- `update-cursor-settings` (Cursor/VSCode settings.json)

## 10) Tools (Lokale Agent-Tools, zusammengefasst)

Diese Agent-Tools sind in der Umgebung verfuegbar und sollten bevorzugt genutzt werden:
- Dateizugriff: `ReadFile`, `LS`, `Glob`, `rg`
- Dateieditierung: `ApplyPatch`, `EditNotebook`
- Terminal/CLI: `Shell` (git, php artisan, npm, etc.)
- Lints: `ReadLints`
- Web: `WebSearch`, `WebFetch`
- Sonstiges: `AskQuestion`, `TodoWrite`, `GenerateImage`, `SemanticSearch`

## 11) MCPs (Server)

- `deger-family-app-laravel-boost`:
  - Laravel-spezifische Helfer wie `search-docs`, `list-routes`, `tinker`, `database-schema`, `read-log-entries`.
- `cursor-ide-browser`:
  - Browser-Automation fuer UI-Tests (navigate, snapshot, click, etc.).

## 12) Kurzbeispiele (Minimal)

**Listen lesen**
```
GET {{BASE_URL}}/api/v1/lists
Authorization: Bearer {{TOKEN}}
Accept: application/json
```

**Notiz schreiben**
```
POST {{BASE_URL}}/api/v1/notes
Authorization: Bearer {{TOKEN}}
Accept: application/json
Content-Type: application/json

{
  "title": "Neue Notiz",
  "content": "Hallo Familie!"
}
```
