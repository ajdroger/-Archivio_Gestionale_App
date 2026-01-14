---
description: Enterprise Git Workflow enforcing develop-first approach, dedicated testing branches, and hotfix release paths.
---

# Enterprise Development Workflow

**CRITICAL RULE**: All development starts from `develop`.

## 1. Feature Development
**STRICT RULE**: Feature branch names **MUST** include a version number that is sequential to the previous feature (e.g., if previous was v5.4.1, new is v5.4.2).

Work starts on a dedicated feature branch created from `develop`.
```bash
git checkout develop
# Example: git checkout -b feature/login-system-v5.4.2
git checkout -b feature/NAME-vX.Y.Z
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

## 3. Completion (Success Path)
If tests pass and no conflicts exist:
(User implication: Merge back to develop to persist history)
```bash
git checkout develop
git merge feature/NAME

# 3a. CLOSE BRANCH (Do not delete)
# The branch is now "closed" (inactive). You move back to develop.
# History is preserved.

# 3b. REOPEN BRANCH
# If you need to resume work on this feature later:
# git checkout feature/NAME
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

## 6. Documentation & Logging Rules (FUNDAMENTAL)
**STRICT RULE**: Documentation updates are MANDATORY **AFTER** every merge of a feature branch (which must use correct sequential versioning) into `develop` and `main`.

### A. Documentation Requirements (Ultra-Detailed)
1.  **Files**: Update `CHANGELOG.md` and `DECISION_LOG.md`.
2.  **Content**:
    *   Explain **WHAT** was done and **WHY**.
    *   **MANDATORY**: Include **CODE SNIPPETS** (lines or diffs) of the implementation/fix.
    *   *Example*:
        ```php
        // Updated AuthController to prevent session fixation
        - Session::start();
        + Session::regenerate();
        ```

### B. Milestone Release Rule (Tagging & Branching)
**TRIGGER**: Whenever the version number reaches a "Numeric Milestone" (e.g., 5.5.5, 6.0.0, 6.5.5, 7.0.0, etc.).
**ACTION**:
1.  Create a dedicated **Release Branch** (e.g., `release/v6.0.0`).
2.  Create a **Git Tag** on that branch (e.g., `v6.0.0`).

