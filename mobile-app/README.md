# TNF Today — Android (Capacitor)

This folder wraps the live site `https://tnftoday.com` in a native Android WebView with the `TNFTodayCapacitor/1.0` user-agent so the app shell (bottom tabs, loader, etc.) activates.

## Prerequisites

- Node.js 20+
- Android Studio (latest stable)
- JDK 17 (bundled with Android Studio)

## First-time setup

```bash
cd mobile-app
npm install
npx cap add android
npm run cap:patch
npx cap sync android
npx cap open android
```

## Day-to-day

After Laravel/Vite changes that affect the **website** (not this shell), you usually only need to rebuild on the server. For native Android config changes:

```bash
cd mobile-app
npx cap sync android
npx cap open android
```

## Build signed `.aab` in Android Studio

1. Open the project: `npx cap open android`
2. **Build → Generate Signed App Bundle / APK**
3. Select **Android App Bundle**
4. Create or choose a keystore (store passwords safely — cannot be recovered)
5. Select **release** build variant
6. Finish — output: `android/app/release/app-release.aab`

Upload that file to Google Play Console.

## QA before release

On a real Android device (or emulator):

- App loads `tnftoday.com` with bottom tab bar
- Login works (reviewer account)
- ePaper opens and scrolls
- Profile & delete account page opens from My Account
- Back button behaves reasonably

Browser QA without Android Studio: `https://tnftoday.com?tnf_app=1`

## App ID

`com.tnftoday.app` — change in `capacitor.config.ts` only before first Play Store upload.
