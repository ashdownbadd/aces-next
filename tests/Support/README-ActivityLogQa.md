# Activity Log QA Seed

Run:

```powershell
php tests/Support/SeedActivityLogsForQa.php
```

This inserts 75 synthetic rows into `activity_logs` only. It does not create or modify members, loans, payments, or users.

After pagination/filter QA is complete:

```powershell
php tests/Support/ClearActivityLogQaSeed.php
```

The cleanup removes only rows whose description starts with `QA pagination seed:` and uses IP `127.0.0.1`.
