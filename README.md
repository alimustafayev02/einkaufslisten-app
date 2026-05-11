# Einkaufslisten-App

Ein REST-Backend-Service mit Web-Oberfläche zur Verwaltung mehrerer Einkaufslisten – realisiert mit **Symfony 7**, **MySQL 8** und **Docker**.

---

##  Inhaltsverzeichnis

- [Übersicht](#übersicht)
- [Verwendete Technologien](#verwendete-technologien)
- [Voraussetzungen](#voraussetzungen)
- [Installation & Start](#installation--start)
- [Verwendung der Web-Oberfläche](#verwendung-der-web-oberfläche)
- [REST-API Dokumentation](#rest-api-dokumentation)
- [Datenmodell](#datenmodell)
- [Projektstruktur](#projektstruktur)
- [Hilfreiche Befehle](#hilfreiche-befehle)
- [Fehlerbehebung](#fehlerbehebung)

---

## Übersicht

Diese Anwendung stellt eine vollständige REST-API zur Verwaltung von Einkaufslisten bereit und bietet zusätzlich eine benutzerfreundliche Web-Oberfläche zur Pflege der Listen. Alle in der Aufgabenstellung geforderten Endpunkte sind implementiert.

### Funktionsumfang

-  Mehrere Einkaufslisten anlegen und verwalten
-  Einträge zu Listen hinzufügen, bearbeiten, löschen
-  Einträge als "erledigt" markieren (Checkbox)
-  Mengenangabe pro Eintrag
-  Responsive Web-Oberfläche (Bootstrap 5)
-  Vollständige REST-API nach Symfony-Prinzipien
-  Dockerisierte Entwicklungsumgebung

###  Zusätzlich – über die Aufgabenstellung hinaus

Um die Überprüfung der Anwendung möglichst komfortabel zu gestalten, wurden folgende **Extras** ergänzt:

-  **Interaktive Swagger-UI Dokumentation** unter [http://localhost:8080/api-docs.html](http://localhost:8080/api-docs.html) – alle Endpunkte direkt im Browser testbar
-  **Postman-Collection** im Ordner [`postman/`](postman/) – alle 7 Endpunkte mit automatischen Tests, einfach importieren und ausführen
-  **Docker-Setup** – ein einziger Befehl reicht zum Start, keine lokale PHP/MySQL-Installation nötig

---

## Verwendete Technologien

| Schicht        | Technologie                       |
| -------------- | --------------------------------- |
| Backend        | PHP 8.2, Symfony 7.1              |
| ORM            | Doctrine ORM 3                    |
| Datenbank      | MySQL 8.0                         |
| Frontend       | Twig, Bootstrap 5, Vanilla JS     |
| Webserver      | Nginx 1.27                        |
| Container      | Docker & Docker Compose           |

---

## Voraussetzungen

Es muss **nur eine einzige Software** auf dem Rechner installiert sein:

- **Docker Desktop** (Windows / macOS) oder **Docker Engine + Docker Compose** (Linux)
  → Download: <https://www.docker.com/products/docker-desktop/>

Es ist **nicht nötig**, PHP, MySQL, Composer o.Ä. lokal zu installieren – alles läuft in Containern.

---

## Installation & Start

### 1. Repository klonen

```bash
git clone <REPOSITORY-URL>
cd einkaufslisten-app
```

### 2. Umgebungsvariablen vorbereiten (optional)

Für das Standard-Docker-Setup ist dies **nicht erforderlich** – alle Werte sind bereits in `docker-compose.yml` definiert.

Falls Sie eine eigene Konfiguration verwenden möchten, kopieren Sie die Vorlage:

```bash
cp .env.example .env
```

Passen Sie anschließend die Werte in der neuen `.env` Datei an.

### 3. Container starten

```bash
docker compose up -d --build
```

> Dieser Befehl baut die Container, startet MySQL, installiert die Composer-Abhängigkeiten und führt automatisch die Datenbank-Migrationen aus. Der erste Build kann 2–4 Minuten dauern.

### 4. Status überprüfen

```bash
docker compose ps
```

Alle drei Container (`einkaufslisten_php`, `einkaufslisten_nginx`, `einkaufslisten_mysql`) sollten den Status `Up` zeigen.

### 5. Anwendung aufrufen

Die Web-Oberfläche ist erreichbar unter:

 **<http://localhost:8080>**

Die REST-API ist erreichbar unter:

 **<http://localhost:8080/api/lists>**

Die interaktive **API-Dokumentation (Swagger UI)** ist erreichbar unter:

 **<http://localhost:8080/api-docs.html>**

### Container stoppen

```bash
docker compose down
```

Mit Daten löschen (komplette Zurücksetzung):

```bash
docker compose down -v
```

---

## Verwendung der Web-Oberfläche

1. **Startseite** (`/`): Übersicht aller vorhandenen Einkaufslisten. Mit einem Klick auf "Neue Liste" kann eine Liste angelegt werden – optional mit ersten Einträgen.
2. **Detailansicht** (`/lists/{id}`): Zeigt alle Einträge einer Liste. Hier können neue Artikel hinzugefügt, bestehende bearbeitet oder gelöscht und als "erledigt" abgehakt werden.

Die Oberfläche kommuniziert ausschließlich über die REST-API mit dem Backend.

---

## REST-API Dokumentation

Alle Endpunkte erwarten und liefern JSON. Basis-URL: `http://localhost:8080/api`.

> 💡 **Tipp für Reviewer:** Die API kann besonders bequem auf zwei Wegen getestet werden:
>
> 1. **Swagger UI** im Browser: [http://localhost:8080/api-docs.html](http://localhost:8080/api-docs.html) – alle Endpunkte direkt ausprobieren
> 2. **Postman-Collection** importieren – siehe Abschnitt [Postman](#postman-collection) weiter unten

### 1. POST /api/lists – Einkaufsliste erstellen

Erstellt eine neue Einkaufsliste, optional mit X initialen Einträgen.

**Request Body:**

```json
{
  "name": "Wocheneinkauf",
  "items": [
    { "name": "Milch", "quantity": 2 },
    { "name": "Brot", "quantity": 1 }
  ]
}
```

**Response 201 Created:**

```json
{
  "id": 1,
  "name": "Wocheneinkauf",
  "createdAt": "2026-05-11T12:00:00+00:00",
  "items": [
    { "id": 1, "name": "Milch", "quantity": 2, "checked": false, "createdAt": "..." },
    { "id": 2, "name": "Brot", "quantity": 1, "checked": false, "createdAt": "..." }
  ]
}
```

### 2. POST /api/lists/{id}/item – Eintrag hinzufügen

Erstellt einen neuen Eintrag in der angegebenen Liste.

**Request Body:**

```json
{ "name": "Käse", "quantity": 1 }
```

**Response 201 Created:** Die aktualisierte komplette Einkaufsliste.

### 3. GET /api/lists/{id}/items – Liste abrufen

Gibt die komplette Einkaufsliste mit allen Einträgen zurück.

**Response 200 OK:**

```json
{
  "id": 1,
  "name": "Wocheneinkauf",
  "createdAt": "...",
  "items": [ ... ]
}
```

### 4. GET /api/lists/{id}/items/{itemId} – Einzelnes Item

Gibt ein einzelnes Item der Liste zurück.

**Response 200 OK:**

```json
{ "id": 2, "name": "Brot", "quantity": 1, "checked": false, "createdAt": "..." }
```

### 5. PUT /api/lists/{id}/items/{itemId} – Item aktualisieren

Aktualisiert ein einzelnes Item. Alle Felder sind optional.

**Request Body:**

```json
{ "name": "Vollkornbrot", "quantity": 2, "checked": true }
```

**Response 200 OK:** Das aktualisierte Item.

### 6. DELETE /api/lists/{id} – Liste löschen

Löscht eine Einkaufsliste samt aller Einträge.

**Response 204 No Content**

### 7. DELETE /api/lists/{id}/items/{itemId} – Item löschen

Löscht ein einzelnes Item aus der Liste.

**Response 204 No Content**

### Zusätzlicher Endpunkt: GET /api/lists

Listet alle Einkaufslisten auf (wird von der Oberfläche genutzt).

### Beispiel mit curl

```bash
# Liste erstellen
curl -X POST http://localhost:8080/api/lists \
  -H "Content-Type: application/json" \
  -d '{"name":"Wocheneinkauf","items":[{"name":"Milch","quantity":2}]}'

# Eintrag hinzufügen
curl -X POST http://localhost:8080/api/lists/1/item \
  -H "Content-Type: application/json" \
  -d '{"name":"Brot","quantity":1}'

# Liste abrufen
curl http://localhost:8080/api/lists/1/items

# Item aktualisieren 
curl -X PUT http://localhost:8080/api/lists/1/items/1 \
  -H "Content-Type: application/json" \
  -d '{"checked":true}'

# Item löschen
curl -X DELETE http://localhost:8080/api/lists/1/items/1

# Liste löschen
curl -X DELETE http://localhost:8080/api/lists/1
```

---

## Postman Collection

Im Ordner `postman/` befindet sich eine fertige Collection mit allen 7 Endpunkten inkl. automatischer Tests.

### Import in Postman

1. Postman öffnen → **File → Import**
2. Datei `postman/Einkaufslisten-API.postman_collection.json` auswählen
3. Die Collection erscheint in der Seitenleiste

### Verwendung

Die Requests sind so aufgebaut, dass sie **in der Reihenfolge 1 → 8** ausgeführt werden können:

1. **POST /lists** legt eine Liste an und speichert automatisch `listId` und `itemId` als Collection-Variable
2. Alle folgenden Requests nutzen diese Variablen
3. Jeder Request enthält automatische **Tests** (sichtbar im "Tests"-Tab nach Ausführung)

Mit **"Run collection"** in Postman können alle Endpunkte auf einmal getestet werden.

### Base-URL anpassen

Die Variable `baseUrl` ist standardmäßig auf `http://localhost:8080` gesetzt. Bei Bedarf in den Collection-Variablen anpassen.

---

## Swagger UI (Interaktive API-Dokumentation)

Die Anwendung enthält eine eingebettete Swagger-UI:

🔗 **<http://localhost:8080/api-docs.html>**

Dort können alle Endpunkte direkt im Browser ausgeführt werden ("Try it out"). Die zugrundeliegende OpenAPI-Spezifikation befindet sich unter:

🔗 **<http://localhost:8080/openapi.yaml>**

---

## Datenmodell

Es gibt zwei Tabellen mit einer 1:n-Beziehung:

```
┌──────────────────────┐         ┌────────────────────────────┐
│   shopping_list      │         │   shopping_list_item       │
├──────────────────────┤         ├────────────────────────────┤
│ id          INT PK   │ 1     n │ id              INT PK     │
│ name        VARCHAR  │─────────│ shopping_list_id INT FK    │
│ created_at  DATETIME │         │ name             VARCHAR   │
└──────────────────────┘         │ quantity         INT       │
                                 │ checked          BOOLEAN   │
                                 │ created_at       DATETIME  │
                                 └────────────────────────────┘
```

Beim Löschen einer `shopping_list` werden zugehörige Items per `ON DELETE CASCADE` automatisch mit entfernt.

---

## Projektstruktur

```
einkaufslisten-app/
├── bin/
│   └── console                          # Symfony CLI
├── config/                              # Symfony-Konfiguration
│   ├── packages/
│   ├── bundles.php
│   ├── routes.yaml
│   └── services.yaml
├── docker/                              # Docker-Konfiguration
│   ├── nginx/default.conf
│   └── php/Dockerfile
├── migrations/                          # DB-Migrationen
│   └── Version20250101000001.php
├── postman/                             #  Bonus: Postman Collection
│   └── Einkaufslisten-API.postman_collection.json
├── public/                              # Webroot
│   ├── index.php
│   ├── api-docs.html                    #  Bonus: Swagger UI
│   └── openapi.yaml                     #  Bonus: OpenAPI-Spec
├── src/
│   ├── Controller/
│   │   ├── FrontendController.php       # HTML-Oberfläche
│   │   └── ShoppingListApiController.php # REST-API
│   ├── Entity/
│   │   ├── ShoppingList.php             # Liste (Doctrine Entity)
│   │   └── ShoppingListItem.php         # Eintrag (Doctrine Entity)
│   ├── Repository/
│   │   ├── ShoppingListRepository.php
│   │   └── ShoppingListItemRepository.php
│   └── Kernel.php
├── templates/                           # Twig-Templates
│   ├── base.html.twig
│   └── lists/
│       ├── index.html.twig              # Übersicht
│       └── detail.html.twig             # Detailansicht
├── .env                                 # Umgebungsvariablen
├── composer.json
├── docker-compose.yml
└── README.md
```

---

## Hilfreiche Befehle

### Container-Verwaltung

```bash
# Container starten
docker compose up -d

# Container stoppen
docker compose down

# Logs ansehen
docker compose logs -f php
docker compose logs -f nginx

# In den PHP-Container einsteigen
docker compose exec php bash
```

### Symfony-Befehle (im Container)

```bash
# Alle Routen anzeigen
docker compose exec php php bin/console debug:router

# Cache leeren
docker compose exec php php bin/console cache:clear

# Migrationen erneut ausführen
docker compose exec php php bin/console doctrine:migrations:migrate

# Datenbank-Schema überprüfen
docker compose exec php php bin/console doctrine:schema:validate
```

---

## Fehlerbehebung

### Port 8080 oder 3306 ist bereits belegt

Passen Sie in `docker-compose.yml` die Port-Mappings an, z.B.:

```yaml
ports:
  - "8081:80"      # nginx
  - "3307:3306"    # mysql
```

### "Could not resolve host: database"

Die MySQL-Verbindung scheitert. Überprüfen Sie, ob der `database`-Container läuft:

```bash
docker compose ps
docker compose logs database
```

### Komplettes Zurücksetzen

```bash
docker compose down -v        # Container + Volumes löschen
docker compose up -d --build  # Neu bauen und starten
```

### Composer-Abhängigkeiten neu installieren

```bash
docker compose exec php composer install
```

---

## Lizenz

Dieses Projekt wurde als Bewerbungsaufgabe erstellt.
