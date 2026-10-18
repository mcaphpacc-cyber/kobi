# Phase 3.21A authorized owner migration — stopped before normal deployment

Observed 2026-10-10 18:31:33 UTC (2026-10-11 00:01:33 Asia/Calcutta).

The owner authorized the exact owner-expiry correction and subsequent protected bootstrap only after verified recoverable backup and genuine account-1 authentication. The recovery rehearsal stopped. No normal DDL, grant, event or bootstrap call was executed. The owner administrator remains unestablished.

## Backup and recovery result

- Target: normal `medical1`, port 3306, MariaDB 10.4.27.
- Protected backup: `storage/logs/medicine321a-owner-recovery/before.sql`, 5,352,130 bytes; SHA256 `f303f482ba49931db370a33b833bf95e5106e3c20de21d47ce3bfc4c78108cf2`.
- mysqldump exited successfully using single-transaction and including routines/events/triggers/binary data. Temporary client credentials were removed in finally. Backup is readable, hash checked and retained. Its HTTP URL returned 403; directory also has `Require all denied`.
- Preflight and final per-table sorted row digests, schema definitions, trigger/routine/event metadata and database privilege digest are retained in the same protected directory. No row content or credentials were printed.
- Separate MariaDB 10.4.27 server: port 3342, `tests/.medicine-owner-recovery/data`; restore database `kobi_owner_recovery_20261010`. The guard checked server port, resolved datadir, version and matching session/global mode before restore writes.
- 257 dump statements executed on the isolated restore, then the import parser stopped at an unrecognized trigger-specific mode directive. No migration ALTER was rehearsed or deployed. This was an import-parser rejection, not a reported MariaDB schema compatibility error. Recovery has **not** been verified and cannot be certified as recoverable yet.

The intentionally omitted initial dump directive was:

```sql
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
```

The directive that stopped parsing was:

```sql
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
```

The parser recognizes the full-schema style mode directives but not mysqldump's trigger-specific save/restore format. It rejected this instead of silently omitting it. Remaining dump statements were not executed. No normal/global/persistent SQL mode was changed. The initial directive was omitted only from the isolated import to preserve the existing expected mode; the retained backup is unchanged.

Recovery procedure: retain this full backup and its hash; create a fresh isolated MariaDB 10.4.27 instance with the expected mode; explicitly review/account for mysqldump mode directives; restore every other dump statement; compare all data digests and table/view/trigger/routine/event definitions to preflight; only then certify recovery and rehearse the exact ALTER. A retry needs the parser handling corrected and the stopped run reviewed. Do not import the full dump into the existing normal database or apply rollback automatically. A real recovery requires stopping application writes, a separately approved recovery destination and complete integrity comparison before switching access.

The partially restored isolated database was dropped, absence verified, dedicated server shut down, and its datadir removed after exact-path/reparsepoint/process checks. Backup and evidence remain.

## Normal database preservation

Final read-only verification passed:

- All 54 base-table row counts/digests unchanged.
- All table/view definitions, eight trigger definitions/modes/definers, routines/events and privilege digest unchanged.
- Account 1 remains active. Authority grants: 0; governance events: 0. Expiry column remains NOT NULL because the migration was not applied.
- Session and global mode remain `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`.
- Owner designation remains account 1, initial administrator account 1; legacy owner/operator lists empty and both pilot flags false with no activation.
- No unrelated data, Treatment governance, public Medicine exposure, pilot activation or Git state mutation occurred.

## Authentication and deferred actions

The available browser reached the actual KOBI login page at `http://localhost/kobi/public/login` and was signed out. There is no genuine account-1 session available to this run. The bootstrap is currently an internal service only; no route/controller/view invokes it. No CLI actor, synthetic AuthService, direct grant insert, session impersonation or additional role was used.

Deferred: successful full recovery verification, exact isolated ALTER rehearsal, normal ALTER and post-migration metadata/constraint validation, authenticated protected bootstrap, actual grant/event/history and permanent NULL-expiry authorization checks. No administrator/contributor/editor/deployment/reviewer/other role was granted. Ordinary self-grant paths were not changed.

## Files and checks in this deployment attempt

Created `tests/medicine-owner-schema-deployment.php` (CLI-only guarded operator helper; no bootstrap/grant writes), this report, and protected backup/evidence files under `storage/logs/medicine321a-owner-recovery/` (`.htaccess`, `before.sql`, `backup.json`, `preflight.json`, `stopped-verification.json`, `dump.stdout`, `dump.stderr`). Modified only the prior bootstrap report to reference this deployment stop. The approved SQL artifact and application code are unchanged.

Helper PHP lint and project `git diff --check` passed. Actual backup creation, HTTP denial, isolated partial restore/guard/cleanup and normal read-only preservation checks were performed. Earlier bootstrap unit/MariaDB tests remain documented in the preceding report; they were not rerun in this attempt. No live bootstrap/audit success is claimed.
