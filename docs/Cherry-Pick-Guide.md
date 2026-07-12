# Version Control Setup Prompt — PHP (Reusable)

Copy and paste this prompt to use the same version control system on any PHP project.

---

## The Prompt

```
I want to set up a week-by-week versioned presentation system for this project using Git and GitHub.

Before doing anything, scan the entire project and identify:
- All pages or screens available in the system
- What each page does (brief description)
- Group them by feature or role (e.g. admin pages, user pages, public pages)

Once you have a complete picture of all the features and pages, create a rollout plan like this:
- v1.00 starts with the most basic feature (Login and Register, or the landing page)
- Each version after that unlocks exactly ONE page or feature on top of the previous version
- Do admin pages first (one per version), then user/patient pages (one per version)
- The final version presents the full system with everything unlocked
- Pages not yet presented in the current version should show an "Under Construction" placeholder page

Use this version numbering format: v1.00, v1.01, v1.02 ... up to however many pages you have.

Present a plan first in this format and wait for my approval:

  v1.00 - Login / Register - Admin/User
  v1.01 - Admin: [Feature]
  v1.02 - Admin: [Feature]
  ...
  vX.XX - User: [Feature]
  ...
  vX.XX - User: [Last Feature] (Full System)

After I approve the plan, do the following:

1. Create `components/under-construction.php`:
   - define('CURRENT_VERSION', 'v1.00') at the top
   - A styled full-page HTML card with a hard-hat icon, version badge, title, description, and a Go Back button
   - exit at the very bottom

2. Add `<?php require_once '../../components/under-construction.php'; ?>` as the very first line
   of every page that should be gated, so it blocks the page content and shows Under Construction instead.

3. Pages that are part of the current version (v1.00) should NOT have the gate.

4. Create a `docs/Version-Control.md` file with:
   - The full rollout schedule table (version, feature, pages unlocked, pages still gated)
   - The Under Construction strategy explanation
   - Git commands to use per version
   - How Git tags work as permanent snapshots
   - A GitHub Release Tags table with version, tag name, and commit hash columns (fill hashes at the end)
   - A section on what to do when a prof or client requests changes after a presentation

5. For each version, when I say "Proceed":
   - Remove the under-construction gate line from the page being unlocked that version
   - Update the version number in components/under-construction.php
   - Commit with: feat: implement vX.XX - unlock [Feature Name]
   - Create a Git tag: git tag vX.XX
   - Push both: git push origin main && git push origin vX.XX
   - Do NOT stop to ask for confirmation between versions — keep going until all versions are done
   - After ALL versions are committed and pushed, update the commit hash column in Version-Control.md
     using: git tag | sort | xargs -I{} git log -1 --format="{} %H" {}
     Then commit and push the updated docs.

6. If any page has live data that should not be visible yet, add a gate that zeroes out the data
   and shows an empty "Under Construction / No records to show" state instead of hiding the whole page.
   Remove this data gate when the relevant version is reached.

Present the plan first and wait for my approval before making any changes.
```

---

## Notes for Using This Prompt

- This prompt is written for **PHP projects**. For other languages, see the matching prompt file in this folder:
  - `Version-Control-Setup-Prompt-VBNET.md` — VB.NET Windows Forms (Visual Studio 2022)

- The version numbering used here is `v1.00, v1.01, v1.02 ...`. The number of versions equals
  the number of pages/features in your project — one unlock per version.

- If your project has both a **public side** and an **admin/user side**, mention that in the prompt
  so the AI groups features correctly (admin pages first, user pages after).

- When the AI asks "Proceed" for the next version, you can say **"Proceed without asking for
  confirmation, do all remaining versions at once"** to let it finish everything in one go.

- After all versions are done, the AI will automatically fill in the commit hash table in
  `docs/Version-Control.md` and push the final update.

---

## Sample Rollout Format (for reference)

```
Version 1.00 - Login / Register - Admin/User

Version 1.01 - Admin
-Dashboard

Version 1.02 - Admin
-Manage Appointment

Version 1.03 - Admin
-View Calendar

Version 1.04 - Admin
-Doctor Schedule

Version 1.05 - Admin
-Patient Record

Version 1.06 - Admin
-Manage User

Version 1.07 - Admin
-Reports

Version 1.08 - Admin
-Admin Profile

Version 1.09 - User
-Dashboard

(continue one per menu for user side)
```

Each page's own features must work fully when unlocked.
If a button or link inside it leads to a page not yet unlocked, that link will naturally
hit the under-construction gate when clicked.

---

## Quick Reference: Commands Per Version

```bash
# Stage and commit
git add <unlocked-page.php> components/under-construction.php
git commit -m "feat: implement vX.XX - unlock [Feature]"

# Tag and push
git tag vX.XX
git push origin main
git push origin vX.XX
```

## Quick Reference: Update Docs After All Versions Are Done

```bash
# Get all tag hashes to paste into Version-Control.md
git tag | sort | xargs -I{} git log -1 --format="{} %H" {}
```

## Quick Reference: When Prof Requests Changes After a Presentation

```bash
# Fix on main first
git checkout main
git add .
git commit -m "feat: update [page] per feedback"
git push origin main

# Delete old tag and re-create it pointing to the new commit
git tag -d vX.XX
git push origin :refs/tags/vX.XX
git tag vX.XX
git push origin vX.XX
```
