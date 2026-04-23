# Munoludy

Plebiscytowa aplikacja Muno (muzyczne podsumowania roku) zbudowana w Laravelu 11 z panelem administracyjnym Filament v3. Głosowanie jest dwuetapowe: rejestracja -> kod e-mail / token jury -> rankowane wybory -> podsumowanie wyników.

Frontend publiczny odwzorowuje projekty z `../templates/` (Figma Make), panel administracyjny korzysta z domyślnego UI Filamenta.

## Stack

- PHP 8.3+
- Laravel 11
- Filament PHP v3
- MySQL / MariaDB (lokalnie WAMP)
- Vite + Tailwind CSS v3
- Pest (test runner)

## Current state

- Phases 0-10 ukończone.
- Test suite: 34 testy (Pest) przechodzą.
- Pełny flow: rejestracja publiczna + jury, głosowanie rankowane, podsumowanie, publikacja wyników, panel administracyjny, CSV export.
- Security hardening: `.htaccess` z nagłówkami i blokadą AI botów, `robots.txt`, rate limiting na rejestracji i głosowaniu.

## Wymagania lokalne

- WAMP (Apache + MySQL) lub równoważnik
- PHP 8.3+ z rozszerzeniami: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `curl`, `gd`, `zip`, `intl`
- Composer 2+
- Node.js 20+ i npm

## Instalacja

```bash
# 1. Zainstaluj zależności
composer install
npm install

# 2. Skonfiguruj .env (skopiuj z .env.example)
cp .env.example .env
php artisan key:generate

# 3. Utwórz bazę MySQL "munoludy" (w phpMyAdmin lub CLI)

# 4. Uruchom migracje, seedery, pobierz listę disposable e-maili,
#    wylosuj tajny slug panelu admin
php artisan munoludy:install

# 5. Zbuduj assety
npm run build
```

Vhost w Apache powinien wskazywać DocumentRoot na `d:/WWW/muno/munoludy/ml.muno.pl/public` i obsługiwać hosta `muno.local` (lokalnie) lub `ml.muno.pl` (produkcja).

## Uruchomienie developerskie

```bash
# Apache przez WAMP obsłuży PHP
# Asset watcher:
npm run dev
```

Strona publiczna: `http://muno.local/`.

Panel admina: `http://muno.local/<ADMIN_PANEL_PATH>` (wartość slugu w `.env` pod `ADMIN_PANEL_PATH`).

### Konta administratorów

Zakładasz je komendą:

```bash
php artisan munoludy:make-admin
```

(prompty o imię, e-mail, hasło — możesz też przekazać przez opcje `--name --email --password --role=super_admin|editor`).

Pierwszy admin zostanie utworzony automatycznie podczas `munoludy:install`. Kolejnych dodajesz w panelu: **System → Administratorzy → Nowy administrator** (widoczne tylko dla roli `super_admin`). Role:

- `super_admin` — pełny dostęp, w tym zarządzanie administratorami.
- `editor` — dostęp do panelu bez zarządzania administratorami.

## Struktura katalogów (kluczowe)

```
app/
  Actions/           akcje domenowe (rejestracja, głosowanie)
  Console/Commands/  munoludy:install
  Filament/          resources, pages, widgets panelu
  Http/Controllers/
    Public/          LandingController, VoteController, ResultsController
  Models/
  Services/          UserCom, Turnstile, DisposableEmail, VoteSession...
resources/
  views/
    layouts/
    components/      Blade components (btn, header, footer, form-card,...)
    vote/            widoki flow głosowania
routes/web.php
storage/app/disposable-domains.txt  (download via installer)
public/.htaccess     (hardened)
public/robots.txt
```

## Testy

```bash
php artisan test
```

Obecnie 34 testy obejmujące: modele, landing, rejestrację (public + jury), flow głosowania, panel admin (access, dashboard), publikację wyników.

## Bezpieczeństwo

- `.htaccess` dodaje: `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, ukrywa `Server/X-Powered-By`.
- Blokada znanych AI crawlerów (GPTBot, ClaudeBot, CCBot, PerplexityBot i in.) zarówno na poziomie `robots.txt`, jak i User-Agent w Apache.
- Panel admin pod losowym slugiem (`ADMIN_PANEL_PATH`).
- Rate limiting: rejestracja `3/60min`, weryfikacja kodu `5/5min`, submit głosu `1/10min`.
- Walidacja disposable e-maili (offline lista z disposable-email-domains).
- Cloudflare Turnstile przy formularzach publicznych (włączane przez `TURNSTILE_*` w `.env`).

## Integracja user.com

Skonfigurowana w `config/services.php` + `App\Services\UserCom\*`. Klucze:

- `USER_COM_BASE_URL`
- `USER_COM_API_KEY`

Sync zarejestrowanych użytkowników leci w tle przez queue workera.

## Deployment

```bash
php artisan queue:work    # workera trzymaj pod Supervisor / Task Scheduler
php artisan schedule:run  # raz na minutę przez cron / Task Scheduler
```

Przed produkcją:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `SESSION_SECURE_COOKIE=true` (HTTPS)
- skonfigurowana poczta (mailer inny niż `log`)

## Przyszłe prace (future work, nieopracowane w tym wydaniu)

- **CloneEdition** — komenda artisan klonująca poprzednią edycję plebiscytu (kopia kategorii, pytań, widoków panelu z adnotacją `Edycja N+1`). YAGNI do momentu gdy będzie realnie potrzebna.
- **Sitemap generator** — `robots.txt` deklaruje `sitemap.xml`, ale plik jest statycznym stubem. Docelowo wygenerować przez `spatie/laravel-sitemap` na podstawie aktywnej edycji i publicznych tras (`/`, `/wyniki/...`).
- **Eksport wyników w innych formatach** (PDF, JSON) oraz webhook do systemów zewnętrznych.
- **Rozbudowany dashboard** (wykresy trendów, porównanie edycji).
