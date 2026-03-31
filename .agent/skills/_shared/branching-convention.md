# Branching and Release Convention

## Branch Management
- **No direct commits to `develop` or `main`**: If the current branch is `develop` or `main` and a change is proposed, ALWAYS ask the user for a feature name to create a dedicated branch (e.g., `feature/name-of-feature`).
- **Feature-first focus**: Each new task must start in its own branch unless explicitly told otherwise.

## Release Process
When performing a release, FOLLOW these steps exactly:
1.  **Update `version.txt`**: Increment the version number (e.g., `v1.9.29` -> `v1.9.30`).
2.  **Update `CHANGELOG.md`**: Add the new version header in format `## [X.X.X] - YYYY-MM-DD` at the top and summarize the changes.
3.  **Commit as Release**: Commit both files with message `Release vX.X.X: [Summary]`.
4.  **Merge & Push**: Merge the work to `develop` and push to the remote.
5.  **Tagging**: Create a git tag `vX.X.X` and push the tag to trigger the release workflow.
