# Admin Refactor TODO

> Goal: refactor `admin.php` into a maintainable structure (thin entrypoint + services + templates) while keeping **behavior identical** (same DB queries, categories, placeholders, CSRF rules, and upload constraints).

## Core implementation tasks

- [ ] `admin.php`: refactor into a thin entrypoint (split helpers/services/templates)
- [ ] `src/admin/csrf.php`: CSRF token generation + validation (`hash_equals` + return `403`)
- [ ] `src/admin/upload.php`: upload dir mapping + MIME/size checks + safe `move_uploaded_file`
- [ ] `src/admin/project_service.php`: add/update/delete project logic
- [ ] `src/admin/experience_service.php`: add/update/delete experience logic
- [ ] `templates/admin/*`: extract admin UI panels (tabs, tables, inline edit forms) + shared head/styles
- [ ] Keep behavior identical to current version:
  - DB queries & SQL structure
  - placeholders
  - categories
  - CSRF behavior
  - upload constraints

## Manual verification checklist (post-refactor)

- [ ] Login → Admin access still works
- [ ] Admin actions work when `csrf_token` is present:
  - [ ] add/update/delete projects
  - [ ] add/update/delete experiences
  - [ ] logout
- [ ] Admin actions are rejected with `403` when `csrf_token` is missing/invalid
- [ ] Upload tests:
  - [ ] Each valid category uploads successfully
  - [ ] Invalid upload MIME rejected
  - [ ] Files `> 4MB` rejected
- [ ] Image update/delete correctness:
  - [ ] Update replaces images correctly
  - [ ] Delete removes images correctly
  - [ ] Placeholder image is not deleted
- [ ] UI consistency:
  - [ ] Switch tabs (projects/experiences) keeps UI consistent
  - [ ] Inline edit panels work correctly
- [ ] Frontend:
  - [ ] `index.php` still renders projects/experiences from DB without errors
