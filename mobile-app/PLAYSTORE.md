# TNF Today — Google Play Store checklist

Use this after deploying the Laravel site and building the Android app.

## 1. On production VPS (run once)

```bash
cd /home/tnftoday/htdocs/tnftoday.com
git pull origin main
npm run build
php artisan optimize:clear
php artisan tnf:sync-legal-pages
php artisan tnf:create-play-reviewer --email=reviewer@tnftoday.com --password='ChooseAStrongPassword123!'
```

Save the reviewer email and password for Play Console.

## 2. Play Console — App content

| Item | Value |
|------|--------|
| Privacy policy URL | `https://tnftoday.com/privacy-policy` |
| App access | Restricted — provide reviewer credentials |
| Ads | No (unless you add ads later) |
| Account deletion | In-app: My Account → Profile & delete account |

### Sign-in instructions for Google reviewers

```
1. Open the TNF Today app.
2. Tap Account → Sign In.
3. Email: reviewer@tnftoday.com
4. Password: (your chosen password)
5. Browse Home, ePaper, and Videos.
6. To test account deletion: My Account → Profile & delete account (do not delete during review unless testing on a copy).
```

## 3. Data safety (typical answers for TNF Today)

| Data type | Collected | Shared | Purpose |
|-----------|-----------|--------|---------|
| Name | Yes | No | Account |
| Email | Yes | No | Account / login |
| User IDs | Yes | No | Account |
| Photos / videos (submissions) | Optional | No | Member submissions |
| App interactions | Optional | No | Service operation |
| Device identifiers | Only if push enabled | No | Push notifications |

- Data encrypted in transit: **Yes** (HTTPS)
- Users can request deletion: **Yes** (in-app)
- Data sold: **No**

## 4. Store listing assets

| Asset | Requirement |
|-------|-------------|
| App icon | 512 × 512 PNG |
| Feature graphic | 1024 × 500 |
| Phone screenshots | Minimum 2 |
| Short description | Max 80 characters |
| Full description | Up to 4000 characters |
| Category | News & Magazines |
| Contact email | contact@tnftoday.com |

## 5. Build signed `.aab` (Android Studio)

See [README.md](./README.md) in this folder.

Output file: `android/app/release/app-release.aab`

## 6. Upload release

Play Console → **Test and release** → **Internal testing** (recommended first) → Create release → Upload `.aab` → Submit.

After internal testing on a real device, promote to **Production**.
