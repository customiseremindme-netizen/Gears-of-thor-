# Putting the website live on Hostinger

This website is a small PHP application with a MySQL database. That is the standard setup every Hostinger **web hosting** and **cloud hosting** plan supports, so no extra services or paid add-ons are needed.

## 1. Check that your Hostinger plan can run it

I could not log in to your Hostinger account, so please confirm your plan type. It takes two minutes.

| Hostinger product | Works? | Why |
| --- | --- | --- |
| **Web Hosting** (for example Premium or Business) | ✅ Yes | PHP 8, MySQL databases, File Manager and Git deployment are included. |
| **Cloud Hosting** (Startup, Professional, …) | ✅ Yes | Same tools, more power. |
| **VPS** | ✅ Yes | Needs a web server set up (see the end of this page). |
| **Website Builder** or **Horizons** (AI builder) plans | ❌ No | These have no File Manager, databases, PHP settings or Git, so custom websites cannot be installed. |

**How to check:** sign in to hPanel → **Websites** → **Manage** next to your site. If the left menu shows **File Manager**, **Databases** and **Advanced → PHP Configuration**, your plan works. After you upload the files, the installer runs a full **hosting check** and tells you if anything is missing.

You do **not** need Node.js hosting. Hostinger offers Node.js only on Business and Cloud plans, but this website uses PHP, which runs on all web and cloud plans.

### Requirements (for reference)

- PHP **8.1 or newer** (8.3 recommended) with the `pdo_mysql`, `gd`, `mbstring` and `fileinfo` extensions. They are on by default at Hostinger.
- One MySQL / MariaDB database.
- Apache or LiteSpeed with `.htaccess` support (standard at Hostinger).
- About 50 MB of disk space, plus space for your photos and videos.
- A free SSL certificate (HTTPS), included with Hostinger plans.

## 2. Costs

There are **no extra running costs** beyond your Hostinger plan and domain renewal:

| Item | Cost |
| --- | --- |
| Website and dashboard | Included (no licences or subscriptions) |
| Fonts (Archivo, self-hosted) | Free, open licence |
| SSL certificate (HTTPS) | Free in hPanel |
| Email alerts for new enquiries | Free, uses your hosting’s email |
| Google Maps directions link | Free, no API key needed |
| Cloudflare Turnstile spam check (optional) | Free |

Nothing in the website charges per visitor or per enquiry.

## 3. Create the database

1. hPanel → **Databases → MySQL Databases**. (Can’t find it? Type “MySQL” in the hPanel search bar.)
2. Create a new database with a short name such as `gym`. If the form also asks for a username and password, use `gym` and a strong password.
3. Newer hPanel accounts create the database user automatically and do not ask for a password. In that case, set one: next to the new database’s user, open its options (**⋮** or **›**) → **Change password** → type or generate a strong password → save.
4. Write down three things exactly as hPanel shows them: the **full** database name and the **full** username (Hostinger adds a prefix, for example `u123456789_gym`), and the password. The host is `localhost`.

## 4. Upload the website

The files must end up **directly inside `public_html`**: you should see `.htaccess`, `app`, `public` and `storage` in `public_html`, not inside a sub-folder.

### Upload the ZIP file (recommended)

1. hPanel → **Files → File Manager** → open `public_html`.
2. Delete Hostinger’s placeholder files there (such as `default.php`, `index.html` or `index.php`).
3. Press **Upload** and choose `got-fitnezz-website.zip`.
4. Right-click the ZIP → **Extract**, and extract it into `public_html` itself (no new folder name).
5. Check that `.htaccess`, `app`, `public` and `storage` are now directly in `public_html`. If they are inside an extra folder, open that folder, select everything (including `.htaccess`), **Move** it to `public_html`, then delete the empty folder.
6. Delete the ZIP file.

### Alternative: copy it from GitHub

hPanel → **Advanced → Git** can copy the website straight from GitHub (**Connect with GitHub**, choose the repository and branch, deploy into the empty `public_html`), and can then update it automatically every time the code changes.

Only use this after checking that it keeps your settings and photos. Hostinger replaces the files in the folder on each deployment, and its documentation does not say whether files that are not part of the code are kept: `storage/config.php` (your database connection) and the photos in `public/uploads`. Test it first on a spare subdomain: install, upload a photo, deploy a small code change, and confirm the photo and dashboard are still there.

## 5. Run the installer

1. Visit your domain. You will be taken to the **Set up your website** page.
2. **Hosting check:** everything should be green. Fix any red item using the hint shown (usually choosing PHP 8.3 under **Advanced → PHP Configuration**).
3. **Setup code:** for security, open File Manager → `public_html/storage/setup-code.txt`, copy the code and paste it in. This proves you control the hosting, so nobody else can install the site before you.
4. **Database:** keep **MySQL** selected, then enter the full database name, username and password from step 3. The host stays `localhost`.
5. **Your login:** your name, email and a password of at least 10 characters. Check **Website address** shows your domain (ideally starting with `https://`).
6. Press **Install website**, then sign in at `yourdomain.com/admin`.

