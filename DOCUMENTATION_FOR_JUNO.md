# Deger Family App - Agentic AI Dokumentation

Diese Dokumentation erklaert einer agentic AI, was die Anwendung tut, wie sie per REST API Eintraege lesen und schreiben kann und welche Skills/Tools/MCPs zur Verfuegung stehen.

## 1) Anwendung im Ueberblick

Die Deger Family App ist eine Familien-Organisations-App mit gemeinsam nutzbaren:
- Listen (Todo- und Shopping-Listen) mit Listeneintraegen
- Kalender-Terminen
- Notizen
- Rezepten (Koch- und Backrezepte)
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
- Recipe: Rezepte mit Kategorie, Zutaten, Zubereitung, Zeiten und Portionen.
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

### Recipes
- `GET /recipes` - Rezepte, die fuer den User sichtbar sind (eigene + geteilte).
- `POST /recipes` - Rezept erstellen.
- `GET /recipes/{recipe}` - Rezept anzeigen.
- `PUT /recipes/{recipe}` - Rezept aktualisieren.
- `DELETE /recipes/{recipe}` - Rezept loeschen.

### Sharing (Listen, Notizen, Rezepte, Kalender-Termine)
- `POST /lists/{list}/share` - Liste mit einem User teilen.
- `DELETE /lists/{list}/share` - Sharing einer Liste aufheben.
- `POST /notes/{note}/share` - Notiz mit einem User teilen.
- `DELETE /notes/{note}/share` - Sharing einer Notiz aufheben.
- `POST /recipes/{recipe}/share` - Rezept mit einem User teilen.
- `DELETE /recipes/{recipe}/share` - Sharing eines Rezepts aufheben.
- `POST /calendar/events/{event}/share` - Kalender-Termin mit einem User teilen.
- `DELETE /calendar/events/{event}/share` - Sharing eines Kalender-Termins aufheben.

### Notifications
- `GET /notifications` - Paginiert (30 pro Seite).
- `POST /notifications/push` - Notification an einen anderen User senden (nur AI Agenten).
- `PATCH /notifications/{notification}/read` - Einzelne Notification als gelesen markieren.
- `POST /notifications/read-all` - Alle eigenen Notifications als gelesen markieren.

### Google Calendar
- `GET /google-calendar/status` - Verbindungsstatus abfragen.
- `POST /google-calendar/connect` - Google Calendar per Service Account verbinden.
- `PATCH /google-calendar/toggle` - Sync aktivieren/deaktivieren.
- `DELETE /google-calendar/disconnect` - Verbindung trennen.

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

### Recipes
**Create (`POST /recipes`)**
```
{
  "title": "Spaghetti Bolognese",
  "description": "Klassiker der italienischen Kueche",
  "category": "cooking",
  "servings": 4,
  "prep_time": 15,
  "cook_time": 30,
  "ingredients": "500g Spaghetti\n400g Hackfleisch\n1 Dose Tomaten",
  "instructions": "Wasser kochen\nSpaghetti kochen\nSosse zubereiten"
}
```
- `title`: required, string, max 255
- `description`: optional, string, max 1000
- `category`: required, `cooking` | `baking` | `dessert` | `snack` | `drink`
- `servings`: optional, integer, 1-100
- `prep_time`: optional, integer (Minuten), 0-1440
- `cook_time`: optional, integer (Minuten), 0-1440
- `ingredients`: required, string (eine Zutat pro Zeile)
- `instructions`: required, string (Schritt fuer Schritt)

**Update (`PUT /recipes/{recipe}`)**
```
{
  "title": "Spaghetti Bolognese (verbessert)",
  "servings": 6,
  "is_favorite": true
}
```
- Alle Felder optional; gleiche Regeln wie create.
- `is_favorite`: optional, boolean

### Sharing (Listen, Notizen, Rezepte)
**Share (`POST /lists/{list}/share`, `POST /notes/{note}/share`, `POST /recipes/{recipe}/share`)**
```
{
  "user_id": 3,
  "permission": "view"
}
```
- `user_id`: required, exists in users, darf nicht der eigene User sein
- `permission`: required, `view` | `edit`

**Share Kalender-Termin (`POST /calendar/events/{event}/share`)**
```
{
  "user_id": 3
}
```
- `user_id`: required, exists in users, darf nicht der eigene User sein
- Kalender-Termine benoetigen kein `permission`-Feld; der Status wird automatisch auf `pending` gesetzt.

