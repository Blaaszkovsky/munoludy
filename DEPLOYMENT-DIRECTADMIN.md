# Wdrożenie produkcyjne — VPS z DirectAdmin

Instrukcja krok po kroku dla **serwera VPS z panelem DirectAdmin** i domeną
`ml.muno.pl` (HTTPS). Zakłada dostęp SSH jako użytkownik DirectAdmin (lub root)
oraz panel DirectAdmin do zarządzania domeną, bazą, SSL i cronami.

Repozytorium: `https://github.com/Blaaszkovsky/munoludy.git` (gałąź **`main`**).

> Stack aplikacji: Laravel 11 + Filament 3, PHP **8.2+**, MySQL/MariaDB.
> Kolejki i scheduler **nie są używane** (`QUEUE_CONNECTION=sync`, cache i sesje
> w bazie) — nie trzeba workerów ani crona Laravela. Maile wysyła **user.com**,
> nie aplikacja.

---

## 0. Wymagania

- **PHP 8.2+** (najlepiej 8.3) z rozszerzeniami: `bcmath`, `ctype`, `curl`,
  `dom`, `fileinfo`, `json`, `mbstring`, `openssl`, `pcre`, `pdo`, `pdo_mysql`,
  `tokenizer`, `xml`, `gd` (lub `imagick`), `zip`, `intl`.
- **MySQL 8.x** lub **MariaDB 10.6+** (`utf8mb4_unicode_ci`).
- **Composer 2.x** i **Git** dostępne z SSH.
- **Node.js 20+** i npm — tylko do jednorazowego builda assetów. Jeśli serwer
  go nie ma, asset zbuduj lokalnie i wgraj `public/build/` (patrz §6, wariant B).
- Serwer WWW: Apache, LiteSpeed/OpenLiteSpeed albo nginx+apache — w każdym z nich
  działa repo'wy `public/.htaccess` (mod_rewrite / LiteSpeed rewrite).

> **Najczęstszy problem na DirectAdmin:** w `php.ini` (CLI) bywają wyłączone
> `proc_open`, `exec`, `shell_exec` przez `disable_functions`, co blokuje
> Composera i część `php artisan`. Sprawdź: `php -i | grep disable_functions`.
> Jeśli są wyłączone — użyj osobnego CLI PHP bez restrykcji albo poproś
> administratora o `custom php.ini` dla użytkownika z odblokowanym `proc_open`.

---

## 1. Domena i użytkownik w DirectAdmin

1. Zaloguj się do DirectAdmin → utwórz (lub wybierz) **użytkownika** obsługującego
   `ml.muno.pl`. Domyślny katalog domeny:
   `/home/<USER>/domains/ml.muno.pl/public_html`.