The installer adds your logo, sharing image and all starting content. It then locks itself and deletes the setup code.

## 6. Switch on HTTPS

1. hPanel → **Security → SSL**: make sure the free SSL is installed for your domain (it usually is already).
2. Switch on **Force HTTPS**, so visitors always get the secure version.
3. In the dashboard, open **Settings** and check **Your website address** starts with `https://`.

## 7. Recommended settings

- **Bigger uploads (for the hero video):** hPanel → **Advanced → PHP Configuration → PHP options**. Set `upload_max_filesize` to at least `64M` and `post_max_size` slightly higher.
- **Email alerts:** create an address such as `website@yourdomain.com` in hPanel → **Emails**, then add it in dashboard → **Settings → Send from**, and your own address under **Send alerts to**. Press **Send a test email**.
- **Backups:** Hostinger keeps automatic backups (hPanel → **Files → Backups**). Business and Cloud plans back up daily; Premium weekly.

## 8. Updating the website code later

- **With a ZIP:** ask Claude for the change; Claude updates the code on GitHub and prepares a new `got-fitnezz-website.zip`. Upload it to `public_html` and extract it over the old files, choosing to replace them. Never delete `storage/config.php` or `public/uploads`.
- **With Git (once tested as described in step 4):** the update goes live when the change reaches the deployed branch on GitHub, or when you press **Deploy** in hPanel → Advanced → Git.

Your content, photos and enquiries are stored in the database and in `public/uploads`, which updates never touch. Any database changes in a new version are applied automatically on the next visit.

## 9. Troubleshooting

| Problem | Fix |
| --- | --- |
| “This website needs PHP 8.1 or newer” | hPanel → Advanced → PHP Configuration → choose PHP 8.3. |
| Home page works but `/admin` says page not found | The hidden `.htaccess` file was not uploaded. Upload the files again, including `.htaccess`. |
| Installer says “storage folder is not writable” | File Manager → right-click `storage` (and `public/uploads`) → Permissions → `755`. |
| Photo upload fails as “bigger than your hosting allows” | Raise `upload_max_filesize` (step 7) or choose a smaller file. |
| No enquiry emails | Check the spam folder; press **Send a test email**; use a “Send from” address on your own domain. Enquiries are always saved in the dashboard regardless. |
| Moved to a new domain | Dashboard → Settings → Website address. |
| Nobody can sign in | Use the **Forgotten your password?** link on the sign-in page (needs File Manager access). |
| Something else | Errors are written to `storage/logs/app-YYYY-MM.log`. |

## For developers

- **Layout:** the repository root goes in `public_html`. The root `.htaccess` sends every request into `public/`, and `app/`, `storage/`, `docs/`, `bin/` and `tests/` are refused (each also has its own deny-all `.htaccess`). If you can set the document root yourself (VPS, cloud, local), point it at `public/`.
- **Secrets:** `storage/config.php` (written by the installer, file mode 640) holds the database password and a random application key. It is git-ignored. The optional Turnstile secret is stored in the database and is never sent to the browser.
- **Uploads:** `public/uploads` refuses scripts (`.php`, `.html`, `.svg`, …) through its own `.htaccess`. Uploaded images are re-encoded with GD, so EXIF and GPS data is removed. Videos are type-checked with `fileinfo`.
- **Local development:**
  ```
  php bin/install.php --db=sqlite --email=you@example.com --password="a long password"
  php -S 127.0.0.1:8000 -t public public/index.php
  ```
- **Tests:** `php tests/unit.php` and `bash tests/e2e/run.sh` (needs Node.js and Playwright). Set `DB=mysql DB_NAME=… DB_USER=… DB_PASS=…` to run the browser tests against MySQL.
- **Nginx (VPS):** set `root /path/to/site/public;` and `try_files $uri /index.php?$query_string;`, pass `.php` to PHP-FPM, and add `location ~* ^/uploads/.*\.(php|phtml|html?|svg|js)$ { deny all; }`.

Sources used to check Hostinger’s plan features (October 2026): [Node.js hosting options](https://www.hostinger.com/support/?p=8903), [Deploy a Git repository](https://www.hostinger.com/support/1583302-how-to-deploy-a-git-repository-in-hostinger/), [Website Builder vs other platforms](https://www.hostinger.com/support/6618476-what-s-the-difference-between-hostinger-website-builder-and-other-content-management-systems/), [PHP upload size](https://www.hostinger.com/in/tutorials/?p=748), [Git deployment](https://docs.hostinger.com/websites/git), [Databases](https://docs.hostinger.com/websites/databases).
