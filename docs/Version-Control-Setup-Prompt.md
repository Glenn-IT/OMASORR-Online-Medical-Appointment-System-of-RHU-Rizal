# Version Control Setup Prompt (Reusable)

Copy and paste this prompt to use the same version control system on any project.

---

## The Prompt

```
I want to set up a week-by-week versioned presentation system for this project using Git and GitHub.

Before doing anything, scan the entire project and identify:
- All pages or screens available in the system
- What each page does (brief description)
- Group them by feature or role (e.g. admin pages, user pages, public pages)

Once you have a complete picture of all the features and pages, create a rollout plan like this:
- Week 1 starts with the most basic feature (usually Login and Register or the landing page)
- Each week adds one logical feature or group of features on top of the previous week
- The final week presents the full system with everything unlocked
- Pages not yet presented in the current version should show an "Under Construction" placeholder page

After I approve the plan, do the following:

1. Create a `components/under-construction.php` (or equivalent for this project's language/framework)
   that outputs a full styled page saying "Under Construction" with the current version number,
   and exits immediately so the rest of the page does not run.

2. Add a `require_once` (or equivalent import/include) of that file at the very top of every
   page that should be gated, so it blocks the page content and shows Under Construction instead.

3. Pages that are part of the current version should NOT have the gate — remove it or skip adding it.

4. Create a `docs/Version-Control.md` file with:
   - The full week-by-week rollout schedule listing which files are unlocked each week
   - Step-by-step Git commands for pushing each version to GitHub
   - An explanation of how Git tags work as permanent snapshots
   - A GitHub Release Tags table with version, tag name, and commit hash columns
   - A section on what to do when a prof or client requests changes after a presentation
     (fix on main, re-tag the upcoming version)
   - The Under Construction page strategy explanation

5. For each version as we proceed week by week:
   - Remove the under-construction gate from the pages being unlocked that week
   - Update the version number shown on the Under Construction page
   - Commit with a clear message like: feat: implement vX.XX - unlock [Feature Name]
   - Create a Git tag: git tag vX.XX
   - Push both the commit and the tag: git push origin main && git push origin vX.XX

6. If any page has live data that should not be visible yet (e.g. a public listing page
   that shows database records), add a gate that zeroes out the data and shows an
   "Under Construction / No records to show" empty state instead of hiding the whole page.
   Remove this data gate when the relevant feature version is reached.

Present the plan first and wait for my approval before making any changes.
```

---

## Notes for Using This Prompt

- Works best with **PHP projects** as-is. For other languages adjust the include strategy:
  - **Python/Flask**: use a route decorator or redirect
  - **Node/Express**: use middleware
  - **HTML only**: replace the file with a static under-construction HTML page

- The version numbering used here is `v1.01, v1.02 ... v1.10`. You can adjust to however many weeks you need — just tell the AI how many presentations you have planned.

- If your project has both a **public side** and an **admin/user side**, mention that in the prompt so the AI groups features correctly.

- After the plan is approved and all versions are done, update the GitHub Release Tags table in `Version-Control.md` by running:
  ```bash
  git tag | sort | xargs -I{} git log -1 --format="{} %H" {}
  ```
  Then paste the output into the table.

---

## Quick Reference: Commands Per Version

```bash
# Stage and commit
git add .
git commit -m "feat: implement vX.XX - unlock [Feature]"

# Tag and push
git tag vX.XX
git push origin main
git push origin vX.XX
```

## Quick Reference: When Prof Requests Changes

```bash
# Fix on main first
git checkout main
git add .
git commit -m "feat: update [page] per feedback"
git push origin main

# Re-tag the upcoming version
git tag -d vX.XX
git push origin :refs/tags/vX.XX
git tag vX.XX
git push origin vX.XX
```
