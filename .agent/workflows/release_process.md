---
description: Create a new release (Version, Changelog, Tag, Push)
---

This workflow outlines the steps to correctly release a new version of the application.

# 1. Update Version File
- Open `version.txt`.
- Increment the version number (e.g., change `v1.8.1` to `v1.8.2`).
- Save the file.

# 2. Update Changelog
- Open `CHANGELOG.md`.
- Add a new section at the top of the list (below the header) with the new version and date.
- List all Fixed, Added, or Changed items.
- Example format:
  ```markdown
  ## [1.8.2] - 2026-01-27
  
  ### Fixed
  - Description of the fix...
  ```

# 3. Commit Changes
- Stage the version and changelog files.
- Commit with a standard message.
```powershell
git add version.txt CHANGELOG.md
git commit -m "Chore: Update version to v1.8.2 and add changelog release notes"
```

# 4. Create Git Tag
- Create a new tag matching the version number.
```powershell
git tag v1.8.2
```

# 5. Push Changes
- Push the commit to the `develop` branch.
```powershell
git push origin develop
```

# 6. Push Tags
- Push the new tag to the repository (this triggers the release on GitHub).
```powershell
git push origin v1.8.2
```

> **Note:** If you forget to update the version/changelog *before* tagging, you must:
> 1. Delete the local tag: `git tag -d v1.8.2`
> 2. Make the commits.
> 3. Re-tag: `git tag v1.8.2`
> 4. Force push tag if already pushed: `git push origin v1.8.2 --force`
