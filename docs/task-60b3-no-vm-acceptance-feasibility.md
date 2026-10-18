# Step 60B-3 — No-VM Acceptance Environment Feasibility

Date: 19 October 2026. Read-only inspection; proposed resources below do not exist yet.

## 1. Executive Summary

**STEP 60B-3: READY WITH DESIGN CHANGES.** No-VM cohosting is technically feasible for controlled synthetic acceptance, but a second name-based vhost in the existing user-owned Apache process is insufficient isolation on the observed machine. Recommend a **separate acceptance Apache/PHP process and separate MariaDB instance**, with independent identity/config/storage, outside XAMPP, using LAN-only HTTPS **8443** and DB **127.0.0.1:3307**. Do not change protected ports 80/443 or the demo route.

New user-provided facts resolve two earlier design assumptions: `monchez.in` is owner controlled, and a physical Android phone is the primary target. VM licensing/storage and emulator acceleration are therefore not prerequisites to this revised approach.

Still required: approval of this revised process/port/permission model, confirmation of the actual demo hosting/origin mapping, private DNS, a dedicated trusted certificate, phone LAN details and verified runtime/file/network restrictions. **Not ready for immediate provisioning or device testing.** Conditional feasibility is not proof that isolation is already installed. If adequate process/ACL/network restrictions cannot be established within separately authorized changes, stop rather than share the protected runtime or weaken the boundary.

## 2. 60B-1/60B-2 Context

Read the 60B-1 VM design and blocked 60B-2 result, together with the 60B environment assessment, 60A readiness report and current code/schema/project instructions. Those documents remain historical and were not edited. This task explicitly authorizes evaluating a different no-VM design; it does not authorize setup.

The VM design's synthetic-only/independent-secret/trusted-HTTPS requirements remain. Its VM/guest-license/emulator requirements need not be carried forward when using separately isolated native services and the supplied physical target. A plain extra vhost must not be mistaken for an OS isolation boundary.

## 3. Current Apache

Apache 2.4.54 Win64 at `D:/xampp/apache`; `httpd -S` shows only default `*:443 localhost`, document root `D:/xampp/htdocs`. Listen 80 and 443 are wildcard/all-interface listeners. `httpd -t`: Syntax OK. `httpd-vhosts.conf` has no active additional vhost; the active configuration does **not** name `kobi.monchez.in`.

PHP 8.2.0 ZTS is integrated through `D:/xampp/php/php8apache2_4.dll`; CLI loads `D:/xampp/php/php.ini`. Both running httpd processes belong to **LAPTOP-PTFFHOMV\Sanjog**. No Apache Windows service was found; mysql runs as LocalSystem. No cloudflared/ngrok/php-cgi process was observed in the targeted process inventory. This is not proof that no upstream proxy exists elsewhere.

