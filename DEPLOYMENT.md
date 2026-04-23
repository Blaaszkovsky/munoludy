# Wdrożenie produkcyjne — Munoludy

Instrukcja dla serwera z SSH (VPS/dedykowany). Scenariusz: Apache/Nginx + PHP-FPM + MySQL, Linux, domena `ml.muno.pl` z certyfikatem SSL.

Jeśli masz hosting współdzielony bez SSH (cPanel/DirectAdmin/FTP), zobacz sekcję [Wdrożenie na hostingu współdzielonym](#wdrozenie-na-hostingu-wspoldzielonym) na końcu.

---

## 0. Wymagania serwera

- **PHP 8.3+** (`php -v`) z rozszerzeniami: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `json`, `mbstring`, `openssl`, `pcre`, `pdo`, `pdo_mysql`, `tokenizer`, `xml`, `gd` lub `imagick`, `zip`.
- **MySQL 8.x** lub **MariaDB 10.6+** z kolacją `utf8mb4_unicode_ci`.
- **Composer 2.x**.
- **Node.js 20+** i npm (do jednorazowego builda assetów; potem możesz go odinstalować).
- **Git**.
- **Apache z mod_rewrite** lub **Nginx**.
- Certyfikat SSL (Let's Encrypt wystarczy).
- Dostęp SSH, możliwość uruchamiania `php artisan` i `composer`.

---

## 1. Przygotowanie repozytorium

### Jeśli jeszcze nie masz zdalnego gita

Projekt jest w lokalnym git w `d:\WWW\muno\munoludy\ml.muno.pl\`. Utwórz prywatne repo (GitHub/GitLab/Bitbucket) i wypchnij:

```bash
# z lokalnej maszyny, w katalogu ml.muno.pl
git remote add origin git@github.com:<twoja-organizacja>/munoludy.git
git push -u origin master
```

Sprawdź, czy w `.gitignore` są: `.env`, `.env.production`, `/vendor`, `/node_modules`, `/public/storage`, `/storage/*.key` — powinny już być.

### Na serwerze

```bash
cd /var/www
sudo mkdir munoludy && sudo chown $USER:$USER munoludy
cd munoludy
git clone git@github.com:<twoja-organizacja>/munoludy.git .
```

---

## 2. Zainstaluj zależności

```bash
# Composer bez dev-deps (brak Pesta, Telescope itp. w produkcji)
composer install --no-dev --optimize-autoloader --no-interaction

# Node tylko do builda
npm ci
npm run build
# po buildzie katalogi node_modules możesz usunąć jeśli chcesz:
# rm -rf node_modules
```

---

## 3. Baza danych

Na serwerze MySQL:

```sql
CREATE DATABASE munoludy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'munoludy'@'localhost' IDENTIFIED BY 'bardzo-mocne-haslo';
GRANT ALL PRIVILEGES ON munoludy.* TO 'munoludy'@'localhost';
FLUSH PRIVILEGES;
```

---

## 4. Plik `.env` dla produkcji

```bash
cp .env.example .env
nano .env   # albo vim/edytor
```

Ustaw:

```dotenv
APP_NAME="Munoludy"
APP_ENV=production
APP_KEY=                           # wygenerujemy za chwilę
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
DB_DATABASE=munoludy
DB_USERNAME=munoludy
DB_PASSWORD=bardzo-mocne-haslo

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=true         # WAŻNE: tylko HTTPS
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

CACHE_STORE=database
QUEUE_CONNECTION=sync
BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local

MAIL_MAILER=log                    # Laravel nie wysyła maili — user.com to robi

# Munoludy
ADMIN_PANEL_PATH=                  # pusty — munoludy:install wygeneruje losowy

USER_COM_BASE_URL=https://kicket.user.com
USER_COM_API_KEY=<twoj-64-znakowy-klucz-z-kicket.user.com>
USER_COM_VOTED_TAG_NAME=munoludy2026_voted
USER_COM_MARKETING_ATTRIBUTE="Marketing email"

TURNSTILE_SITE_KEY=<site-key-z-cloudflare>
TURNSTILE_SECRET_KEY=<secret-key-z-cloudflare>
```

Wygeneruj APP_KEY:

```bash
php artisan key:generate
```

---

## 5. Inicjalizacja aplikacji

```bash
php artisan munoludy:install
```

Co zrobi:
- Uruchomi migracje (`migrate --force`).
- Zasieje bazę (edycja `munoludy-2026`, pytania, treści, role `super_admin` + `editor`).
- Pobierze najnowszą listę disposable domen (lub użyje tej z repo).
- Wygeneruje losowy slug admin panelu do `.env` (`ADMIN_PANEL_PATH`).
- Zapyta, czy utworzyć pierwsze konto admina — **TAK**, podaj imię, e-mail, hasło (min. 8 znaków).

Zapisz sobie URL admina: **`https://ml.muno.pl/<ADMIN_PANEL_PATH>`** (wartość zobaczysz w `.env`).

---

## 6. Uprawnienia plików

```bash
# user www-data to typowy user Apache/Nginx na Debian/Ubuntu
# na RHEL/CentOS/AlmaLinux: apache albo nginx
sudo chown -R $USER:www-data /var/www/munoludy
sudo find /var/www/munoludy -type f -exec chmod 644 {} \;
sudo find /var/www/munoludy -type d -exec chmod 755 {} \;
sudo chmod -R ug+rwx storage bootstrap/cache
```

---

## 7. Optymalizacja produkcyjna

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan filament:optimize
php artisan storage:link
```

Zalecana jednorazowa optymalizacja:

```bash
php artisan optimize
```

---

## 8. Konfiguracja serwera WWW

### Apache (preferowane, bo masz już `.htaccess`)

Plik `/etc/apache2/sites-available/ml.muno.pl.conf`:

```apache
<VirtualHost *:80>
    ServerName ml.muno.pl
    Redirect permanent / https://ml.muno.pl/
</VirtualHost>

<VirtualHost *:443>
    ServerName ml.muno.pl
    DocumentRoot /var/www/munoludy/public

    SSLEngine on
    SSLCertificateFile      /etc/letsencrypt/live/ml.muno.pl/fullchain.pem
    SSLCertificateKeyFile   /etc/letsencrypt/live/ml.muno.pl/privkey.pem

    <Directory /var/www/munoludy/public>
        AllowOverride All
        Require all granted
        Options -Indexes -MultiViews +FollowSymLinks
    </Directory>

    <Directory /var/www/munoludy>
        Require all denied
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/munoludy-error.log
    CustomLog ${APACHE_LOG_DIR}/munoludy-access.log combined

    # Wymuś HSTS tylko gdy jesteś pewien, że HTTPS działa bezbłędnie
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
</VirtualHost>
```

Aktywacja:

```bash
sudo a2enmod rewrite headers ssl
sudo a2ensite ml.muno.pl.conf
sudo apache2ctl configtest
sudo systemctl reload apache2
```

Plik `public/.htaccess` jest już w repo z hardeningiem (X-Frame-Options, X-Content-Type-Options, blokada dostępu do `.env`, blokada AI-botów po User-Agent).

### Nginx (alternatywa)

Plik `/etc/nginx/sites-available/ml.muno.pl`:

```nginx
server {
    listen 80;
    server_name ml.muno.pl;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name ml.muno.pl;

    root /var/www/munoludy/public;
    index index.php;

    ssl_certificate     /etc/letsencrypt/live/ml.muno.pl/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/ml.muno.pl/privkey.pem;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(env|git|ht) { deny all; }
    location ~* \.(log|bak|sql|ini)$ { deny all; }

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    # AI boty blokowane przez User-Agent (Nginx wymaga map w /etc/nginx/conf.d/aibots.conf
    # lub prostego regexa tutaj — patrz niżej)
}
```

---

## 9. SSL (Let's Encrypt)

```bash
sudo apt install certbot python3-certbot-apache   # lub ...-nginx
sudo certbot --apache -d ml.muno.pl               # lub --nginx
```

Auto-odnowienie działa przez cron Certbota automatycznie.

---

## 10. Cloudflare Turnstile

1. Zaloguj się do [dash.cloudflare.com](https://dash.cloudflare.com) → Turnstile → Add Site.
2. Dodaj domenę `ml.muno.pl`, tryb "Managed" (domyślny).
3. Skopiuj **Site Key** i **Secret Key** do `.env`:
   ```
   TURNSTILE_SITE_KEY=0x4AA...
   TURNSTILE_SECRET_KEY=0x4AA...
   ```
4. Na landing page widget wyświetli się automatycznie (ma fallback: jeśli klucze puste, weryfikacja jest pomijana — dlatego lokalnie działa bez konfiguracji).

Po zmianie `.env`:

```bash
php artisan config:cache
```

---

## 11. Integracja user.com

W `.env` upewnij się, że masz:

```
USER_COM_BASE_URL=https://kicket.user.com
USER_COM_API_KEY=<klucz z panelu user.com>
```

W user.com musisz mieć:
- **Listę ID 17** z wypełnioną „Mailing list" (nazwa niezbyt istotna). ID jest konfigurowalne w panelu: **Edycje → Munoludy 2026 → ID listy** — jeśli masz inny ID, zmień tam.
- **Tag `munoludy2026_voted`** (utworzony automatycznie przy pierwszym głosie, ale najlepiej utwórz ręcznie z góry).
- **Atrybut `Marketing email`** (boolean).
- **Atrybuty `munoludy2026_link` i `munoludy2026_kod`** (string).
- **Automation** dla nowego kontaktu na liście 17 → wysyła mail z linkiem i kodem (używa pól `munoludy2026_link` / `munoludy2026_kod`).
- **Automation** dla tagu `munoludy2026_voted` → wysyła mail z podziękowaniem.

---

## 12. Test produkcji

1. Otwórz `https://ml.muno.pl/` — powinien pokazać się landing z Turnstile widgetem (jeśli klucze ustawione).
2. Wpisz prawdziwy e-mail (najlepiej swój) + zgoda na politykę prywatności + checkbox marketingu → Wyślij.
3. Sprawdź:
   - Czy dostałeś maila (user.com automation).
   - Czy w panelu user.com kontakt ma wypełnione atrybuty `munoludy2026_link`, `munoludy2026_kod`, `Marketing email = true` i jest na liście 17.
   - Czy w panelu Munoludy (`/<ADMIN_PANEL_PATH>`) w **Uczestnicy** widzisz nowy rekord.
4. Kliknij w link z maila, wpisz kod, wypełnij formularz, wyślij.
5. Sprawdź:
   - Ekran „Dziękujemy!" z animacjami.
   - W panelu: **Oddane głosy** → nowy rekord z punktami.
   - W user.com: kontakt ma tag `munoludy2026_voted`.
   - Ewentualnie dostaniesz drugiego maila (podziękowanie).

---

## 13. Monitoring

- Log aplikacji: `storage/logs/laravel.log` (rotacja dzienna, bo `LOG_STACK=daily`).
- Log serwera: `/var/log/apache2/munoludy-error.log` lub `/var/log/nginx/error.log`.
- Szybki podgląd:
  ```bash
  tail -f storage/logs/laravel-$(date +%Y-%m-%d).log
  ```

---

## 14. Backup

Minimum raz dziennie:

```bash
# cron wpis: crontab -e
0 3 * * * mysqldump -u munoludy -p'HASLO' munoludy | gzip > /backup/munoludy-$(date +\%F).sql.gz
```

Folder `/backup` powinien być poza `public/`, najlepiej na osobnym wolumenie.

---

## 15. Aktualizacje w przyszłości

Gdy wprowadzasz zmiany lokalnie i chcesz wgrać na produkcję:

```bash
# lokalnie
git add -A
git commit -m "feat: nowa funkcja"
git push origin master

# na serwerze
cd /var/www/munoludy
git pull
composer install --no-dev --optimize-autoloader --no-interaction
npm ci && npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:optimize
```

Sensowne jest zrobić prosty skrypt `deploy.sh`:

```bash
#!/usr/bin/env bash
set -e
cd /var/www/munoludy
git pull
composer install --no-dev --optimize-autoloader --no-interaction
npm ci --silent
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
php artisan filament:optimize
echo "Deploy OK."
```

---

## 16. Typowe problemy

| Problem | Rozwiązanie |
|---------|-------------|
| `500` po deployu | `tail -f storage/logs/laravel-*.log`, najczęściej zapomniany `storage` chmod lub brakujący klucz w `.env` |
| Panel admina `/admin` daje 404 | Zgodnie z założeniem — panel jest pod slugiem z `.env` (`ADMIN_PANEL_PATH`), nie pod `/admin` |
| Linki pokazują `http://` zamiast `https://` | Dodaj do `.env`: `APP_URL=https://ml.muno.pl`, potem `php artisan config:cache` |
| CSS/JS 404 | Przebuduj: `npm ci && npm run build`, sprawdź uprawnienia do `public/build/` |
| user.com zwraca 401 | Zły klucz API — sprawdź w panelu user.com → Profil → API keys; upewnij się, że używasz klucza z `kicket.user.com` (nie z innej instancji) |
| user.com zwraca 404 na subscribe | Lista 17 nie istnieje — utwórz listę w user.com albo zmień ID w panelu Edycji |
| Sesja się wylogowuje po każdym kliku | Brakuje uprawnień do `storage/framework/sessions/`, albo `SESSION_SECURE_COOKIE=true` przy dostępie po HTTP zamiast HTTPS |
| 429 na rejestracji | Limit 10/h na (IP+email) + 30/h na IP. Lokalnie wyłączony (`APP_ENV=local`). Reset: `php artisan cache:clear` |

---

## Wdrożenie na hostingu współdzielonym

Jeśli nie masz SSH (np. cPanel/DirectAdmin):

1. Na lokalnej maszynie zbuduj projekt:
   ```bash
   composer install --no-dev --optimize-autoloader
   npm ci && npm run build
   ```
2. Przez FTP wgraj **cały katalog** do serwera (zawartość wszystkiego poza `.git`, `node_modules`, `.env`).
3. Utwórz `.env` przez panel hostingu (File Manager → Nowy plik) — użyj wartości z sekcji 4.
4. W phpMyAdmin hostingu utwórz bazę (sekcja 3).
5. W cPanel/DirectAdmin skieruj domenę tak, żeby DocumentRoot był w `public/` (najczęściej opcja „Document Root" w zarządzaniu domeną).
6. Jeśli hosting nie pozwala na zmianę DocumentRoot, musisz przenieść zawartość `public/*` do `public_html/`, a wszystko inne do katalogu wyżej — i edytować `public_html/index.php`, żeby wskazywał na właściwe ścieżki (`../bootstrap/app.php`). To hack, nieładny, ale działa.
7. Jeśli hosting ma „PHP Selector" lub „Terminal", uruchom z niego:
   ```
   php artisan key:generate
   php artisan munoludy:install
   php artisan storage:link
   php artisan config:cache
   ```
   Jeśli nie ma terminala — musisz skontaktować się z pomocą hostingu, żeby uruchomili te komendy ręcznie, albo wgrać gotowy już `.env` z wygenerowanym `APP_KEY` z localu (UWAGA: klucz lokalny vs produkcyjny powinny być różne).
