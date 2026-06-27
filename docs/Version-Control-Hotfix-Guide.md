# Version Control – Hotfix & Retag Guide

## The Golden Rule

**Always code on `main`.** Never develop directly on a tag.
Tags are read-only snapshots of main at a point in time.

---

## Scenario: Prof Asks You to Add/Fix a Feature on a Past Version

### Step 1 — Code the fix on `main` first

```bash
git checkout main
# make your changes...
git add .
git commit -m "feat: add [feature] per prof feedback"
git push origin main
```

### Step 2 — Re-tag only the versions the prof will check

For each version that needs the fix (usually just v1.00 and v1.01 for login-related changes):

```bash
# Create a temporary branch from the old tag
git checkout -b temp-vX.XX-fix vX.XX

# Manually apply just the changed file(s) from main
# (copy-paste the relevant lines — do NOT merge all of main)

git add .
git commit -m "feat: add [feature] per prof feedback"

# Move the tag to this new commit
git tag -d vX.XX
git tag vX.XX
git push origin vX.XX --force

# Back to main, clean up
git checkout main
git branch -D temp-vX.XX-fix
```

Repeat for each version that needs it.

---

## Which Versions Do You Actually Need to Re-tag?

| Version | Re-tag? | Reason |
|---------|---------|--------|
| v1.00 | **Always** | This is where the feature originally lives |
| v1.01 | **If prof checks it** | Re-tag only if they'll open it |
| v1.02–v1.13 | **Skip unless asked** | They have the login page already — the prof won't look for a login feature here |

---

## How to Avoid This Pain in the Future

Make your fix on `main` **before** tagging the next version.
If you commit first, then tag — the fix is baked into every future tag automatically. No re-tagging needed.

```
BAD:  commit v1.00 → tag v1.00 → tag v1.01 → fix login → re-tag v1.00, v1.01
GOOD: commit v1.00 → fix login → tag v1.00 → tag v1.01  (fix is in both automatically)
```

---

## Quick Reference — Re-tag a Single Version

```bash
git checkout -b temp-fix vX.XX       # branch off the old tag
# apply changes manually
git add . && git commit -m "fix: ..."
git tag -d vX.XX && git tag vX.XX    # move the tag
git push origin vX.XX --force        # update remote
git checkout main
git branch -D temp-fix               # clean up
```