Apache can provide separate DocumentRoot, logs and TLS settings per vhost. However, same-process mod_php retains the same OS privileges and common failure/restart boundary. Adding/reordering a vhost could also change default-host routing. See [Apache name-based vhost selection](https://httpd.apache.org/docs/2.4/vhosts/name-based.html). Reusing the PHP version/binary distribution is feasible; **sharing the current live PHP process/configuration is not the selected design**.

## 4. Current KOBI Deployment

Verified local copy: `D:/xampp/htdocs/kobi`, public root `.../public`; root `.env` is loaded by `app/Helpers/functions.php`, then `app/Config/config.php` supplies application settings. Current safe metadata: APP_URL `http://localhost`, APP_BASE_PATH `/kobi/public`, DB `localhost:3306/medical1`.

Writable areas include local `storage/logs` (Logger uses an app-relative hardcoded directory), configured storage/cache, PHP sessions/temp and document storage `config(storage_path)/health-documents/<profileId>`. A separate copy naturally separates app-relative resources, provided no normal `.env`, uploads, cache/log contents, path links or external credentials are copied. APP_STORAGE_PATH alone does not redirect Logger.

The public demo's filesystem/database mapping is **unverified**. Public DNS and its different TLS certificate do not establish that it is served by this local copy. No demo application HTTP request, login or content inspection was performed to guess the mapping.

## 5. Configuration Isolation

Future acceptance root `.env` must be created from explicit supported keys, not copied from normal `.env`:

| Setting | Proposed acceptance value |
| --- | --- |
| APP_NAME | KOBI MOBILE ACCEPTANCE — SYNTHETIC ONLY |
| APP_URL | `https://kobi-mobile-acceptance.monchez.in:8443` |
| APP_BASE_PATH | Empty, because DocumentRoot is the acceptance `public` directory |
| APP_TIMEZONE / APP_DEBUG | Asia/Kolkata / false |
| DB_HOST / DB_PORT | 127.0.0.1 / 3307 |
| DB_DATABASE / DB_USERNAME | kobi_mobile_acceptance_60c / kobi_mobile_runtime |
| DB_PASSWORD / API_AUTH_RATE_KEY | Newly generated, acceptance-only, server-side secrets; no values here |
| APP_STORAGE_PATH | `D:/kobi-mobile-acceptance/app/storage` |

Use a separate acceptance PHP ini with unique session store/name, temp/error paths and no inherited host secret environment. No fictitious APP_ENV enforcement is assumed. Flutter staging label supplies the client marker. Verify effective settings from that process before any fixture login. Required credentials must never fall back to the protected instance.

## 6. Database Isolation

Fresh metadata-only connection: MariaDB **10.4.27**, selected normal schema **medical1**, configured localhost/3306, global ALL PRIVILEGES for the configured account and no configured password. No account name/password/hash was printed. Current listener is `:::3306`. SQL mode session/global: `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION`.

MariaDB supports schema/table-scoped local accounts, but normal-account reuse is unacceptable. Recommended independent instance: `D:/kobi-mobile-acceptance/runtime/mariadb`, own config/data/PID/logs, **bind 127.0.0.1:3307**, new admin/bootstrap credentials and schema `kobi_mobile_acceptance_60c`. Do not initialize its data directory by copying normal mysql/data.

Runtime account `kobi_mobile_runtime`, explicit local host scope and unique strong password: SELECT on acceptance schema, INSERT/UPDATE only on `user_api_sessions`, `user_api_refresh_tokens` and `health_medicine_schedule_checkoffs`. Operator handles fixture/schema/retention tasks. No runtime global/DDL/FILE/GRANT privileges. Verify transactions/locking/upsert under final grants and deny access to other schemas; a refusal to connect to normal services must be tested without production health queries/writes.

Separate schemas/users on the current normal server are technically possible but not chosen: that would modify protected MariaDB and share its data/admin/failure boundary. A second instance keeps acceptance DB setup/teardown independent. It does **not** itself prevent the acceptance OS process accessing normal 3306; that requires the process/network controls in section 17.

## 7. Schema Readiness

`database/structure.sql` is authoritative and unchanged, with current slot/checkoff/API auth tables and triggers. It contains DROP statements, dependency/constraint controls, DELIMITER/trigger definitions and three SQL-mode directives; it is **not an approved blind import stream**. No INSERT seed statements or turnkey seed/install file were found in the focused project scan.

Future setup needs an asserted empty acceptance target, delimiter-aware import and actual table/index/FK/CHECK/trigger verification. Prepare a reviewed stream omitting only the mode-changing directives at lines 9, 13 and 1603 under explicit setup approval; preserve original source and all other schema objects. Verify required mode without changing normal/global/session SQL mode. If a new instance's baseline differs, stop for review rather than silently set it. Complete fresh import compatibility remains unexecuted; do not replay additive migrations already represented in structure.sql.

Minimal synthetic fixture provisioning is additional to schema installation. Public disease/registry rows and valid saved snapshots need deliberate fixture content; `resources/disease-references.php` is a source manifest tied to known disease IDs, not a database seed. Never manufacture medical/source mappings for synthetic diseases or copy production health records to fill gaps.

## 8. DNS

Fresh DNS results using approved read-only access:

| Name | Result |
| --- | --- |
| monchez.in | A 216.10.240.202 |
| kobi.monchez.in | A 216.10.240.202 |
| kobi-mobile-acceptance.monchez.in | NXDOMAIN / DNS name does not exist |

Initial restricted-context DNS queries returned no usable records; the explicit permitted retry produced the results above. No public IP discovery was performed for this workstation and **216.10.240.202 is not proven to be its WAN address/origin**.

Preferred arrangement: private/split DNS A record **kobi-mobile-acceptance.monchez.in → 192.168.1.5**, after reserving/revalidating the workstation LAN IP. The phone must actually use that resolver. Public authoritative DNS only needs temporary `_acme-challenge` TXT for DNS-01; a public acceptance A record pointing to the demo's address is unnecessary and could reach the wrong server. Do not change any demo record. Publishing a private A record publicly is not selected; split DNS keeps test routing explicit and avoids assumptions about DNS-rebinding filters.

DNS does not encode port 8443: the app URL includes it. If a portless acceptance URL is mandatory, that requires a separately approved listener/front-end change or distinct IP; it is not silently mapped onto protected 443.

## 9. HTTPS

Fresh **TLS connection only, no HTTP request** to kobi.monchez.in succeeded with peer/hostname verification and verify result **0**. Served certificate:

- Subject CN `www.kobi.monchez.in`; issuer Let's Encrypt **YR2**.
- SAN `DNS:*.monchez.in, DNS:www.kobi.monchez.in`.
- Valid 21 August 2026 18:51:15 UTC through 19 November 2026 18:51:14 UTC.

Thus an existing public wildcard **does** cover the proposed one-label acceptance subdomain cryptographically. Its private-key location/issuance/deployment permissions are unknown; it was not located locally and must not be retrieved/reused merely because its public SAN matches. Local default Apache instead references the existing KOBI development localhost leaf/key under `D:/xampp/apache/conf/kobi-local-tls`, whose SAN is localhost only. These are distinct TLS arrangements; upstream termination is possible but unverified.

Recommendation: issue a **dedicated** leaf for `kobi-mobile-acceptance.monchez.in` via the owner's DNS-01 control, with new acceptance-only key/full chain. DNS-01 can issue for a server that is not publicly exposed; see [Let's Encrypt DNS-01](https://letsencrypt.org/docs/challenge-types/). Install only in acceptance Apache. Certificate validation ignores the URL port for hostname matching, so 8443 does not require a different SAN.

Physical Android should validate a compatible normally trusted full chain with correct device time; this must be verified in the actual app. No user-CA install, localhost-key reuse, HTTP, badCertificateCallback, custom trust exception or inferred Android success from this host's cURL result. No certificate was issued/imported/changed here.

## 10. Physical Android Connectivity

Current Windows Wi-Fi LAN address is **192.168.1.5**. Existing Apache binds IPv4/IPv6 all interfaces, so a same-LAN phone could theoretically reach it if network/firewall permits. That is the protected instance, not an authorized acceptance target. No request from the phone or proof of its subnet/resolver/client-isolation state is available.

Proposed actual target: `https://kobi-mobile-acceptance.monchez.in:8443/api/v1`, private DNS to this machine and a new listener bound specifically to its LAN IP. Phone must use the approved Wi-Fi/test network with no AP client isolation blocking the route, and correct DNS (Private DNS/VPN settings can alter resolution). Record its IP for firewall scoping; do not alter device settings or connect it now. Physical device removes VM/AVD requirements; it does not remove DNS/TLS/isolation gates.

## 11. Firewall

Effective Windows Firewall profiles are enabled with inbound Block. Existing Public-profile rules allow normal Apache and mysqld TCP/UDP, **Any port / Any remote address**. Existing MariaDB listens broadly; it cannot honestly be described as localhost-only or externally inaccessible. No LAN peer reachability or compromise was demonstrated.

Acceptance needs a new executable path and a narrow TCP **8443** inbound rule scoped to approved phone/operator IPs and local LAN IP. Do not broaden the existing Apache rules; those apply to the protected executable. New MariaDB bind is 127.0.0.1:3307 with no inbound rule, admin panel or PHP internal listener exposed.

Acceptance outbound restrictions must deny protected database/HTTP services and unneeded external access. Validate loopback/filter behavior under the actual acceptance process; Windows firewall-rule presence alone does not prove denial. Any adjustment of existing protected DB/firewall policy requires separate approval. Router port forwarding/public tunnels are unnecessary and excluded. No rule changed during inspection.

## 12. Flutter Endpoint Configuration

`Environment.fromBuild()` uses `--dart-define=KOBI_ENV` and `--dart-define=API_BASE_URL`; it requires HTTPS ending exactly `/api/v1`. It supports port 8443 without source edits. Future values: staging and **`https://kobi-mobile-acceptance.monchez.in:8443/api/v1`**. The host root alone is not a valid API base URL.

No secrets belong in Flutter. Public knowledge requests omit tokens; protected requests use Bearer and the API client follows no redirects or cookies. Existing `.invalid` build remains compile-only. No file changed and no APK build/install occurred.

## 13. Session/Cookie Isolation

Observed PHP defaults: **PHPSESSID**, files handler, **D:\xampp\tmp**, empty cookie_domain, path `/`, auto_start off. Browser bootstrap enables strict/only-cookie mode, then sets session cookies Secure for HTTPS, HttpOnly and SameSite=Lax. API bootstrap skips browser session startup; native API identity is Bearer and uses no cookie jar.

Empty cookie_domain normally means host-only, so ordinary host-only cookies for the two names are not shared. However, a parent-domain `.monchez.in` cookie could reach both; effective public-demo response-cookie policy was not inspected. Port separation **does not isolate cookies**. Sharing D:\xampp\tmp also shares session files even when host-only browser cookie delivery differs. Current CSRF token is stored inside `$_SESSION`, not a separate application CSRF cookie found in the source scan. No other setcookie/auth-cookie implementation was found by that scan; external demo plugins/proxies are not certified.

Required acceptance PHP-only settings: **session.name=KOBIACCEPTANCESESSID**, unique save_path `D:/kobi-mobile-acceptance/var/sessions`, empty session.cookie_domain (no `.monchez.in`), strict mode/only-cookies on, HTTPS Secure/HttpOnly/Lax as existing bootstrap expects. Separate session GC/temp paths and file permissions. Test cookie attributes and independent browser session/logout/CSRF behavior before declaring isolation. No application changes are needed for these supported [PHP session settings](https://www.php.net/manual/en/session.configuration.php).

Subdomains are not an adversarial browser security boundary: parent-domain cookie injection remains a sibling risk. Unique cookie names/stores and private access address controlled acceptance, not arbitrary hostile multi-tenancy. Do not claim a new vhost alone guarantees cookie isolation.

## 14. Log Isolation

Separate Apache access/error logs under **D:/kobi-mobile-acceptance/var/log/apache**, PHP error log under `.../php`, app log under copied `app/storage/logs`, limiter/cache under acceptance storage. Do not symlink/reuse normal logs or D:\xampp\tmp. Separate TLS session cache/PID/temp as well.

Use no Authorization, cookies, bodies, raw tokens or health payloads in access/debug logs; restrict local log access/retention. Generic app exception logging can include details, so validate safe error paths and server settings rather than falsely guarantee all traces are harmless. No log files/directories were created or existing log content read.

## 15. Filesystem Isolation

Proposed root: **D:\kobi-mobile-acceptance**, absent at inspection and outside protected XAMPP/public document root. Suggested children: `app`, `runtime/apache`, `runtime/php`, `runtime/mariadb`, `conf`, `tls`, `var/sessions`, `var/tmp`, `var/log` and `dbdata`. Only `app/public` is served. Root path must be checked for junctions/reparse targets and existing contents before separately authorized creation/deletion.

Copy explicit allowlisted app/resources/public/routes/bootstrap/schema/manifests/dependencies only. Exclude `.env`, .git, .codex, all storage/data/logs/uploads/backups, production smoke credentials, DB directories, TLS keys and mobile SDK caches. Create acceptance configuration and empty storage from scratch; hash application source rather than edit logic.

**Important new ACL evidence:** normal `.env`, storage and mysql/data inherit access for Authenticated Users/Users (including read, and broader rights for Authenticated Users). A new nonadmin Windows account by itself does **not** prove these protected resources unreadable. Normal TLS directory is separately restricted. Process/ACL containment for new acceptance identities must be explicitly designed and validated; do not copy those broad inherited grants or treat PHP open_basedir as a full security boundary. PHP itself cautions that open_basedir is only an extra safety measure: [core ini directives](https://www.php.net/manual/en/ini.core.php).

No ACL was changed. If preventing acceptance identities from reading protected files requires changes to protected ACLs, stop for a specific approval or a different verified restriction mechanism. No blanket production ACL rewrite is implied by this feasibility result.

## 16. Production/Demo Boundary

| Route/resource | Verified mapping / limit |
| --- | --- |
| Local localhost `/kobi/public` | Local protected D:\xampp\htdocs\kobi → configured medical1 on localhost:3306 |
| Public kobi.monchez.in | DNS 216.10.240.202 → verified public wildcard TLS; actual origin/document root/DB **not established** |
| Future acceptance host | Private DNS 192.168.1.5 → independent port 8443 Apache → D:\kobi-mobile-acceptance\app/public → its .env → isolated 127.0.0.1:3307/kobi_mobile_acceptance_60c |

Do not assert public demo equals this workstation or change its DNS/hosting to force the mapping. Both identified local protected resources and the public demo must remain unchanged. Setup must record the real demo origin/routing before any shared-host listener proposal. No demo credential or private health row is needed for that mapping.

## 17. Recommended No-VM Architecture

```text
physical Android on approved LAN
  → private DNS kobi-mobile-acceptance.monchez.in = workstation LAN IP
  → normally trusted HTTPS :8443
  → dedicated acceptance Apache/PHP process/configuration
  → acceptance app/public + independent .env/storage/session/logs
  → dedicated local MariaDB :3307
  → kobi_mobile_acceptance_60c / restricted kobi_mobile_runtime

protected host ports 80/443/3306 and public demo route remain separate
```

New processes use dedicated nonadmin runtime identities with minimum access to acceptance files. Same PHP **version** is fine; independent binaries/config/process avoid global ini/session changes and inherited OS-user privilege. Planned vhost inside that independent instance: ServerName `kobi-mobile-acceptance.monchez.in`, explicit LAN-IP:8443 listener, acceptance public DocumentRoot, own certificate/logs and strict rejection of other hostnames/default-site requests. Preserve copied `.htaccess` and its scoped API Authorization forwarding unchanged.

This revises the conceptual second-vhost proposal: a second vhost **inside existing user-owned XAMPP on 443 is not recommended**. If portless 443 or that shared instance is mandatory, the plan must return for review of default-host behavior, restart risk and per-process PHP/credential boundaries. No automatic public exposure or demo redirect is an acceptable substitute.

No-VM is operational isolation on one trusted host, not VM-grade containment. Restricted DB credentials prevent normal application cross-schema access; they do not protect against a process deliberately using another local account or readable host secrets. File/process and protected-port denial gates must pass, or no-VM setup must be marked blocked at that stage.

## 18. Step 60B-4 Setup Sequence

Future only; not authorized/executed now:

1. Confirm actual public-demo origin/route, physical phone IP/network and acceptance port 8443. Explicitly approve revised independent-instance design and process/ACL restrictions; resolve how protected-file denial is achieved without unapproved protected changes.
2. Record protected config/source fingerprints and exact new resource/removal manifest. Validate D:\kobi-mobile-acceptance and free ports; no normal backup/data dump required.
3. Establish acceptance-only runtime identities/permissions and network restrictions; verify denied protected file/service access without reading health rows or secret contents. Stop if denied-access gates fail.
4. Create isolated directories and independently configured runtimes. Copy only reviewed safe app/dependencies; never normal env/data. Provision local-only MariaDB:3307 and acceptance DB/operator/runtime credentials.
5. Review schema import stream/mode handling, assert empty target, import complete schema and verify constraints/triggers/grants/modes. No correction DDL or relaxed constraints to obtain PASS.
6. Create dedicated certificate via owner-approved DNS-01, install independent vhost/full chain, configure private DNS and narrowly scoped phone→8443 rule. Do not touch demo records/listeners/certificate or expose 3307.
7. Validate host HTTPS/SNI/hostname/default denial, safe unauthenticated API responses and isolation. Verify separate effective session paths/name, logs and cookie attributes.
8. Provision only guarded synthetic fixtures. Validate genuine synthetic login/refresh/logout, Authorization forwarding, read APIs and initial unrecorded Due occurrence; record target and safe persistence evidence.
9. Rebuild via existing staging defines, review artifact hash/endpoint, obtain normal installation approval for the known physical phone, and verify native DNS/TLS/login/read/checkoff smoke behavior.
10. Report readiness only after live smoke/isolation gates pass. Do not begin full 60C without its scope/authorization. Retain manifest-guarded teardown instructions.

Step 60B-4 may begin as a separately authorized design/setup-preflight task, but must not mutate infrastructure until the unresolved boundary/permission choices are approved. This report authorizes no setup.

## 19. Synthetic Fixture Plan

Guest-independent names now refer to the new local acceptance instance only. Use newly generated verified active synthetic local-password accounts A/B; clearly labelled synthetic profile A; current `TEST MEDICATION — NOT FOR USE`; two active slots with known zones/current server-local eligibility and initially **zero checkoff rows**. Add only small viewer/foreign/profile-switch, future-start/inactive/ended/timezone cases required by the smoke/acceptance plan.

Perform unset→Taken on one slot, unset→Skipped on another, then Taken↔Skipped correction. Same-state repeats use a separately approved synthetic API driver where UI hides current-state action. Verify exactly one slot/day row, retained identity/creation label and server-returned state/date; no calendar authority on phone. Keep personal data empty and no governance bootstrap/activation. Broader knowledge/saved journeys require approved synthetic/public reference fixtures compatible with current contracts, not copied accounts/profiles/records.

## 20. Cleanup Plan

Maintain explicit new root, process/service IDs, config paths, DB instance/data directory, DB/account names, certificate fingerprint, private DNS/rule IDs, phone app/artifact identity. Stop only acceptance processes; revoke synthetic families normally if practical. Destroy only acceptance DB/account/data through its own operator/server or remove the stopped independent data directory. Delete the verified non-reparse acceptance root after checking its absolute target; no wildcard cleanup of XAMPP.

Remove only newly owned 8443 rules/private DNS/challenge entries and acceptance certificate/private key; never existing wildcard/demo certificate, DNS or normal firewall rules. Owner removes the test APK/app state on the known phone; installation may share the existing package ID, so backup/replace/uninstall implications must be agreed beforehand. Verify no 8443/3307 test listeners, no stale mappings and no protected config/file changes. No production DB cleanup or data comparison is needed. No teardown occurred here.

## 21. Risk Register

| Risk | Evidence / mitigation |
| --- | --- |
| Mistaking public demo for local origin | Different observed DNS/TLS arrangement, no named local vhost; owner confirms routing before setup |
| Shared Apache/PHP privilege/failure boundary | Current process runs as Sanjog; choose independent restricted process/port/config |
| Readable protected secrets/files | Broad inherited Users/Authenticated Users ACLs; require verified process/file containment or specific approval; no new-user assumption |
| Accidental normal DB connection | Separate instance/port/name/password, no copied env, restricted grants and verified denial of protected services |
| Session/cookie collision | Separate name/store, host-only cookie, no parent-domain scope; port alone does not isolate cookies |
| Public exposure | Split DNS/private LAN, scoped 8443 only; no WAN forwarding/demo DNS alias |
| Certificate/key reuse | Public wildcard exists but custody unknown; issue dedicated leaf/key, no demo TLS edits |
| Native DNS/TLS failure | Actual phone resolver/time/chain must pass; host cURL result insufficient |
| Schema/seed incompatibility | Fresh import not executed; preserve objects/mode, stop on failure, guarded synthetic seeds |
| Wrong-target teardown | Exact process/path/DB/rule manifest; do not remove demo resources |

## 22. External Prerequisites

- Owner confirmation of the actual kobi.monchez.in origin/routing and permission boundary; no credential disclosure required.
- Approval of separate runtime identities/processes, ports 8443/3307, and how protected files/normal services are made inaccessible to acceptance identities. Any protected ACL/firewall change needs its own explicit review; it is not included automatically.
- Owner DNS-01 issuance control for the supplied real hostname, dedicated certificate/full chain and private LAN DNS resolution on the phone.
- Known physical Android device, supported OS, approved network/IP and normal install/debugging method; no device change performed here.
- Available disk/new runtime distribution, restrictive config/session/log paths, schema/fixture import review and cleanup owner. VM/Windows guest licensing/emulator acceleration are not requirements for this selected design.

## 23. Final Feasibility Decision

**READY WITH DESIGN CHANGES.** The actual supplied hostname and physical device remove the previous domain/VM/emulator assumptions. Independent native services/configuration plus normal DNS-01 HTTPS make a private no-VM architecture practical without new application features.

It is **not READY FOR NO-VM SETUP as-is**: current shared Apache identity, broad protected-file ACLs, normal DB privileges/exposure, missing acceptance DNS/certificate and unverified demo origin must be addressed in the revised setup controls. Production/demo isolation is proposed, not proven. If the required process/file/network denials cannot be established without unapproved protected changes, 60B-4 must stop as BLOCKED. Actual physical Android testing remains pending successful setup/native handshake.

## 24. Validation Performed

Read-only Apache version/vhost/config/syntax/PHP integration; process ownership/service identity; PHP ini/session metadata; app env/session/CSRF/storage/logger/routing source; Flutter endpoint/transport; schema/seed/reference inventory; safe MariaDB version/database/grant/mode metadata; file ACL metadata (not secret contents); effective firewall/listeners/LAN; public DNS and **TLS-only** demo certificate handshake with verification enabled. No public demo HTTP/application request or health-data read was made.

Primary Apache/PHP/Let's Encrypt documentation supports server/session/issuance semantics; links appear beside claims. No full regression/build/device test or DDL/fixture harness was run for this feasibility review. Source path/SHA-256 comparison replaced prohibited Git operations. No VM/emulator needed to be started.

## 25. Files Changed

Created only `docs/task-60b3-no-vm-acceptance-feasibility.md`. SHA-256 baseline: **691 existing nonignored project files**; final comparison found no existing file byte changes/removals and only this report added. Inventory excludes `.git`, vendor, storage and ignored/generated caches. No setup file/directory/copy was created. An initial inventory result yielded before completion; the subsequent completed baseline was used, before any write.

## 26. Confirmation of No Implementation

No application/Flutter/native/API/test/schema source, `.htaccess`, Apache/PHP configuration, service/user/process setup, application copy, APK build/install or acceptance environment implementation. No change to kobi.monchez.in. No Step 60B-4/60C execution.

## 27. Confirmation of No Database Mutation

Only metadata SELECT/SHOW GRANTS with sanitized booleans; no row dump, health query, CREATE/DROP/ALTER/INSERT/UPDATE/DELETE/GRANT, migration, mode change, fixture/auth family/checkoff creation or database/user provisioning. No production credentials were disclosed or reused for acceptance. `database/structure.sql` unchanged.

## 28. Confirmation of No TLS/Firewall/DNS Changes

No certificate/private-key/trust-store/TLS, firewall, DNS/hosts/router/listener change; no private key read, insecure fallback or certificate-validation bypass. DNS and the public certificate were observed only. Nothing was installed on the phone.

## 29. Confirmation of No Git Operations

No Git command, commit, push, reset, checkout, branch/merge/rebase/staging/history mutation. File-integrity checks used hashes only.

**STOP after Step 60B-3 feasibility inspection.**
