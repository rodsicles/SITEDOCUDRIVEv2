# Project instructions

- Start with **Quick Context** and **Topic index** in `docs/SYSTEM_GUIDE.md`. For UI work, follow `docs/AGENTDESIGN.md`. Do not reread files under `docs/archive/legacy/`.
- The same rules are in `docs/AGENTS.md`. Code is evidence of current behavior; record discrepancies instead of silently treating a proposed feature as implemented.
- After authorized implementation changes, update the affected guide/design sections and append one concise change-history entry. Never invent a commit ID or claim a deployment was verified.
- Questions, audits, and plan-only requests do not authorize edits. Preserve unrelated local work. Commit, push, migration execution, and deployment follow the user's requested scope.
- Preserve Coordinator home-plus-extra-program permissions, Faculty sharing rules, personal categories, and private-folder restrictions. Use the existing authorization helpers; verify direct endpoints as well as UI visibility.
- Follow current `--site-*` design tokens and shared components. Keep the approved upload workflow and role-consistent styling. Feature CSS such as `teacher-loads.css` already exists; do not follow obsolete “global CSS only” or lime-palette rules in the archived design file.
- Treat archived Markdown as historical. Do not delete it until the user approves the exact list. Do not touch dependency documentation or licenses.
- Run checks proportional to the change. For documentation-only work, verify paths, links, factual claims, and the diff. Never run `composer test` on Laravel Cloud.
