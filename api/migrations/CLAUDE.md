this application uses a code-first approach to manage DB schema changes. we've maintained perfect code -> migrations for years; we'd like to continue for many more years.

1. do not hand-write migrations for schema changes; use `make:migration`
2. if `make:migration` does not produce the expected migration, then the migration should be deleted, and the underlying problem fixed before running `make:migration` again
   * the problem could be bad code, that a previous dev forgot to rollback changes from another branch they were on, ... investigate, and solve
3. hand-writing migrations makes sense for data changes, only
