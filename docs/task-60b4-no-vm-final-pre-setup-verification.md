# Step 60B-4 — No-VM Final Pre-Setup Verification

Date: 19 October 2026. **READ-ONLY; no setup performed.** Includes the owner's hosting correction received during this task.

## 1. Executive Summary

**STEP 60B-4: READY WITH EXTERNAL PREREQUISITES.** The selected no-VM design is independent acceptance Apache/PHP on LAN HTTPS **8443**, an independent local-only MariaDB instance on **3307**, a separate app/config/storage tree and synthetic-only credentials/data. Current port/configuration inspection found no requirement to change the protected host's listeners or application source.

The owner confirms **kobi.monchez.in is on Hostinger shared hosting, does not run on this workstation and does not proxy to it**. That resolves the previous origin ambiguity. Windows `medical1` and local XAMPP remain protected development resources in their own right; neither they nor the Hostinger demo are acceptance resources.

Before implementation, the owner must supply/approve the private DNS mechanism, DNS-01 certificate provisioning route and the phone's same-LAN IP/installation method. The future setup authorization must also cover acceptance runtime identities and narrowly scoped permission/network controls; a new user alone does not overcome current broad file permissions. These are explicit setup gates, not controls installed or tested here. Actual Android connectivity/trust is unverified until setup.

No VM, emulator acceleration, Windows guest license or 80 GiB VM disk budget is needed for this revised physical-phone design. No full acceptance, deployment, code fix or infrastructure change occurred.

## 2. 60B-1/60B-2/60B-3 Baseline

Read the current 60B-1/60B-2/60B-3 reports and project instructions/schema/mobile configuration. 60B-1 selected a VM with unresolved prerequisites; 60B-2 stopped correctly; 60B-3 established conditional no-VM feasibility and identified the unresolved demo route, shared sessions and broad file/network permissions.

The latest owner correction supersedes the assumption that public-demo mapping might involve this local copy. The revised architecture still preserves the same independent-config/credential/trust/synthetic-data safety boundary. Earlier reports were not edited to rewrite historical observations.

## 3. Existing Demo Origin

| Question | Established answer / evidence limit |
| --- | --- |
| Machine/provider | Hostinger shared hosting, independent of this workstation: direct owner confirmation |
| Public address | Fresh DNS: kobi.monchez.in and monchez.in both A **216.10.240.202** |
| Proxy to workstation | **No**, owner confirmed |
| Serving Apache instance | Hostinger-managed web service; its exact server software/process identity is not exposed by local inspection and is not assumed |
| Public HTTPS port | 443; previous 60B-3 verified a normal TLS handshake there |
| Remote document root/database | Hostinger account's deployed KOBI/root/DB; exact filesystem path/schema name not supplied and not guessed |
| Local origin | Local XAMPP localhost `/kobi/public` → D:\xampp\htdocs\kobi → medical1 is a separate protected development deployment |

There is no need for Hostinger admin credentials or demo data to create the independent Windows acceptance service. No demo HTTP/app request, login, control-panel access or remote configuration change was made. A local 8443 process cannot displace Hostinger's 443 service; DNS mistakes could still direct clients to the wrong machine, so exact hostname resolution is a setup gate.

## 4. Apache Findings

Local Apache **2.4.54 Win64** at `D:/xampp/apache`; `httpd -S` shows only default `*:443 localhost`, main document root `D:/xampp/htdocs`. Active Listen 80/443 are wildcard/all-interface. No named local kobi.monchez.in vhost or active proxy mapping was found in inspected configs. `httpd -t`: **Syntax OK**. Targeted process inventory found only the two local httpd processes, no cloudflared/ngrok/php-cgi; mysql service is running as LocalSystem. Prior process-owner inspection identified httpd as Sanjog.

**Select option B: separate acceptance Apache process on 8443.** Option A, an extra vhost in normal Apache, would share OS privileges, loaded PHP/global settings, restart/failure scope and default-host selection. Separate process/config/PID/logs/TLS on 8443 keeps changes and teardown off normal 80/443. Additional operational setup is justified by the observed shared permissions/session defaults.

