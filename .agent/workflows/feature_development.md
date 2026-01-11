---
description: Standard git workflow for feature development, testing, and merging.
---

# Feature Development Workflow

This workflow enforces the project's strict branching and testing strategy.

## 1. Create Feature Branch
Always start work on a new feature or fix in a dedicated branch.
```bash
git checkout -b feature/name-of-task
```

## 2. Implement Changes & Tests
- Write the code for the feature or fix.
- **CRITICAL**: Create or update relevant tests.
- Ensure all tests pass on the feature branch.

## 3. Dedicated Testing (Optional but Recommended)
If the task is complex, verify specifically on a testing branch or run the full suite.
```bash
# Run all tests
vendor/bin/pest
```

## 4. Merge to Main
Once tests pass, merge the feature branch into `main`.
```bash
git checkout main
git merge feature/name-of-task
git push origin main
```

## 5. Merge to Stable
After confirming stability on `main`, merge to `stable`.
```bash
git checkout stable
git merge main
git push origin stable
```

## 6. Cleanup
Delete the feature branch after successful merge.
```bash
git branch -d feature/name-of-task
```
