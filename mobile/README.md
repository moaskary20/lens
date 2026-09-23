# Lens mobile

Flutter app for the Lens marketplace (clients and vendors). Dark theme only. Brand colors: `#FF5A1F`, `#0D0D0F`, `#F2EFE9`.

## Run

Google Chrome (web):

```bash
cd mobile
flutter pub get
flutter run -d chrome
```

Android emulator:

```bash
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api
```

iOS simulator / desktop:

```bash
flutter run --dart-define=API_BASE_URL=http://127.0.0.1:8000/api
```

Laravel must be running (`php artisan serve`). The app loads `GET /api/app/bootstrap` on launch.

## Layout

```
lib/
  main.dart
  app.dart
  core/          # config, API client, theme
  features/      # splash, shell (screens come next)
```