Future acceptance vhost: explicit ServerName `kobi-mobile-acceptance.monchez.in`, listener on the reserved workstation LAN IP:8443, DocumentRoot `D:/kobi-mobile-acceptance/app/public`, own certificate/logs, default/unknown-host denial. Copy `.htaccess` unchanged; preserve its front controller and scoped Bearer forwarding. Verify `/me`/profile/checkoff routes through real Apache later. No changes, reloads or restarts were performed now.

## 5. Filesystem Isolation

Protected app: **D:\xampp\htdocs\kobi**, observed as a normal directory, not a junction. Proposed new root **D:\kobi-mobile-acceptance** is absent and lies outside normal XAMPP document root. Do not use a sibling inside D:\xampp\htdocs where the existing default server could expose the new tree.

Future independent layout: `app/public`, `app/storage`, `runtime/apache`, `runtime/php`, `runtime/mariadb`, `conf`, `tls`, `dbdata`, `var/sessions`, `var/tmp`, `var/log`. Verify target/parent reparse behavior and existing contents before creation/deletion. Do not share or link normal paths.

Documents use configured storage plus `health-documents/<profileId>`; cache/limiter use storage; Logger writes app-relative `storage/logs`; PHP sessions currently use D:\xampp\tmp. All must be independently configured/recreated. Copy only approved code/resources/public/dependency/schema files, excluding normal `.env`, storage contents, uploads/documents, DB data/dumps, credentials, .git/.codex, TLS keys and caches. No directory or copy was created.

**Permission gate:** fresh ACL metadata confirms local protected `.env`, storage and mysql/data inherit Users read/execute and Authenticated Users modify access. Separate standard accounts alone do not make these unreadable. Future implementation needs dedicated acceptance identities and explicit tested restrictions. Any required ACE changes on protected paths need specific owner approval; do not silently rewrite inherited ACLs or claim open_basedir is OS containment. Stop setup before fixture/service admission if required file/service denial cannot be established within authorized scope.

## 6. Configuration Isolation

`env()` reads the `.env` beside that copy's app root once per PHP request and places values in `$_ENV`. `app/Config/config.php` reads supported keys; Database builds a PDO DSN from DB_HOST/DB_PORT/DB_DATABASE plus DB_USERNAME/DB_PASSWORD. Relevant production DB names are not hardcoded in this normal runtime class; test fixture scripts have their own hardcoded normal-source assumptions and must not be copied/executed as provisioning scripts.

Acceptance configuration will be generated explicitly, **not copied from normal env**, with complete required keys:

| Key | Future value |
| --- | --- |
| APP_NAME | KOBI MOBILE ACCEPTANCE — SYNTHETIC ONLY |
| APP_URL | https://kobi-mobile-acceptance.monchez.in:8443 |
| APP_BASE_PATH | Empty |
| APP_TIMEZONE / APP_DEBUG | Asia/Kolkata / false |
| DB_HOST / DB_PORT | 127.0.0.1 / 3307 |
| DB_DATABASE / DB_USERNAME | kobi_mobile_acceptance_60c / kobi_mobile_runtime |
| DB_PASSWORD / API_AUTH_RATE_KEY | Unique acceptance-only secrets, values generated/stored server-side later |
| APP_STORAGE_PATH | D:/kobi-mobile-acceptance/app/storage |

New PHP/Apache process has its own ini/environment, no normal secret inheritance, dedicated temp/session/error/SSL cache paths and independently reviewed DLL/extension locations. Reusing the same compatible PHP **version** is feasible; pointing the new process at normal php.ini is rejected. Existing PHP binary distributions may be copied/reused read-only if provenance/dependencies are verified, without editing originals. Separate binaries/config paths simplify rule scoping and teardown.

Future preflight must assert every required key, actual DB/server and resolved path before any application request/write. Missing config must not fall back to normal host/port/user settings. No acceptance copy exists now, so no inherited production credentials were discovered. The proposed allowlist/config procedure avoids inheritance; do not create a copy and then scrub production secrets after exposure.

## 7. Session/Cookie Isolation

Fresh CLI/ini values: PHP 8.2.0, loaded `D:\xampp\php\php.ini`, name **PHPSESSID**, file handler, path **D:\xampp\tmp**, empty cookie_domain, cookie path `/`, auto_start off. Browser bootstrap enables strict/only-cookie mode and sets Secure for HTTPS, HttpOnly, SameSite=Lax. On native API requests it skips browser session startup; API client uses Bearer, no cookie jar and no redirects.

