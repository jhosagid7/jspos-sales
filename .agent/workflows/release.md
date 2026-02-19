---
description: How to perform a release (Update version, Changelog, Git Push, Tag)
---

# Release Process

Follow these steps to correctly release a new version of the application.

## 1. Verify & Update Version
- Check `c:\laragon\www\jspos-sales\version.txt`.
- Increment the version number (e.g., `v1.8.1` -> `v1.8.2`).
- Save the file.

## 2. Update Changelog
- Open `c:\laragon\www\jspos-sales\CHANGELOG.md`.
- Add a new header with the version and date at the top of the list.
- **IMPORTANT:** Use the format `## [X.X.X] - YYYY-MM-DD`. 
  - Do NOT use `v1.8.7` or plain `1.8.7` in the header.
  - The System Updater regex requires brackets `[]` to detect the notes.
- List all `Added`, `Changed`, and `Fixed` items since the last release.

## 3. Commit Changes
- Stage ALL changes (including code updates, version file, and changelog).
- Commit with a standard message.
```powershell
git add .
git commit -m "Release vX.X.X: [Brief Summary]"
# Example: git commit -m "Release v1.8.2: Fix permissions and update reports"
```

## 4. Push to Develop
- Push the committed changes to the `develop` branch.
```powershell
git push origin develop
```

## 5. Tag and Release
- Create a git tag for the version.
```powershell
git tag vX.X.X
# Example: git tag v1.8.2
```
- Push the tag to the remote repository (this triggers the release on GitHub).
```powershell
git push origin vX.X.X
```

> **Note:** If you forget to update the version/changelog *before* tagging, you must:
> 1. Delete the local tag: `git tag -d v1.8.2`
> 2. Make the commits.
> 3. Re-tag: `git tag v1.8.2`
> 4. Force push tag if already pushed: `git push origin v1.8.2 --force`

## 6. Notification
- Notify the user that the release is complete.
