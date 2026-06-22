# Flutter Compilation Convention

## CRITICAL: Flutter Apps Compilation protocol

Every time you are tasked with compiling or building any of the Flutter apps in the workspace (`mobile_app/`, `mobile_bolsas_app/`, `mobile_soplados_app/`, or `mobile_vip_app/`), you MUST strictly adhere to the following rules:

1. **Read CONTEXTO_IA.md Section 9.1**: Always check `CONTEXTO_IA.md` first to confirm specific settings, paths, and package names.
2. **Path to SDK**: Use `C:\src\flutter\bin\flutter.bat` for running Flutter commands.
3. **Command Execution**:
   - DO NOT run `flutter build apk --release` directly.
   - ALWAYS run: `C:\src\flutter\bin\flutter.bat build apk --release --split-per-abi`
4. **Distribution File**:
   - Only copy the modern ARM64 APK from the build output directory (`build/app/outputs/flutter-apk/app-arm64-v8a-release.apk`) to the project **ROOT**.
   - DO NOT copy the heavy full APK (`app-release.apk`).
5. **Naming Pattern**:
   - Rename the copied APK according to the app type:
     - Bags App: `JSPOS_Mobile_Bolsas_v[Version]_[ShortDescription]_SuWeb.apk`
     - Soplados App: `JSPOS_Mobile_Soplados_v[Version]_[ShortDescription]_SuWeb.apk`
     - VIP App: `JSPOS_Mobile_VIP_v[Version]_[ShortDescription]_SuWeb.apk`
     - Main App: `JSPOS_Mobile_v[Version]_[ShortDescription]_SuWeb.apk`