Host-only cookies for the two different names normally remain separate. A `.monchez.in` parent-domain cookie can reach both; port 8443 is not a cookie boundary. Public demo's actual cookie attributes were not requested or inferred from local PHP. Current KOBI CSRF token lives inside `$_SESSION`; no separate auth/CSRF setcookie path was found in the focused source inspection.

Future acceptance ini: **session.name=KOBIACCEPTANCESESSID**, independent `D:/kobi-mobile-acceptance/var/sessions`, empty cookie domain, strict/only-cookie mode, separate GC/temp paths; existing bootstrap preserves HTTPS Secure/HttpOnly/Lax. Do not set `.monchez.in`. Validate effective browser attributes/session/logout independence later without operating a real demo session. Private storage and unique names also prevent shared session-file/GC collisions. No session config, browser session or cookie was changed here.

## 8. MariaDB Isolation

Read-only metadata: MariaDB **10.4.27**, normal `localhost:3306`, schema **medical1**, current configured account differs from proposed `kobi_mobile_runtime`, has global ALL PRIVILEGES and no configured password. No username/password/hash was disclosed. The inspected mysql.user plugin field for the current identity was empty; no undocumented authentication mechanism is inferred from that alone.

Current normal listener is `:::3306`, **not verified localhost-only**. Do not change its global bind/users/grants to provision acceptance. Selected design is a separate native MariaDB instance with independent config/data/admin identity at **127.0.0.1:3307**, schema `kobi_mobile_acceptance_60c`. Future loopback-only binding is feasible without occupying normal 3306; no DB inbound firewall rule or phone connection is required.

Runtime account `kobi_mobile_runtime`, unique generated password/local host scope; planned SELECT on acceptance schema plus INSERT/UPDATE on API session/refresh tables and schedule checkoffs. Operator owns schema/fixtures/cleanup. No global/DDL/FILE/GRANT privileges for runtime. Validate locking/upsert under exact final version/grants; do not grant ALL as a workaround. Distinct instance/name/user/password/config prevents ordinary misrouting; file/process/network restrictions remain necessary to prevent access using other host privileges.

MariaDB supports this model; no architectural impossibility was found. No second instance/database/user was created and no least-privilege denial test against a future account can be claimed yet.

## 9. Database Schema Readiness

Unchanged `database/structure.sql` contains the current application/auth/saved/schedule/checkoff tables, FKs/CHECKs/indexes and governance/auth triggers. There are no INSERT seed statements in the inspected source and no turnkey installer/seed from the prior focused scan. Stored reference/fixture data is additional to schema; disease source attribution also reads `resources/disease-references.php`.

The file includes DROP statements, constraint-control directives, DELIMITER/trigger definitions and mode-changing directives at lines **9, 13, 1603**. Future empty-target import requires a reviewed stream excluding only those mode directives under setup authorization, original source unchanged, all other objects accounted for and verified. Do not change SQL mode, relax constraints, replay duplicate migrations or silently omit failing statements. Normal current session/global mode is `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION` (fresh metadata confirmed). A new instance's actual baseline must be checked and any mismatch reported before import rather than silently altered.

Complete fresh import has **not** been executed in this task; compatibility is a setup gate, not a certified PASS. Synthetic fixtures must satisfy account/member/medicine/FK/date relationships; no production DB dump is required. Do not run existing CLI fixtures unmodified: they assert different DB identities and a normal-source connection.

## 10. DNS Findings

Fresh DNS: monchez.in and kobi.monchez.in A **216.10.240.202**; acceptance name **NXDOMAIN**. Owner controls the domain and can create required DNS records via its control panel. These public records route to the Hostinger demo, not proof of the Windows host's WAN address.

Selected acceptance routing: **private/split DNS A `kobi-mobile-acceptance.monchez.in → 192.168.1.5`**, after confirming/reserving that LAN address. Phone and operator must use the approved private resolver/override. Public authoritative DNS needs only the DNS-01 challenge TXT for issuance; public acceptance A/AAAA is unnecessary for private testing. Do not point the acceptance name at Hostinger's demo address or create WAN forwarding merely to obtain a certificate.