**Unshare (`DELETE /lists/{list}/share`, `DELETE /notes/{note}/share`, `DELETE /recipes/{recipe}/share`, `DELETE /calendar/events/{event}/share`)**
```
{
  "user_id": 3
}
```
- `user_id`: required, exists in users
- Nur der Owner kann Sharing aufheben.

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

**RecipeResource**
- `id`, `title`, `description`, `category`, `servings`, `prep_time`, `cook_time`
- `ingredients`, `instructions`, `is_favorite`
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

**Rezept erstellen**
```
POST {{BASE_URL}}/api/v1/recipes
Authorization: Bearer {{TOKEN}}
Accept: application/json
Content-Type: application/json

{
  "title": "Pfannkuchen",
  "category": "baking",
  "servings": 4,
  "prep_time": 10,
  "cook_time": 15,
  "ingredients": "250g Mehl\n3 Eier\n500ml Milch\n1 Prise Salz",
  "instructions": "Alle Zutaten verrühren\nTeig 10 Min. ruhen lassen\nIn der Pfanne goldbraun backen"
}
```

---

## Google Calendar Synchronisation

Juno kann seinen eigenen Google Calendar mit der App synchronisieren. Die Synchronisation ist bidirektional:
- Kalender-Eintraege aus der App werden automatisch in den Google Calendar geschrieben (via Queue-Job)
- Aenderungen im Google Calendar werden alle 5 Minuten in die App gezogen (via Scheduler)

### Aktive Verbindung

Junos Google Calendar ist bereits verbunden:
- **Calendar ID:** `claw.juno@gmail.com`
- **Verbindungstyp:** Service Account (`juno-calendar-service@juno-claw.iam.gserviceaccount.com`)
- **Google Cloud Projekt:** `juno-claw`
- **Status:** Aktiv

### Infrastruktur

- **Queue Worker:** Laeuft via Supervisor (`/etc/supervisor/conf.d/deger-family-worker.conf`), verarbeitet Sync-Jobs automatisch
- **Scheduler Cronjob:** `* * * * * cd /var/www/html/deger-family-app && /usr/bin/php artisan schedule:run` (root crontab)
- **Pull-Intervall:** Alle 5 Minuten via `google-calendar:pull` Artisan Command
- **Service Account Credentials:** `/var/www/html/deger-family-app/storage/app/google/service-account.json`

### Google Calendar API Endpoints

#### Verbindungsstatus pruefen

```
GET {{BASE_URL}}/api/v1/google-calendar/status
Authorization: Bearer {{TOKEN}}
Accept: application/json
```

Antwort wenn verbunden:
```json
{
  "connected": true,
  "connection_type": "service_account",
  "calendar_id": "claw.juno@gmail.com",
  "enabled": true,
  "last_synced_at": "2026-02-08T08:45:03+00:00"
}
```

#### Verbindung herstellen (Service Account)

```
POST {{BASE_URL}}/api/v1/google-calendar/connect
Authorization: Bearer {{TOKEN}}
Accept: application/json
Content-Type: application/json

{
  "calendar_id": "claw.juno@gmail.com"
}
```

#### Sync aktivieren/deaktivieren

```
PATCH {{BASE_URL}}/api/v1/google-calendar/toggle
Authorization: Bearer {{TOKEN}}
Accept: application/json
```

#### Verbindung trennen

```
DELETE {{BASE_URL}}/api/v1/google-calendar/disconnect
Authorization: Bearer {{TOKEN}}
Accept: application/json
```

### Funktionsweise

Sobald die Verbindung aktiv ist:
- Jedes Erstellen, Aktualisieren oder Loeschen eines Kalender-Eintrags wird automatisch per Queue-Job an Google Calendar synchronisiert
- Events, die Juno betreffen (als Owner oder geteilt), werden synchronisiert
- Alle 5 Minuten werden Aenderungen aus Google Calendar in die App gezogen (Scheduler: `google-calendar:pull`)
- Sync-Loop Prevention: Wenn Events von Google gepullt werden, werden sie nicht zurueck nach Google gepusht

### Wartung

- Worker Status pruefen: `sudo supervisorctl status`
- Worker neustarten: `sudo supervisorctl restart deger-family-worker:*`
- Worker Log: `/var/www/html/deger-family-app/storage/logs/worker.log`
- Manuell Pull ausfuehren: `php artisan google-calendar:pull`
