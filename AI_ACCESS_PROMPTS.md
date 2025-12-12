# 🤖 AI Access Prompts für EasyBGM

Diese Datei enthält Copy-Paste-Prompts für Claude/ChatGPT/Cursor, um auf die EasyBGM-Anwendung zuzugreifen.

---

## 🚀 Quick Start Prompt (Empfohlen)

```
Du bist ein AI-Agent der die EasyBGM-Anwendung testet.

## Einloggen

Navigiere zu dieser URL:
https://app.easybgm.de/auth/jwt/sign-in?demo=true

Nach dem automatischen Login wirst du zum Dashboard weitergeleitet.

## Deine Aufgabe

1. Erkunde das Dashboard und beschreibe die Hauptbereiche
2. Navigiere durch die Menüpunkte
3. Dokumentiere was du findest

## Hinweise

- Du bist als Demo-User eingeloggt
- Einige Funktionen (System-Einstellungen) sind eingeschränkt
- Bei Fehlern: Screenshot machen und Fehlermeldung notieren
```

---

## 🔐 Sicherer Login mit Token

```
Du bist ein AI-Agent der die EasyBGM-Anwendung testet.

## Schritt 1: Demo-Token holen

Führe diesen API-Call aus:

```bash
curl -X POST https://api.easybgm.de/api/auth/demo-token \
  -H "Content-Type: application/json" \
  -d '{"validityMinutes": 5}'
```

## Schritt 2: Mit Token einloggen

Nimm den "token" Wert aus der Response und navigiere zu:
https://app.easybgm.de/auth/jwt/sign-in?autoLoginToken=<TOKEN>

## Schritt 3: Anwendung erkunden

Nach dem Login:
1. Prüfe das Dashboard
2. Teste die Benutzerverwaltung unter /dashboard/user
3. Dokumentiere alle gefundenen Issues
```

---

## 🧪 QA Test Prompt

```
Du bist ein QA-Tester für die EasyBGM Plattform.

## Setup

Logge dich ein: https://app.easybgm.de/auth/jwt/sign-in?demo=true

## Test-Szenario: Benutzerverwaltung

1. Navigiere zu: Dashboard → Benutzer (oder /dashboard/user)

2. Teste die User-Liste:
   - [ ] Wird die Liste geladen?
   - [ ] Funktioniert die Suche?
   - [ ] Sind Pagination-Controls sichtbar?

3. Teste User-Erstellung:
   - [ ] Klicke "Benutzer hinzufügen"
   - [ ] Fülle alle Pflichtfelder aus
   - [ ] Prüfe Validierungsmeldungen
   - [ ] (Optional) Speichere den User

4. Teste User-Bearbeitung:
   - [ ] Wähle einen existierenden User
   - [ ] Ändere ein Feld
   - [ ] Prüfe ob Änderungen gespeichert werden

## Dokumentation

Für jeden Fehler notiere:
- URL wo der Fehler auftrat
- Schritte zur Reproduktion
- Erwartetes vs. tatsächliches Verhalten
- Screenshot (falls möglich)
```

---

## 🔄 Vollständiger Workflow Test

```
Du bist ein AI-Agent der einen End-to-End Workflow testet.

## Kontext

- Produktions-URL: https://app.easybgm.de
- Auto-Login: Nutze ?demo=true Parameter
- Du bist als Demo-User mit eingeschränkten Rechten eingeloggt

## Workflow: Benutzer anlegen und löschen

### Phase 1: Login
1. Navigiere zu: https://app.easybgm.de/auth/jwt/sign-in?demo=true
2. Warte bis das Dashboard vollständig geladen ist
3. Bestätige: Siehst du "Dashboard" oder "Willkommen"?

### Phase 2: Navigation
1. Finde den Menüpunkt "Benutzer" oder "Mitarbeiter"
2. Klicke darauf
3. Bestätige: Siehst du eine Liste mit Benutzern?

### Phase 3: Benutzer erstellen
1. Klicke auf "Hinzufügen" oder "+ Benutzer"
2. Fülle das Formular aus:
   - Vorname: Test
   - Nachname: AI-Agent
   - E-Mail: test-ai-TIMESTAMP@example.com (ersetze TIMESTAMP)
3. Speichere
4. Bestätige: Erscheint der neue Benutzer in der Liste?

### Phase 4: Benutzer löschen
1. Finde den gerade erstellten Benutzer
2. Klicke auf Löschen/Entfernen
3. Bestätige die Löschung
4. Bestätige: Ist der Benutzer aus der Liste verschwunden?

### Phase 5: Dokumentation
Erstelle einen Bericht mit:
- ✅ Erfolgreiche Schritte
- ❌ Fehlgeschlagene Schritte
- 🐛 Gefundene Bugs
- 💡 Verbesserungsvorschläge
```

---

## 🔧 API-Only Test (Headless)

```
Du bist ein API-Tester. Teste die EasyBGM API ohne Browser.

## Schritt 1: Demo-Token holen

```bash
curl -X POST https://api.easybgm.de/api/auth/demo-token \
  -H "Content-Type: application/json" \
  -d '{}'
```

Speichere den "token" Wert.

## Schritt 2: Token gegen JWT tauschen

```bash
curl -X POST https://api.easybgm.de/api/auth/auto-login \
  -H "Content-Type: application/json" \
  -d '{"token": "<TOKEN_AUS_SCHRITT_1>"}'
```

Speichere den "accessToken" Wert.

## Schritt 3: User-Info abrufen

```bash
curl https://api.easybgm.de/api/auth/me \
  -H "Authorization: Bearer <ACCESS_TOKEN>"
```

## Schritt 4: API erkunden

Mit dem JWT kannst du alle API-Endpoints aufrufen:

```bash
# Benutzer-Liste
curl https://api.easybgm.de/api/users \
  -H "Authorization: Bearer <ACCESS_TOKEN>" \
  -H "X-Tenant-ID: <TENANT_ID_AUS_ME_RESPONSE>"

# Notifications
curl https://api.easybgm.de/api/notifications \
  -H "Authorization: Bearer <ACCESS_TOKEN>"
```

Dokumentiere alle Responses und deren Status-Codes.
```

---

## 📍 Lokale Entwicklung

Für lokale Tests (Backend auf :8000, Frontend auf :3039):

```
Du bist ein AI-Agent der die lokale EasyBGM-Entwicklungsumgebung testet.

## URLs

- Frontend: http://localhost:3039
- Backend API: http://localhost:8000

## Login

Navigiere zu: http://localhost:3039/auth/jwt/sign-in?demo=true

## Alternative: Token-basiert

```bash
# Token holen
curl -X POST http://localhost:8000/api/auth/demo-token \
  -H "Content-Type: application/json" -d '{}'

# Mit Token einloggen
http://localhost:3039/auth/jwt/sign-in?autoLoginToken=<TOKEN>
```
```

---

## 🔑 Credentials Referenz

| Umgebung | URL | Login |
|----------|-----|-------|
| **Production** | app.easybgm.de | `?demo=true` oder Token |
| **Lokal** | localhost:3039 | `?demo=true` oder Token |

| Demo-User | Wert |
|-----------|------|
| E-Mail | demo@bgm-portal.de |
| Passwort | demo2025 |
| Rolle | Demo-User (eingeschränkt) |

---

## ⚠️ Wichtige Hinweise

1. **Tokens sind einmalig** - Jeder Token kann nur einmal verwendet werden
2. **Tokens laufen ab** - Standard: 5 Minuten
3. **Demo-User ist eingeschränkt** - Keine System-Settings, keine Integrationen
4. **Für jeden Test neuen Token** - Nicht wiederverwenden