DNS resolution, TCP reachability and certificate validation are separate gates. An A record has no port; app uses :8443 explicitly. Windows hosts alone does not configure Android. Owner-provided LAN DNS capability/phone resolver remains an external prerequisite; no record or hosts file was changed.

## 11. HTTPS Findings

Current **local** default certificate is the separate KOBI development localhost leaf under D:/xampp/apache/conf/kobi-local-tls, SAN localhost only. It does not match the acceptance FQDN and is not the chosen trust route.

Prior Step 60B-3 TLS-only inspection of **Hostinger demo** showed a verified Let's Encrypt YR2 leaf, CN www.kobi.monchez.in, SAN `*.monchez.in` and `www.kobi.monchez.in`, valid 21 August 2026 18:51:15 UTC–19 November 2026 18:51:14 UTC. Public wildcard matching does not authorize copying its private key; local key custody is not established. This task made no new demo connection or application request.

Selected certificate: a **new dedicated publicly trusted leaf** for exact SAN **kobi-mobile-acceptance.monchez.in**, new private key/full intermediate chain in `D:/kobi-mobile-acceptance/tls`, restricted to the acceptance runtime/operator. Android must validate normally with correct time and supported chain; actual phone verification remains pending. No CA import, bypass, badCertificateCallback, HTTP fallback or production certificate reuse.

