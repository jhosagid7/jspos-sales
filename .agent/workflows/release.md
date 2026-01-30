---
description: How to perform a release (Update version, Changelog, Git Push, Tag)
---

# Release Process

Follow these steps to release a new version of the application.

1. **Verify Version**
   - Check `c:\laragon\www\jspos-sales\version.txt`.
   - Ensure the version number is incremented correctly (e.g., v1.8.6 -> v1.8.7).

2. **Update Changelog**
   - Edit `c:\laragon\www\jspos-sales\CHANGELOG.md`.
   - Add a new header with the version and date.
   - **IMPORTANT:** Use the format `## [X.X.X] - YYYY-MM-DD`. Do NOT use `v1.8.7` or plain `1.8.7`. The System Updater regex requires brackets `[]` to detect the notes.
   - List all `Added`, `Changed`, and `Fixed` items since the last release.

3. **Commit Changes**
   - Run specific git commands to save changes.
   - Files to add: `version.txt`, `CHANGELOG.md`, and any other modified source files.
   ```powershell
   git add .
   git commit -m "Release vX.X.X: [Brief Summary]"
   ```

4. **Push to Develop**
   - Push the committed changes to the `develop` branch.
   ```powershell
   git push origin develop
   ```

5. **Tag and Release**
   - Create a git tag for the version.
   ```powershell
   git tag vX.X.X
   ```
   - Push the tag to the remote repository.
   ```powershell
   git push origin vX.X.X
   ```

6. **Notification**
   - Calculate the `walkthrough` artifact.
   - Notify the user that the release is complete.
