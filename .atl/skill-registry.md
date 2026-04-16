# Project Skill Registry — jspos-sales

This registry defines mandatory technical standards and behavioral triggers for AI agents working on this repository.

## Compact Rules (Auto-Injected)

### 🌿 Git & Branching
- **MANDATORY**: Never work directly on the `develop` or `main` branches. 
- **FEATURE BRANCHES**: Always create a new branch `feature/[short-description]` before starting ANY technical task, regardless of size.

### 🚀 Release Protocol
- **MANDATORY**: Before concluding ANY task that involves code changes, you MUST read and follow `.agent/workflows/release.md`.
- **CHANGELOG**: Never announce a release as complete without verifying the `CHANGELOG.md` entry follows the `## [X.X.X] - YYYY-MM-DD` format.
- **VERSION**: Ensure `version.txt` is updated BEFORE tagging.

### 🔄 Mobile-Web Synchronicity
- **CRITICAL**: The mobile app MUST be a mirror of the web system. 
- **VERIFICATION**: Before modifying or creating any API endpoint in `app/Http/Controllers/Api/`, you MUST read the corresponding Livewire component in `app/Livewire/` (e.g., `SalesReport.php`, `Commissions.php`) to ensure filters and business logic are identical.

### 💾 Persistence & State
- **AUTO-MIGRATE**: Any database change MUST be accompanied by a version bump in `version.txt` to trigger the `AutoMigrate` middleware.

### 📱 Mobile Compilation Protocol (Flutter)
- **SDK Path**: `C:\src\flutter\bin\flutter.bat`.
- **BUILD COMMAND**: `flutter build apk --release --split-per-abi`.
- **DISTRIBUTION**: Always pick `build/app/outputs/flutter-apk/app-arm64-v8a-release.apk`.
- **LOCATION**: Move the finalized APK to the project **ROOT**.
- **NOMENCLATURE**: `JSPOS_Mobile_vX.X.X_BreveDescripcion_SuWeb.apk`.
- **AESTHETICS**: High-premium feel (GoogleFonts.outfit, gradients, micro-animations, no basic colors).

## Skill Triggers

| Trigger Path | Mandatory Skill / Action |
|--------------|--------------------------|
| `app/Http/Controllers/Api/*` | Read corresponding `app/Livewire/*` component first. |
| `*` (on task completion) | Read and execute `.agent/workflows/release.md`. |
| `database/migrations/*` | Verify `AutoMigrate.php` compatibility and bump version. |