Correct order: owner DNS control → DNS-01 TXT publication/validation → dedicated certificate issuance → independent Apache HTTPS configuration; private A/resolver routing can be prepared independently → host DNS/TCP/TLS validation → actual Android app validation. **A public A record or public inbound port 80/443 is not required for DNS-01**. See [Let's Encrypt challenge types](https://letsencrypt.org/docs/challenge-types/). DNS credentials stay with the operator, never Apache/Flutter. No issuance was requested.

## 12. Port 8443 Findings

Fresh TCP listener inventory found **no 8443 listener**. IPv4 and IPv6 excluded TCP ranges contained only **50000–50059**, so 8443 is not in those exclusions. Portproxy inventory showed no configured mappings. No bind/test listener was opened, so successful future bind is not falsely claimed.

**Retain 8443** as suitable candidate: unused/unexcluded by inspected state, no demonstrated conflict with protected 443. Recheck immediately before setup for races/reservations; do not displace another process. Existing firewall policy is a distinct access concern, not a port reservation. 3307 also had no observed listener.

## 13. Firewall Findings

Current Wi-Fi profile is Public. Prior effective profiles enabled inbound default Block; fresh enabled-rule inspection again found broad Public TCP/UDP Any-port/Any-remote rules for **normal XAMPP executable paths**. No explicit 8443 rule was returned by the focused filter. This does not prove absence of every possible overriding third-party/filter rule.

Future new acceptance executable path requires a **new narrowly scoped inbound TCP 8443** rule: approved LAN local IP, phone/operator remote IPs, appropriate active profile. Do not alter/reuse broad normal Apache rules or disable Firewall. Local-only acceptance MariaDB 3307 and PHP internal/administrative interfaces receive no LAN rule. Existing normal 3306 is broadly bound/allowed and must not be labelled protected by loopback alone.

Future acceptance-process egress restrictions and proof of denied normal-service access are setup controls, not installed policy. Do not infer loopback denial from a rule's presence; verify the selected Windows enforcement mechanism. Any needed change to protected rules/permissions requires explicit approval; otherwise stop. No firewall or service change now.

## 14. Physical Android Network Requirements

Current Windows **Wi-Fi 192.168.1.5**, gateway 192.168.1.1, Public profile. Owner confirms phone IP is **unknown** and will be determined when both join the same LAN. No phone connection, ARP/phone probing or device setting was performed; actual Wi-Fi peer connectivity/client isolation cannot be certified from the host alone.

Required later: same approved LAN/test Wi-Fi, working private DNS for the FQDN, unblocked phone→192.168.1.5:8443 route, normal TLS/full chain/device time and supported Android API **24+**. Private DNS/VPN/router client isolation may affect routing and must be checked by owner, not silently disabled.

APK can be transferred/installed through an owner-approved local file/wireless/manual method; **USB debugging is not mandatory**. Use USB/ADB only if desired and separately authorized on the known phone. Android may require the owner to permit installation from the chosen source; do not change that setting now. The current app requires INTERNET, not SMS/location/contact/health permissions; no broad phone permission change is needed. Confirm whether the existing package ID is installed before replace/uninstall, to avoid destroying real app state.

## 15. Flutter Endpoint Findings

Existing `Environment.fromBuild` supports compile-time HTTPS URL including explicit port and requires `/api/v1`; no source change needed. Future build inputs:

```text
KOBI_ENV=staging
API_BASE_URL=https://kobi-mobile-acceptance.monchez.in:8443/api/v1
```

Root URL alone is insufficient. Build helper scopes environment variables; no production endpoint mutation or hardcoded LAN IP is necessary. APK has public endpoint/label only, never DB/password/limiter/DNS/private-key material. Default/native trust, no redirects/cookies and Bearer behavior remain unchanged. No build/install was run; `.invalid` artifacts remain compile-only.

## 16. Synthetic Data Plan

Normal registration hashes a local password, but login requires active status, verified email and (for API) password_set_at. `AuthService::verifyPasswordRecord` checks these server-side without modifying user eligibility. A synthetic `.invalid` email cannot receive ordinary real-world verification mail, so do not pretend unactioned registration becomes API-ready automatically.

Use a future **guarded acceptance-only operator fixture procedure** after exact independent instance/schema assertions: create a clearly synthetic active verified local-password account with locally generated password hash/password_set_at, profile/owner membership, current test medicine, two active slots with known zones/current local effective dates and zero initial checkoffs. Preverification is explicit administrative fixture setup in the isolated DB, not an auth bypass in production or a changed API. It needs bootstrap/operator grants, not expanded runtime privileges. No Google/real-account bootstrap or external mail is required.

Prepare distinct inactive/future/ended/foreign/viewer/profile-switch cases only as needed. Test unset→Taken, independent unset→Skipped and both corrections/repeats with real synthetic authentication and one slot/day persisted row; server controls returned date/state. Broad V1 knowledge/history requires approved synthetic/public reference fixtures and valid snapshots, not copied personal records or invented citations. No fixture/admin tool was written or run here.

## 17. Log Isolation

Future Apache access/error logs: `D:/kobi-mobile-acceptance/var/log/apache`; PHP errors: `.../php`; app Logger: copied `app/storage/logs`; separate cache/limiter/sessions/temp. Configure own PID/SSL cache and no links to normal logs. No payload/Authorization/cookie/token logging or credentials in process arguments. Restrict log access/retention and validate safe failure paths; do not claim app exception traces inherently cannot contain sensitive values. Nothing was created/read from existing logs.

## 18. Cleanup

Independent removal is feasible by manifest: exact new process/service IDs, root/ini/vhost/TLS/data paths, DB instance/schema/account, certificate fingerprint, private DNS/challenge record identifiers, scoped firewall rules and APK hash/device installation state. Stop only acceptance processes; revoke synthetic families normally if practical; remove independent DB/user/data or its stopped dedicated data directory; remove the checked acceptance root, new DNS/TLS/rules/logs and owner-approved test app state.

Never edit Hostinger demo resources or normal XAMPP during teardown; no wildcard deletion or reuse of mysql service names. Validate resolved root remains inside the acceptance path before any recursive deletion. If setup adds specifically approved protected-path ACEs for new acceptance identities, save/restore only those exact entries and verify original ACLs; no broad ACL reset. Test snapshots/artifacts/secrets are disposable. No teardown now.

## 19. Security Review

| Boundary | Final selected control / verification gate |
| --- | --- |
| Hostinger demo vs Windows | Independent hosting confirmed; no demo route/config/key/DB access needed |
| Protected local DB vs acceptance | Separate 3307 instance/name/user/password, own data directory, least privileges and target assertions |
| Files/credentials | Explicit allowlist copy, no normal env, new restricted identities; protected-path denial must be specifically authorized and tested given broad inherited ACLs |
| Browser sessions | Unique name/store, host-only cookie, no parent domain; port alone is insufficient |
| Uploads/documents/cache/logs | Independent app root and configured writable paths, no symlink/data copy |
| API tokens | Normal synthetic Bearer login/rotation; no cookie fallback, key/token logs or client secrets |
| TLS | New dedicated public chain/SAN, normal Android verification, no demo/localhost key reuse |
| LAN exposure | Private DNS/8443 phone-scoped access, no WAN forward or public demo alias |
| DB exposure | New bind 127.0.0.1:3307, no LAN rule; existing 3306 exposure remains an existing protected-host issue, not acceptance authorization |
| Routing | Dedicated process/IP:8443, exact hostname/default denial, preserved API rewrite/Authorization |

No-VM is trusted-host operational isolation, not a guarantee against malicious administrators or arbitrary native-code compromise on the shared Windows OS. A new username/open_basedir alone does not meet the stronger file boundary. The selected controls can be implemented with normal Windows permissions/process/network mechanisms, but setup must prove them and stop on any failure; no unapproved protected permission changes are implied. No architecture-level impossibility was demonstrated, so the decision is external/setup gates rather than an asserted successful sandbox.

## 20. Final Architecture

```text
PHYSICAL ANDROID (owner-approved same LAN)
  → private DNS + normally verified HTTPS
  → kobi-mobile-acceptance.monchez.in:8443
  → dedicated acceptance Apache/PHP process + independent ini/TLS/logs
  → D:\kobi-mobile-acceptance\app\public
  → acceptance-only .env/storage/session/cache configuration
  → local-only acceptance MariaDB 127.0.0.1:3307
  → kobi_mobile_acceptance_60c / restricted kobi_mobile_runtime
  → synthetic data only

kobi.monchez.in:443 → Hostinger shared hosting → existing demo KOBI/DB
  (not hosted on or proxied to this Windows machine)

localhost:80/443 / D:\xampp\htdocs\kobi → medical1:3306
  (separate protected local development deployment; unchanged)
```

Select **separate process**, not a new vhost in the existing process. No VM requirement; physical phone is primary.

## 21. External Prerequisites

1. Owner chooses/provides an actual private DNS resolver/override route for phone and host; reserve/revalidate 192.168.1.5. Domain ownership and Hostinger separation are now confirmed, not missing.
2. Owner supplies DNS-01 control/approval to publish the scoped challenge and obtain a dedicated leaf/full chain for the exact name; keep secret credentials out of report/Apache/Flutter. No public API A record is required.
3. Phone IP, Android version, shared-LAN reachability and approved transfer/install method; phone/resolver/manual settings remain to be checked later.
4. Separate setup authorization for runtime identities, application/runtime copy, independent DB, scoped firewall and permission controls. In particular, any protected ACL entry needed to deny **new acceptance identities only** must be explicitly approved, scoped and reversible; otherwise an equivalently verified restriction is required before admission. No global/demo ACL/config change is automatically authorized.
5. Approved independent runtime distributions/config paths, available capacity, guarded import/fixture/teardown procedure; final effective SQL mode and denied protected access are ordinary stop-before-write setup validations.

The underlying product/API is sufficient; no additional product-development phase is required. Hostinger remote filesystem/DB details are not needed to provision Windows, but they remain unspecified and must not be invented in reports.

## 22. Exact Setup Sequence

Future separately authorized implementation, not executed:

1. Record resolved hosting boundary and owner-approved private DNS/phone/IP/install choices, runtime/ACL scope and resource manifest; reserve/recheck LAN IP and ports 8443/3307.
2. Prepare DNS-01 TXT validation/issuance and dedicated key/full chain without publishing a public API route. Prepare private A/resolver mapping independently; preserve demo records.
3. Establish acceptance identities and file/network restrictions first; record reversibility and prove no protected-file/normal-service access. Stop before app admission if controls fail or require unapproved protected changes.
4. Create verified new root and independent runtimes/config/PID/log/temp/session paths. Transfer allowlisted application/dependencies, no normal secret/data files; explicitly generate complete acceptance `.env`.
5. Initialize independent loopback-only MariaDB:3307; assert instance/data directory; create only acceptance schema/operator/runtime users with unique server-side secrets.
6. Review/authorize import stream, verify unchanged required SQL mode, import complete schema into asserted empty target and inspect actual constraints/triggers/grants; stop on incompatibility.
7. Create guarded synthetic eligibility/profile/medicine/slot fixtures with zero initial checkoffs and no real data. Keep operator credentials inaccessible to runtime.
8. Configure independent Apache/PHP vhost/listener :8443, dedicated TLS chain/key, copied unmodified routing/Authorization rule, exact-host rejection and own logging/session policies.
9. Add only narrowly scoped phone/operator→8443 rule; no DB/admin port or existing broad-rule changes. Verify local effective configuration, name resolution, TCP/TLS/hostname/chain, unauthenticated API responses, synthetic auth/read/persistence/isolation and separate sessions.
10. Owner validates phone resolver/path/certificate; produce acceptance APK with existing staging defines, verify endpoint/hash/no secrets and install by approved method. No mandatory USB debugging.
11. Perform only separately authorized environment smoke/native connectivity checks; reconcile synthetic checkoffs, record safe evidence/cleanup instructions. Declare ready for full Step 60C only after actual Android HTTPS and smoke pass, then await its authorization.

If any stage fails, stop and report exact evidence rather than changing app/source/schema/modes/trust or falling back to protected KOBI. No existing service restart is needed for this separate-instance design.

## 23. Final Decision

**STEP 60B-4: READY WITH EXTERNAL PREREQUISITES.** Technical choices are independent process/root/config/session/TLS/DB, private DNS, public DNS-01 certificate, phone-scoped 8443 and synthetic fixtures. Hostinger origin correction removes the earlier hosting ambiguity; 8443 is suitable by inspected listener/exclusion state.

This is **not READY FOR IMPLEMENTATION without prerequisites/authorization**, nor a claim of live isolation/Android PASS. Owner DNS/certificate/network/install/permission scope and future effective configuration tests remain gates. The no-VM architecture has no demonstrated fundamental incompatibility; if protected-access restriction or clean schema/runtime requirements cannot be met safely during setup, stop as BLOCKED before writes. Step 60C remains unstarted.

## 24. Validation Performed

Fresh read-only Apache vhost/syntax/config/proxy/routing inventory; Windows LAN/profile/listeners/services and targeted process presence; IPv4/IPv6 TCP exclusion and portproxy inventory; effective relevant inbound firewall rules; PHP ini/session metadata; filesystem/reparse/ACL metadata; app env/DB/session/log/document/authentication code; schema tables/mode/seed and fixture references; Flutter endpoint configuration; DNS queries; sanitized MariaDB version/selected schema/grant/plugin/mode metadata. No account names/passwords/hashes/private keys/token values were printed.

The owner answered the pending origin/phone question during this task. External TLS facts are explicitly prior 60B-3 inspection, not a new demo test. No public-demo application call, browser session operation, phone connection, bind/listener probe, restart/reload, DDL/fixture, build/install or full regression rerun. Primary DNS-01 documentation is linked where used. No Git command.

## 25. Files Changed

Created only `docs/task-60b4-no-vm-final-pre-setup-verification.md`. SHA-256 baseline: **692 existing nonignored project files**; final comparison found no existing-file byte changes/removals and only this report added. Inventory excludes .git, vendor, storage and ignored/generated tooling caches. No runtime/directory/config/setup script was created.

## 26. Confirmation of Read-Only Execution

All environment operations were read-only; the requested report is the only written artifact. No application/Flutter/native/API/test/config/schema edits, copying, service change/restart/reload, fixture, certificate, DNS, ACL, firewall, APK or phone change. No Hostinger/demo configuration touched and no Step 60C work.

## 27. Confirmation of No Database Mutation

Read-only metadata SELECT/SHOW GRANTS only; no health rows, real authentication lifecycle, DB/schema/user/grant/data creation/modification/deletion, import/migration, password change or SQL-mode change. Structure source unchanged. No acceptance row counts exist to certify yet.

## 28. Confirmation of No DNS/TLS/Firewall Change

No DNS/hosts/resolver, TLS/certificate/private-key/trust-store, network/router/firewall/listener or device-security change. No exposed port, public forward or verification bypass. Proposed settings remain report text only.

## 29. Confirmation of No Git Operation

No Git command/staging/commit/push/reset/checkout/merge/rebase/branch/history operation. Source integrity checked with hashes only.

**STOP after the Step 60B-4 report; no implementation or Step 60C execution.**
