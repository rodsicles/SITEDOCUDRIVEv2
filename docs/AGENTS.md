# Project instructions

- Start with **Quick Context** and **Topic index** in `docs/SYSTEM_GUIDE.md`. Read only the sections relevant to the task, then inspect the implementation files they identify. Do not reread `docs/archive/legacy/` unless the task is documentation archaeology.
- `docs/SYSTEM_GUIDE.md` is the product/behavior reference. `docs/AGENTDESIGN.md` is the UI system to follow. Code is evidence of current behavior; record discrepancies instead of silently treating a proposed feature as implemented.
- After authorized implementation changes, update the affected guide and design sections and append one concise change-history entry with verification and migration notes. Never invent a commit ID or claim a deployment was verified.
- Questions, audits, and plan-only requests do not authorize edits. Preserve unrelated local work. Commit, push, migration execution, and deployment follow the user's requested scope.
- Preserve Coordinator home-plus-extra-program permissions, Faculty sharing rules, personal categories, and private-folder restrictions. Use the existing authorization helpers; verify direct endpoints as well as UI visibility.
- Follow `docs/AGENTDESIGN.md` and current `--site-*` tokens. Keep the approved upload workflow, login branding, and role-consistent chrome. Feature CSS such as `teacher-loads.css` already exists; do not follow the archived lime / “global CSS only” `AGENTDESIGN.md`.
- Treat `docs/archive/legacy/` as historical copies, not competing instructions. Do not delete those files until the user approves the exact list. Do not touch dependency documentation or licenses.
- Run checks proportional to the change. For documentation-only work, verify paths, links, factual claims, and the diff; application tests/builds are not automatically necessary. Never run `composer test` on Laravel Cloud.