2. Ustaw wersję PHP dla domeny: DirectAdmin → **Domain Setup → ml.muno.pl →
   PHP Version** (lub „Select PHP Version" / MultiPHP) → wybierz **8.2/8.3**.
3. Włącz SSH dla użytkownika (Panel administratora → Manage User → Allow SSH),
   jeśli logujesz się jako user DA.

---

## 2. Baza danych (panel DirectAdmin)

DirectAdmin → **MySQL Management → Create new Database**:

- Database: `munoludy` → realnie utworzy się jako `<USER>_munoludy`
- User: `munoludy` → realnie `<USER>_munoludy`
- Hasło: wygeneruj mocne i zapisz

Zapamiętaj **pełne** nazwy z prefiksem użytkownika — wpiszesz je do `.env`.
Sprawdź kolację — jeśli trzeba, w phpMyAdmin ustaw bazę na `utf8mb4_unicode_ci`.

---

## 3. Pobranie kodu (SSH)

Zalecenie: trzymaj aplikację **poza** `public_html`, a `public_html` zrób
katalogiem publicznym Laravela.

```bash
ssh <USER>@<IP_VPS>
cd ~/domains/ml.muno.pl

# Zarchiwizuj domyślny public_html jeśli ma jakieś pliki:
mv public_html public_html_default_$(date +%s) 2>/dev/null || true

# Pobierz repo do katalogu 'app'
git clone https://github.com/Blaaszkovsky/munoludy.git app
cd app
git checkout main
```

> Repo jest prywatne → przy `git clone` podaj login GitHub i **Personal Access
> Token** (nie hasło) albo wgraj klucz deploy SSH.

---

## 4. Punkt publiczny domeny → `app/public`

Masz dwie drogi. **Wariant A (zalecany)** — symlink `public_html` na `public/`:

```bash
cd ~/domains/ml.muno.pl
rm -rf public_html
ln -s app/public public_html
```

Jeśli DirectAdmin/serwer nie podąża za symlinkiem `public_html` (czasem polityka
hostingu), użyj **Wariantu B** — zmiana Document Root:

- DirectAdmin → **Domain Setup → ml.muno.pl** → włącz **Custom document root**
  (DA 1.6+) i ustaw: `domains/ml.muno.pl/app/public`.
- Albo Apache **Custom HTTPD Config** (Panel admina → Custom HTTPD):
  ```
  |?DOCUMENT_ROOT=/home/<USER>/domains/ml.muno.pl/app/public|
  ```
  i przeładuj: `systemctl reload httpd` (lub `lsws`/`apache2`).

**Wariant C (bez SSH do zmiany docroot, ostateczność):** przenieś zawartość
`app/public/*` do `public_html/` i w `public_html/index.php` popraw ścieżki na
`__DIR__.'/../app/bootstrap/app.php'` oraz `vendor/autoload.php`. Działa, ale
utrudnia `git pull` — preferuj A lub B.

---

## 5. Plik `.env`

```bash
cd ~/domains/ml.muno.pl/app
cp .env.example .env
nano .env
```

Ustaw produkcyjnie:

```dotenv
APP_NAME="Munoludy"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_TIMEZONE=Europe/Warsaw
APP_URL=https://ml.muno.pl

APP_LOCALE=pl
APP_FALLBACK_LOCALE=pl
APP_FAKER_LOCALE=pl_PL

LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=<USER>_munoludy
DB_USERNAME=<USER>_munoludy
DB_PASSWORD=<haslo-z-DirectAdmin>

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=true        # WAŻNE: tylko po włączeniu HTTPS (§8)
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

CACHE_STORE=database
QUEUE_CONNECTION=sync
BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local

MAIL_MAILER=log                   # maile wysyła user.com, nie aplikacja

# Munoludy
ADMIN_PANEL_PATH=                 # zostaw puste — munoludy:install wygeneruje losowy slug
MUNOLUDY_LOGO_URL=https://ml.muno.pl   # adres, pod który prowadzi logotyp w nagłówku

USER_COM_BASE_URL=https://kicket.user.com
USER_COM_API_KEY=<64-znakowy-klucz-z-kicket.user.com>
USER_COM_VOTED_TAG_NAME=munoludy2026_voted
USER_COM_MARKETING_ATTRIBUTE="Marketing email"

TURNSTILE_SITE_KEY=<site-key-cloudflare>
TURNSTILE_SECRET_KEY=<secret-key-cloudflare>
```

> `USER_COM_VOTED_TAG_NAME` oraz pola edycji (`munoludy2026_link/_kod`)
> celowo zostają z `2026` — na stronie zmieniono tylko **widoczną** nazwę na
> „Munoludy 2025", a integracja user.com (lista 17, atrybuty, automatyzacje)
> dalej używa nazw `munoludy2026_*`. Nie zmieniaj ich, dopóki nie
> przekonfigurujesz konta user.com.

---

## 6. Zależności i build assetów

**Wariant A — Node jest na serwerze:**

```bash
cd ~/domains/ml.muno.pl/app
composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build
# node_modules można potem usunąć: rm -rf node_modules
```

**Wariant B — brak Node/zbyt mały RAM na build:** zbuduj lokalnie na Windows
(WAMP), wgraj przez FTP/SCP katalog `public/build/`, a na serwerze tylko:

```bash
composer install --no-dev --optimize-autoloader --no-interaction
```

> Jeśli `composer install` rzuca błąd `proc_open()` / `exec()` is disabled —
> patrz uwaga w §0 (custom php.ini bez `disable_functions` dla CLI).

---

## 7. Inicjalizacja aplikacji

```bash
cd ~/domains/ml.muno.pl/app
php artisan key:generate
php artisan munoludy:install
```

`munoludy:install`:
- uruchomi migracje (`migrate --force`),
- zasieje edycję (slug `munoludy-2026`), pytania/kategorie i **treści stron**
  (m.in. teksty checkboxów zgód, „Sprawdź swoją skrzynkę" + podpowiedź o SPAM,
  widoczne nazwy „Munoludy 2025"),
- pobierze listę domen disposable (lub użyje wersji z repo),
- wygeneruje losowy `ADMIN_PANEL_PATH` i zapisze do `.env`,
- zapyta o pierwsze konto admina — **utwórz** (imię, e-mail, hasło min. 8 znaków).

Zapisz URL panelu: **`https://ml.muno.pl/<ADMIN_PANEL_PATH>`** (wartość jest w `.env`).
Później adminów dodajesz przez `php artisan munoludy:make-admin` lub w panelu.

> Treści są edytowalne w panelu (**Treści stron / PageContent**). Jeśli kiedyś
> zmienisz tekst w panelu, ponowne odpalenie seedera **nadpisze** go wartością
> z kodu — produkcyjnie seedera nie uruchamiaj ponownie bez potrzeby.

---

## 8. SSL i wymuszenie HTTPS (DirectAdmin)

1. DirectAdmin → **SSL Certificates** dla `ml.muno.pl` → **Let's Encrypt** →
   zaznacz `ml.muno.pl` + `www.ml.muno.pl` → **Save**.
2. Włącz **Force SSL Redirect** (DirectAdmin → Domain Setup → „Force SSL" /
   „Secure SSL") aby cały ruch szedł po HTTPS.
3. Dopiero po działającym HTTPS zostaw `SESSION_SECURE_COOKIE=true` w `.env`
   (inaczej sesja/logowanie nie zadziała po HTTP).

`public/.htaccess` z repo zawiera już hardening (X-Frame-Options,
X-Content-Type-Options, blokada `.env`, blokada części botów AI po User-Agent,
listing katalogów off). Nie blokuje Google ani preview social media.

---

## 9. Uprawnienia plików

Na DirectAdmin serwer WWW działa jako **użytkownik domeny**, więc wystarczy:

```bash
cd ~/domains/ml.muno.pl/app
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod -R ug+rwX storage bootstrap/cache
```

`.env` ma zostać poza katalogiem publicznym (jest — w `app/`, a publiczny jest
`app/public`). Upewnij się, że `app/.env` nie jest serwowany (nie jest, bo
docroot to `app/public`).

---

## 10. Optymalizacja produkcyjna

```bash
cd ~/domains/ml.muno.pl/app
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan filament:optimize
```

Po **każdej** zmianie `.env` uruchom ponownie `php artisan config:cache`.

---

## 11. Cloudflare Turnstile

1. [dash.cloudflare.com](https://dash.cloudflare.com) → Turnstile → Add Site →
   domena `ml.muno.pl`, tryb „Managed".
2. Wklej **Site Key** i **Secret Key** do `.env` (`TURNSTILE_*`).
3. `php artisan config:cache`.

**Włączanie / wyłączanie Turnstile:**

- **OBA** klucze puste (`TURNSTILE_SITE_KEY=` i `TURNSTILE_SECRET_KEY=`) →
  Turnstile jest **wyłączony**: widget się nie pokazuje, a backend pomija
  weryfikację (rejestracja działa). Tak zostawiamy „na razie".
- **OBA** klucze ustawione → Turnstile aktywny i wymagany.
- ⚠️ Ustawiony sam `TURNSTILE_SITE_KEY` bez `TURNSTILE_SECRET_KEY` na
  produkcji = **błędna konfiguracja → rejestracja jest blokowana**
  (fail-closed). Albo ustaw oba, albo wyczyść oba.

Po każdej zmianie tych zmiennych: `php artisan config:cache`.

Nawet z wyłączonym Turnstile rejestracja ma inne warstwy anty-bot: honeypot,
kontrola czasu wypełnienia formularza, rate limiting i blokada e-maili
disposable.

---

## 12. Integracja user.com

W `.env`: `USER_COM_BASE_URL=https://kicket.user.com` + `USER_COM_API_KEY`.

W koncie user.com muszą istnieć:
- **Lista ID 17** (ID konfigurowalne w panelu: Edycje → Munoludy 2025 → ID listy).
- Atrybut **`Marketing email`** (boolean).
- Atrybuty **`munoludy2026_link`**, **`munoludy2026_kod`** (string).
- Tag **`munoludy2026_voted`** (najlepiej utwórz ręcznie z góry).
- **Automation**: nowy kontakt na liście 17 → mail z linkiem i kodem
  (pola `munoludy2026_link` / `munoludy2026_kod`).
- **Automation**: tag `munoludy2026_voted` → mail z podziękowaniem.

---

## 13. Cloudflare — cache stron dynamicznych

Trasy `/` (po rejestracji), `/rejestracja` i całe `/glosowanie/*` zależą od
sesji. Aplikacja wysyła na nich nagłówki `Cache-Control: no-store, private`
oraz `CDN-Cache-Control: no-store`, więc przy **domyślnym** trybie cache
Cloudflare (Standard) działają poprawnie.

⚠️ Jeśli masz **Page Rule / Cache Rule z „Cache Everything"** (albo agresywny
APO), Cloudflare może zignorować te nagłówki i serwować z brzegu starą stronę —
objaw: po próbie wysłania głosu z pustą kategorią **nie pokazują się**
komunikaty „W każdej kategorii musisz oddać co najmniej jeden głos…" ani
„Ta kategoria wymaga co najmniej jednego głosu". Po wyłączeniu cache działa.

Napraw to w Cloudflare jedną z dróg (zalecana pierwsza):

- **Cache Rule – Bypass cache** (Caching → Cache Rules → Create):
  - Gdy: `URI Path` `starts with` `/glosowanie/` **OR** `URI Path` equals
    `/rejestracja` **OR** `URI Path` equals `/`
  - Then: **Bypass cache**
- albo **Bypass Cache on Cookie**: w regule dla „Cache Everything" dodaj
  warunek omijania cache, gdy występuje cookie `laravel_session` lub
  `munoludy_session` / `XSRF-TOKEN` (czyli dla zalogowanych/uczestników).
- albo zawęź regułę „Cache Everything" wyłącznie do statyków
  (`/build/*`, `/images/*`, `/fonts/*`), a nie do całej domeny.

Po zmianie wyczyść cache: Cloudflare → Caching → **Purge Everything**.
Nie cache'uj `/glosowanie/*` — to strony chronione kodem dostępu (cache na
CDN groziłby też wyciekiem cudzego draftu/podsumowania).

`/wyniki` można spokojnie cache'ować (publiczne, bez danych sesji).

---

## 14. Test produkcyjny (smoke test)

1. `https://ml.muno.pl/` — landing, logo MUNOLUDY (węższe, max 480px) jest
   linkiem do `MUNOLUDY_LOGO_URL`, w stopce/nagłówku „biletomat" małą literą.
2. Rejestracja: prawdziwy e-mail + checkbox zgody (z linkami „regulamin
   plebiscytu" / „polityce prywatności") + opcjonalnie marketing → Wyślij.
   Powinno pokazać „Sprawdź swoją skrzynkę!" + „Nie widzisz maila? Sprawdź
   folder SPAM."
3. Sprawdź: mail z user.com dotarł; w panelu (`/<ADMIN_PANEL_PATH>` →
   Uczestnicy) jest rekord; w user.com kontakt ma atrybuty i jest na liście 17.
4. Wejdź z linka, podaj kod, **spróbuj wysłać z pustą kategorią** → formularz
   ma się **nie wysłać**, wróci do podsumowania z komunikatem i podświetli
   brakujące kategorie. Uzupełnij wszystkie kategorie → wyślij → ekran
   „Dziękujemy!".
5. W panelu: Oddane głosy → nowy rekord; w user.com kontakt ma tag
   `munoludy2026_voted`.

---

## 15. Aktualizacje (kolejne wdrożenia)

```bash
cd ~/domains/ml.muno.pl/app
git pull origin main
composer install --no-dev --optimize-autoloader --no-interaction
npm ci && npm run build          # lub wgraj public/build/ zbudowany lokalnie
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:optimize
```

Skrypt `deploy.sh` (w `app/`, `chmod +x deploy.sh`):

```bash
#!/usr/bin/env bash
set -e
cd "$(dirname "$0")"
git pull origin main
composer install --no-dev --optimize-autoloader --no-interaction
npm ci --silent && npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
php artisan filament:optimize
echo "Deploy OK."
```

---

## 16. Backup (cron w DirectAdmin)

DirectAdmin → **Cron Jobs** → dodaj zadanie codziennie o 3:00:

```
mysqldump -u <USER>_munoludy -p'<HASLO>' <USER>_munoludy | gzip > ~/backups/munoludy-$(date +\%F).sql.gz
```

Wcześniej `mkdir -p ~/backups`. Trzymaj backupy poza katalogiem publicznym
(`~/backups`, nie w `public_html`). Rozważ też retencję (kasowanie >30 dni).

---

## 17. Typowe problemy na DirectAdmin

| Problem | Rozwiązanie |
|---------|-------------|
| `composer`/`artisan`: `proc_open() has been disabled` | CLI PHP ma `disable_functions`. Użyj innej binarki PHP lub custom `php.ini` bez `proc_open`,`exec` dla użytkownika |
| Strona pokazuje listing plików / kod PHP | Document root nie wskazuje na `app/public` — popraw §4 (symlink lub Custom document root) |
| 500 po wdrożeniu | `tail -f storage/logs/laravel-*.log`; zwykle brak `APP_KEY`, złe dane DB albo prawa do `storage/` |
| `/admin` → 404 | Zgodnie z założeniem — panel jest pod losowym slugiem z `.env` (`ADMIN_PANEL_PATH`) |
| Linki `http://` zamiast `https://` | `APP_URL=https://ml.muno.pl` + `php artisan config:cache`; włącz Force SSL w DA |
| Wylogowuje po kliknięciu | `SESSION_SECURE_COOKIE=true` przy braku HTTPS, albo brak praw do `storage/framework/sessions/` |
| CSS/JS 404 | Brak `public/build/` — zbuduj (`npm run build`) lub wgraj; sprawdź prawa |
| `open_basedir` blokuje vendor/storage | W DA dodaj ścieżki aplikacji do `open_basedir` (Custom php.ini) lub wyłącz dla domeny |
| user.com 401 | Zły `USER_COM_API_KEY` — klucz musi być z `kicket.user.com` |
| user.com 404 na subscribe | Lista 17 nie istnieje — utwórz w user.com lub zmień ID w panelu Edycji |
| 429 przy rejestracji | Limit antyspamowy (IP+e-mail). Reset: `php artisan cache:clear` |
| Brak komunikatów walidacji głosowania, znika po wyłączeniu cache CF | Cloudflare „Cache Everything" cache'uje `/glosowanie/*` — patrz §13 (Cache Rule Bypass / Bypass on Cookie) i Purge Everything |

---

## 18. Checklista przed ogłoszeniem

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` ustawiony
- [ ] HTTPS działa, Force SSL włączony, `SESSION_SECURE_COOKIE=true`
- [ ] Document root = `app/public`, `.env` niedostępny z przeglądarki
      (`https://ml.muno.pl/.env` → 403/404)
- [ ] `php artisan config:cache route:cache view:cache` wykonane
- [ ] Panel admina pod losowym slugiem, konto super-admina utworzone
- [ ] Turnstile: oba klucze ustawione (aktywny) **albo** oba puste (świadomie
      wyłączony); user.com (lista 17 + atrybuty + automatyzacje) gotowe
- [ ] Cloudflare nie cache'uje `/glosowanie/*`, `/rejestracja`, `/` po
      rejestracji (§13) — sprawdzone z włączonym cache
- [ ] Smoke test §14 przeszedł, w tym blokada wysyłki z pustą kategorią
      (komunikaty widoczne **przy włączonym** cache Cloudflare)
- [ ] Backup cron działa, katalog backupów poza `public_html`
```
