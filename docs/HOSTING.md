# Hosting: Netlify (chosen host)

The website is a static site, so it runs on Netlify's free plan with no
servers or databases to manage. Vercel also works (see the end), but use
**one** host only, so there is never a second "live" copy of the site.

## Costs

| Item | Cost |
| --- | --- |
| Netlify Free plan | $0. Commercial use is allowed. Includes 300 credits a month. |
| What uses credits | Each live (production) deploy uses about 15 credits; visitor traffic about 10 credits per GB. A typical month for this site (a few updates, a few thousand visits) stays well inside 300. |
| If credits run out | On the free plan the site pauses until the next month. Upgrade to Netlify Personal (about $9 a month, 1,000 credits) if traffic grows. |
| Custom domain | Your existing domain works; renewal is paid to your domain registrar as now. HTTPS certificate is free. |
| Fonts, code, icons | Free (open licences). |

Netlify changes plans from time to time. Check
<https://www.netlify.com/pricing/> before upgrading.

## One-time setup (about 10 minutes, owner)

1. Go to <https://app.netlify.com/signup> and choose **Sign up with GitHub**,
   using the GitHub account that owns this repository.
2. Choose **Add new project → Import an existing project → GitHub**.
   Allow Netlify to access the repository **Gears-of-thor-** when GitHub asks.
3. Pick the repository. Netlify reads `netlify.toml`, so the build settings
   fill themselves in (build command `npm run build`, publish directory `dist`).
   - **Branch to deploy:** `main` (the live branch). If Claude has not created
     `main` yet, choose `claude/wonderful-tesla-t9n1fa` for now and switch it
     to `main` later in **Project configuration → Build & deploy → Branches
     and deploy contexts**.
4. Give the project a name such as `got-fitnezz` (the address becomes
   `got-fitnezz.netlify.app`) and press **Deploy**.
5. Turn on previews for every branch: **Project configuration → Build &
   deploy → Branches and deploy contexts → Configure → Branch deploys: All**.
   Each branch Claude pushes then gets its own preview address.
6. Tell Claude the project name, so it can give you preview links and check
   the live site.

## How changes go live

1. You ask Claude for a change.
2. Claude works on a separate branch, checks the site, pushes it and gives you
   a preview link like `https://<branch>--got-fitnezz.netlify.app`.
3. You say "publish" (or "make it live").
4. Claude merges the change into `main`; Netlify publishes it in about a
   minute; Claude confirms the live deploy succeeded.

Previews are hidden from Google automatically.

## Connecting your domain (when ready)

Netlify → your project → **Domain management → Add a domain**, then follow the
DNS instructions it shows (usually changing records where you bought the
domain, for example in Hostinger's hPanel → Domains → DNS). After it works,
ask Claude to set `SITE_URL` so links and search results use your domain.

## Undoing a change

Netlify → **Deploys** → open the last good deploy → **Publish deploy**. The
live site switches back immediately. Then ask Claude to fix or revert the
change in GitHub.

## Vercel instead (only if you choose it instead of Netlify)

The project also deploys on Vercel with no code changes (`vercel.json` is
included). Note that Vercel's free Hobby plan does not allow commercial
websites; a business site needs Vercel Pro (about $20 per month).
