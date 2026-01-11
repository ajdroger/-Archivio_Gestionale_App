---
description: Enterprise Git Workflow enforcing develop-first approach, dedicated testing branches, and hotfix release paths.
---

# Enterprise Development Workflow

**CRITICAL RULE**: All development starts from `develop`.

## 1. Feature Development
Work starts on a dedicated feature branch created from `develop`.
```bash
git checkout develop
git checkout -b feature/NAME
```

## 2. Testing Phase
Before merging to develop, changes must be validated on a dedicated test branch.
1. Create/Checkout a test branch (e.g., `tests/integration`).
2. Merge your feature into it.
3. Run the full test suite.
```bash
git checkout -b tests/NAME
git merge feature/NAME
vendor/bin/pest
git checkout -b tests/NAME
git merge feature/NAME
vendor/bin/pest
```
**CRITICAL**: `tests/*` branches must **NOT** be deleted. Keep them for audit history.

**CRITICAL**: `tests/*` branches must **NOT** be deleted. Keep them for audit history.

## 3. Completion (Success Path)
If tests pass and no conflicts exist:
(User implication: Merge back to develop to persist history)
```bash
31: git checkout develop
32: git merge feature/NAME
33: 
34: # 3a. CLOSE BRANCH (Do not delete)
35: # The branch is now "closed" (inactive). You move back to develop.
36: # History is preserved.
37: 
38: # 3b. REOPEN BRANCH
39: # If you need to resume work on this feature later:
40: # git checkout feature/NAME
```

## 4. Conflict / Bug Handling (Hotfix Path)
**IMPORTANTE**: If conflicts, errors, or bugs arise during testing/merge:
1. Create a **Hotfix** branch.
   ```bash
   git checkout -b hotfix/DESCRIPTION
   ```
2. Fix the specific issue.
3. Validate the fix.
4. Merge strictly to **Main** and then **Stable**.
   ```bash
   git checkout main
   git merge hotfix/DESCRIPTION
   git checkout stable
   git merge main
   ```
5. (Recommended) Backport fix to `develop` to keep environments synced.

## 5. Final Release
Stable code flows from `main` to `stable`.
```bash
git checkout stable
git merge main
```
