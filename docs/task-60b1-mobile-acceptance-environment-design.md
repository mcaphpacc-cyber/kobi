# Step 60B-1 — Isolated Mobile Acceptance Environment Design

Date: 19 October 2026. **Design only; no setup commands were executed.** All paths, names, grants and configuration values labelled proposed below describe future resources, not resources created by this task.

## 1. Executive Summary

**STEP 60B-1: READY WITH DESIGN BLOCKER.** The isolation design is defined, but unconditional setup approval would be premature. Required external decisions/assets are an approved, usable Windows-guest VM platform/guest image and adequate storage, plus an owner-controlled test DNS name/certificate issuance route. Android acceleration also needs resolution before emulator acceptance. No Mobile V1 implementation defect is inferred.

Recommend **one disposable Windows VM with its own Apache, PHP, MariaDB, application copy, synthetic data and credentials**, plus a host-side API 34 Android emulator as the first target. The guest must have no production disk mounts, secrets or DB connectivity. Windows guest choice preserves the current Windows deployment/file-path behavior without introducing a Linux port or product fixes during acceptance.

Do not reuse normal XAMPP, its Apache process/vhost, global DB account or certificate. Use a publicly trusted certificate for `kobi-mobile-acceptance.<owner-controlled-zone>`, obtained through DNS validation, with private DNS routing to the VM. The zone is an unresolved owner input; no actual domain is invented. No custom Android trust configuration or certificate-validation bypass is needed with this choice.

Step 60B-2 may begin only after its explicit setup authorization and the gates in section 23 are satisfied. It must not silently install a hypervisor, change Windows security/virtualization, use a different trust approach or substitute protected XAMPP. Step 60C remains unstarted.

## 2. Current Machine Inventory

| Component | Evidence |
| --- | --- |
| Host | `LAPTOP-PTFFHOMV`, Windows 11 Home Single Language 10.0.26300, 64-bit |
| CPU/memory | Intel i7-1065G7, 4 cores/8 logical processors; about 15.75 GiB visible RAM, about 7.36 GiB free at inspection |
| Free disk | C about 28.61 GiB; D 32.86 GiB; E 43.69 GiB; F 53.30 GiB; measurements are transient |
| Virtualization | HypervisorPresent=true; CIM firmware-virtualization/SLAT fields false. These mixed results do not prove an available general-purpose VM platform |
| VM tools | VirtualBox/VMware executables absent at inspected standard locations; `vmcompute.exe` exists, which alone does not establish a usable Windows-guest hypervisor |
| LAN | Wi-Fi `192.168.1.5`, current Public network profile; protected ports 80/443 on all IPv4/IPv6 interfaces, DB 3306 on `::`; no inspected 3307/8443/5037 listener |
| Apache/PHP | Apache 2.4.54 Win64; PHP 8.2.0 ZTS, loaded `D:\xampp\php\php.ini`; Apache uses `php8apache2_4.dll` from normal PHP |
| PHP extensions | Relevant installed: PDO/pdo_mysql, mysqli/mysqlnd, curl, openssl, mbstring, intl, json, session, fileinfo, hash, random, tokenizer, xml/dom and zip |
| MariaDB | 10.4.27; normal configuration `D:\xampp\mysql\bin\my.ini`, normal data directory `D:\xampp\mysql\data` |
| Flutter/Java | Flutter 3.47.7, Dart 3.13.5; JDK 21.0.12.1+1; Gradle wrapper 9.3.1 |
| Android | Project SDK API 35/36, ADB 1.0.41/platform-tools 37.0.1; user SDK emulator 34.2.15.0 and Google APIs API 34 x86_64 image revision 8 |
| AVD/device | Default/project AVD directories absent; Step 60B's ADB list was empty; no new device was connected/inventoried through a started adb server in this task |
| Certificate tools | Existing OpenSSL 1.1.1p, Windows certificate tooling; existing localhost development CA/leaf, unsuitable as-is for the proposed name |

The emulator's read-only `-accel-check` reported **status 6**, with “Hyper-V detected and Windows Hypervisor Platform is available” and an instruction to ensure features are enabled. This is not a usable-acceleration PASS. No BIOS/Windows feature/security change was made. Android documents the platform acceleration requirements in its [emulator guide](https://developer.android.com/studio/run/emulator-acceleration).

## 3. Current KOBI Environment

Protected application root: `D:\xampp\htdocs\kobi`. Config is loaded through `app/Helpers/functions.php` from that root's `.env` and `app/Config/config.php`. Current observed values from Step 60B: APP_URL `http://localhost`, APP_BASE_PATH `/kobi/public`, timezone Asia/Kolkata, DB host localhost/port 3306/database `medical1`. No credential values are reproduced.

Apache main root is `D:/xampp/htdocs`; one default SSL vhost `*:443 localhost` is active. `httpd -S` and `-t` were rechecked: same default vhost and Syntax OK. Existing API route/Authorization forwarding remains in `public/.htaccess` and `routes/web.php`.

Step 60B's host PHP/cURL public catalog read returned HTTP 200/TLS verify 0; that is prior host-only evidence, not a new device/acceptance-host test. No normal endpoint was called here. Existing loopback CLI fixtures are not persistent Apache environments and must not be repurposed for device exposure.

The attachment names `docs/task-60a-mobile-acceptance-environment-readiness.md`, which does not exist. The actual environment assessment is `docs/task-60b-mobile-acceptance-environment-readiness.md`; that report and the actual 60A release-readiness report, 59A–59C evidence and implementation were used. No replacement/duplicate report was created to repair the filename.

## 4. Production Boundary

| Protected resource | Must remain untouched |
| --- | --- |
| App/config/data | `D:\xampp\htdocs\kobi`, its `.env`, storage, secrets and any normal health records |
| Apache | `D:\xampp\apache`, normal httpd/SSL/vhost config, ports 80/443 and running processes |
| PHP | `D:\xampp\php\php.ini`, extensions/modules/CA configuration |
| DB | `medical1` on normal localhost:3306; normal accounts/grants and `D:\xampp\mysql\data` |
| TLS | `D:\xampp\apache\conf\kobi-local-tls`; existing CA, localhost leaf/private key and trust stores |
| Mobile | Existing source, SDK configuration, current app identity and production endpoint/configuration |

Step 60B read-only metadata found a globally privileged normal DB account and no configured password. Do not copy/reuse that account. This task did not re-read credentials or authenticate to MariaDB. Proposed guest must neither possess normal credentials/files nor reach the protected host's DB/HTTP endpoints.

## 5. Recommended Isolation Architecture

| Option evaluated | Decision |
| --- | --- |
| Separate directory only | Reject: same runtime/network/credential boundary |
| Extra vhost in existing Apache | Reject: changes protected hosting, shares process/PHP context and broad existing firewall allowance |
| Separate host-native Apache/DB processes | Better, but not selected: OS/file/loopback restrictions would need proof while normal DB has powerful unpassworded access; same user could still read normal config |
| Disposable VM, independent services and guest firewall | **Selected:** stronger disk, credential, process and network boundary; destruction does not require normal-server rollback |

Proposed VM name `KOBI-Mobile-Acceptance-60C`; host container directory **`F:\KOBI-Mobile-Acceptance-60C`**, only after confirming that drive/location and additional space. Guest app root **`C:\KobiAcceptance\app`**. Guest services use their own ports 443 and loopback 3306, which do not collide with host XAMPP. No host port mapping or production restart.

Initial budget: 2 vCPUs, 4 GiB guest RAM, 64 GiB guest virtual disk, plus Android emulator 2 GiB RAM and its data. Reserve at least 80 GiB free host disk for guest growth and bounded snapshots before setup. Current largest inspected free volume is about 53 GiB, so additional capacity/location is a real gate. Do not promise that a thin disk's initial small size removes growth risk. RAM is also tight; check sufficient free memory before running both targets. No user processes were closed to obtain resources.

No general-purpose VM platform or Windows guest license/image is established. Owner-approved compatible virtualization must be verified without disabling host security or protected services. This is a design dependency, not authorization to install infrastructure. No particular commercial product purchase is prescribed.

## 6. Application Isolation

Source: `D:\xampp\htdocs\kobi`. Proposed destination: **inside the guest only**, `C:\KobiAcceptance\app`; not another folder under host XAMPP. Stage an explicit allowlist of current app/bootstrap/public/routes/resources/database sources, composer manifest/lock and required dependencies. Transfer once with a reviewed archive/manifest; disable shared folders, clipboard/drive redirection and production mounts afterward. Do not configure a live host-source share.

Exclude `.env`/all env copies, `.git`, `.codex`, `.agents`, storage contents/backups/uploads/documents/logs, protected database dumps, TLS directories/private keys, credential files, SDK caches, APK/build artifacts and test smoke scripts/results containing normal-environment assumptions. Do not bulk-copy the project tree or XAMPP configuration. Recreate empty writable storage/cache/log/session/temp directories in the guest. Logger currently writes under the copied application's `storage/logs` directly, so separating only APP_STORAGE_PATH is insufficient; the whole app root must be independent.

Copy byte-identical application code and `public/.htaccess`; record a source hash manifest. Install dependencies from the existing lock or transfer only reviewed dependency files; no vendor patching/upgrade. Recreate guest environment configuration explicitly. Use a supported Windows guest to preserve case-insensitive deployment semantics; a Linux port is not silently included in this task. Configuration, native dependencies and filesystem portability must be validated before declaring the guest functional.

## 7. Database Isolation

Proposed DB **`kobi_mobile_acceptance_60c`** exists only in the guest MariaDB instance. Guest DB listens on `127.0.0.1:3306`, no remote bind. Guest must not connect to `192.168.1.5:3306`; outbound firewall denies protected host services. Normal server/data directory is neither mounted nor accessible. A different schema name on the normal server would not satisfy this design.

Install from the unchanged authoritative `database/structure.sql` in an empty, asserted guest target. It contains DROP statements, FK/unique-check controls, triggers/DELIMITER and SQL-mode directives. There is no turnkey seed/installer found by the focused filename scan, and no INSERT seed statements were found in structure.sql. A clean schema is not a populated product dataset.

Provisioning plan must explicitly exclude from a reviewed import stream these three mode directives, retaining original source bytes:

```sql
/*!40101 SET SQL_MODE=''*/;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
```

Require the guest's existing effective mode to match the acceptance baseline `NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION` **without changing it**. If guest defaults differ, stop and report the conflict before importing; choosing/configuring a matching new runtime needs explicit setup approval. No general session/global mode workaround is authorized by this design. Preserve and account for all other schema statements/constraints/triggers; use a delimiter-aware importer, assert empty target, restore/verify FK/unique checks and inspect actual metadata. Do not replay additive migrations already represented in structure.sql. STOP on import/metadata failure rather than relaxing a constraint.

Complete-schema import has not been executed/certified in this task. Operator creates fixtures only after import and isolation gates pass. Existing CLI fixtures can inform data shape but cannot be run unmodified in the guest: they assert the normal `medical1` source and a different test DB name.

## 8. Database Credential Strategy

Proposed guest connection: `DB_HOST=127.0.0.1`, `DB_PORT=3306`, `DB_DATABASE=kobi_mobile_acceptance_60c`, **`DB_USERNAME=kobi_mobile_runtime`**. This is a new proposed account name, not a disclosed credential. Host-scoped local password authentication supported by the chosen MariaDB build; validate connection host/grant matching rather than relying on a `%` grant.

Generate a unique strong random password locally during separately authorized setup. Store it only in the guest's ACL-restricted server `.env`, never in commands/history/reports/Flutter/client defines. No real password is supplied here. Use separate operator/bootstrap credentials outside the Apache runtime; runtime never receives schema-owner/root credentials.

Planned runtime grants: SELECT on the synthetic acceptance schema; INSERT/UPDATE on `user_api_sessions`, `user_api_refresh_tokens` and `health_medicine_schedule_checkoffs` only. These match inspected authentication issuance/rotation/revocation and checkoff upsert paths. No runtime DELETE/CREATE/ALTER/DROP/TRIGGER/GRANT OPTION/FILE/global privileges; retention/schema/fixture teardown belongs to the operator. Login password verification does not require updating users in the inspected API path.

Prove required SELECT FOR UPDATE/transactions/upsert work under these grants and that unrelated DML/DDL and protected database access are denied. If actual version semantics require another privilege, stop and document the narrow requirement; never grant ALL as a shortcut. Final operator privileges are scoped to creating/dropping this guest database/account/schema, not any host database. Guest OS DB administration is separate from runtime.

## 9. Synthetic Data Strategy

Use unmistakably synthetic users/names/records, not a normal dump or anonymized health export. Preflight seeds provide account eligibility (`active`, verified email, local password hash/password_set_at), active memberships and only required reference rows. No owner-governance bootstrap, grants, Medicine pilot/public activation or Treatment publication changes. Keep HOLD/OFF defaults.

Define fixture IDs/relations in a guest-only manifest; use a distinct range (e.g. users 100001/100002), not normal Account 1. Hash locally supplied synthetic passwords using existing PHP behavior; no raw credentials in seed files. Use a minimal valid saved-check snapshot only for history acceptance and synthetic public disease/substance reference data for knowledge navigation. The checked-in disease-reference PHP resource is required by the current service; copy it as source, but do not assume its real-disease mappings apply to invented seed rows. Prepare explicit compatible public fixtures or separately approved public reference data, never production personal data.

## 10. Apache Design

Inside VM: independent Apache ServerRoot **`C:/KobiAcceptance/runtime/apache`**, PHP configuration **`C:/KobiAcceptance/runtime/php/php.ini`**, document root **`C:/KobiAcceptance/app/public`**, own PID/temp/error/access files. Use the existing supported PHP 8.2+ application contract; isolate binaries/config rather than point modules at host `D:/xampp`. Verify chosen guest MariaDB version/schema behavior against the 10.4.27 baseline; any version difference is recorded and validated, not concealed as exact equivalence.

One guest vhost: `ServerName kobi-mobile-acceptance.<owner-controlled-zone>`, HTTPS 443 on its approved guest IP. Deny other Host/SNI names/default-site access; no insecure HTTP acceptance listener required. Enable existing rewrite/SSL and required PHP integration; permit the copied `.htaccess` directives needed for routing/options. Public API `/api/v1` maps to the unmodified front controller; root mount means no `/kobi/public` prefix.

Do not edit `.htaccess`. Verify the copied explicit Authorization rule for `/me`, logout/private reads and checkoff using synthetic authentication through real Apache. No PHP test router, fake HTTPS server variable or loopback TLS proxy substitutes for this gate. If forwarding fails, stop rather than change auth logic/configuration ad hoc. Web maintenance routes are not required for the mobile test; restrict guest HTTP access to the API/static surface needed for acceptance and operator fixture provisioning offline.

## 11. HTTPS/Certificate Design

Selected trust approach: **new certificate from a normally trusted CA for an owner-controlled test FQDN**, with full intermediate chain installed only in guest Apache. Obtain via DNS-01 so no protected service/port must be exposed publicly. DNS-01 can validate a name whose server is not publicly reachable; keep scoped DNS issuance credentials outside the web runtime. See [Let's Encrypt challenge types](https://letsencrypt.org/docs/challenge-types/).

Required SAN: exact `DNS:kobi-mobile-acceptance.<owner-controlled-zone>` (replace once with the actual owned suffix before setup). Use that same FQDN from emulator/phone/host; no IP or localhost substitution. Certificate validity and device clock must be correct. Private key stays guest-side in a restricted TLS directory, not app/public/archive/Flutter. No reuse of normal CA private key/localhost key.

Existing local leaf SAN is only DNS:localhost, issued by KOBI Local Development CA 2026; existing OpenSSL can inspect certificates, but Android trust of that CA has not been established. A new private-CA leaf plus user-store import is **not the selected approach**: it would need a separately proven runtime trust strategy and changes beyond the unchanged client boundary. No arbitrary self-signed acceptance, rooting/system-store modification, custom CA callback or network-security override is part of this plan. [Dart IO default context](https://api.dart.dev/dart-io/SecurityContext/defaultContext.html) uses Android platform roots; actual app handshake is still a gate, not inferred from Windows/browser trust.

No owned domain/DNS issuance authority or suitable certificate has been supplied. This is a **design blocker**, not something to fill with an invented hostname or `.test`/`.invalid` certificate. Existing production TLS remains unchanged.

## 12. Hostname Design

Proposed label: **`kobi-mobile-acceptance`**, below an owner-controlled registered zone; actual FQDN is unresolved. Proposed API URL once resolved: **`https://kobi-mobile-acceptance.<owner-controlled-zone>/api/v1`**. It is a template, not a usable URL.

Private/split DNS maps the FQDN to a reserved **guest** LAN IP selected outside the DHCP pool or through owner-approved reservation. Do not claim an unallocated `192.168.1.x` address is free; measure/select during setup. Do not map the name to host `192.168.1.5`, which serves protected XAMPP. Public challenge TXT records can be used for issuance without publishing a publicly reachable API.

Physical device: approved LAN DNS resolves the guest directly. Emulator: use the same DNS/FQDN and route to guest, not an emulator hosts hack or literal IP. Its special `10.0.2.2` is host loopback, not the guest or a physical-phone endpoint; no host forwarding is planned. Windows hosts entries alone do not configure Android. Validate DNS and TCP/TLS from the actual target; browser resolution alone is insufficient.

## 13. Network Design

Guest has one separately authorized bridged/test-LAN adapter with a reserved guest IP; no host NAT port-forward to normal XAMPP. Guest inbound defaults deny: allow HTTPS 443 from approved test device/host origin only and administration from the operator only when needed. DB remains guest-loopback-only. Restrict guest outbound to approved DNS/time/package/certificate requirements during setup; deny access to protected host HTTP/HTTPS/DB, host shares and other LAN services. After provisioning, remove unnecessary egress.

Host existing broad Apache/mysqld rules are not copied or changed. Guest firewall/hypervisor network policy must be proven to enforce separation despite those host allowances. For emulator traffic, actual source may appear as the Windows host's LAN address; validate and scope that address rather than assume the emulator's private address is visible to the guest. No router WAN forwarding, public tunnel, broad inbound rule or disabled Firewall. Access-point isolation/bridged Wi-Fi support is an unresolved preflight item; stop if that chosen path fails rather than exposing production or silently switching architecture.

## 14. Android Target Design

**First choice: host-side emulator**, because API 34 Google APIs x86_64 image and emulator are already installed and no physical phone is available. Proposed new AVD name `KobiAcceptance60C_API34`, distinct disposable directory under the approved host acceptance folder; 2 GiB RAM, 2 vCPUs, 4 GiB user data with at least 8 GiB disk headroom. This is an initial budget, not a successful boot result. Phone/tablet widget sizes remain supplemental to native testing.

Acceleration status 6 must be resolved by the owner/approved setup operator. Do not disable Hyper-V/VBS, edit BIOS or install conflicting drivers automatically. Confirm compatibility with the selected server VM platform and enough memory/storage for both. The server VM and emulator run side-by-side, not nested.

Fallback is an owner-supplied supported physical Android (API 24+) with owner-approved normal USB debugging authorization/install, same test LAN DNS and standard trusted chain. No root/user CA installation or security relaxation. A physical device would avoid emulator resource/acceleration work, but none is currently supplied and it does not remove the server/HTTPS prerequisites.

## 15. Flutter Endpoint Configuration

Existing `Environment.fromBuild` consumes compile-time defines; no Flutter file/flavor/source surgery is necessary. A future command, **not run**:

```powershell
& 'mobile/kobi_mobile/tool/flutter.ps1' build apk --debug --no-pub --dart-define=KOBI_ENV=staging --dart-define=API_BASE_URL=https://kobi-mobile-acceptance.<owner-controlled-zone>/api/v1
```

Replace the bracketed suffix with the approved actual FQDN before executing; review artifact defines/hash and install only on the selected test target. Do not use this literal template or the previous reserved `.invalid` URL as a live endpoint. The APK contains only public URL/environment label, never DB credentials, limiter key, TLS private key or DNS credentials. Normal platform trust/cleartext prohibition stays unchanged. Same app ID means an install may replace existing KOBI on a personal device; use the disposable emulator or an explicitly approved test device, not an unapproved personal install.

## 16. Environment Configuration

| Classification | Proposed value / location |
| --- | --- |
| Public client | KOBI_ENV=staging; actual approved HTTPS `/api/v1` URL |
| Guest server nonsecret | APP_NAME=`KOBI MOBILE ACCEPTANCE — SYNTHETIC ONLY`; APP_URL=`https://<actual-test-FQDN>`; APP_BASE_PATH empty; APP_TIMEZONE=Asia/Kolkata; APP_DEBUG=false |
| Guest DB nonsecret | DB_CONNECTION=mysql; DB_HOST=127.0.0.1; DB_PORT=3306; DB_DATABASE=kobi_mobile_acceptance_60c; DB_USERNAME=kobi_mobile_runtime |
| Server-only secret | Newly generated DB_PASSWORD and API_AUTH_RATE_KEY in restricted guest `.env`; no values in the plan |
| Guest storage | APP_STORAGE_PATH=`C:\KobiAcceptance\app\storage`; new isolated cache/log/session/temp paths; independently configure PHP session/temp/error paths |
| Server-only TLS | Guest TLS private key/full chain; no normal certificate/key copy |
| Operator-only | Separate DB bootstrap credential and DNS-01 credential/material; unavailable to Apache/client |

Use actual supported config keys; do not invent APP_ENV behavior that the loader does not enforce. Flutter staging banner and server name distinguish environment, but neither replaces DB/OS isolation. Do not copy normal SMTP/OAuth/external-service credentials. No external emails are needed when the approved synthetic account is preverified by the fixture operator. Maintain existing governance HOLD/OFF defaults; do not run bootstrap/deployment tools.

## 17. Production Safety Controls

Required stop-before-write assertions: VM identity, independent guest DB/data directory, exact acceptance schema, restricted runtime grants, guest-specific configuration/storage, no shared production paths/secrets, blocked guest→protected-host services, correct FQDN/SAN/runtime trust, source hash manifest and synthetic fixture manifest. A missing env key must fail validation; never let a copied normal `.env` provide a fallback.

Create no database/user on the host server. Test least-privilege denials only within the isolated guest; prove protected-host network denial without issuing health queries there. Do not prove isolation by intentionally writing to normal KOBI. Review process command lines so no secret is passed as an argument. Use display_errors off and exception argument redaction in isolated PHP, and restricted logs without request bodies/Authorization. No app/logger code edits; external log policy must not claim raw exception traces can never contain values without checking the guest's observed error paths.

## 18. Cleanup/Destruction Plan

Maintain an ownership manifest with VM UUID, resolved absolute host directory, guest IP, FQDN/DNS record identity, certificate fingerprint, DB/account names, AVD directory and APK hash. On teardown, preserve a redacted result only; use normal synthetic API logout/revocation if possible, stop clients, then shut down only this VM/AVD.

Inside guest, remove test DB/user/fixtures and disposable app/storage/config/TLS, or destroy the guest disk as a unit; destroying the disk also removes its Apache vhost and DB accounts without touching normal servers. Remove the exact hypervisor VM/virtual disk after verifying UUID/path lies within the approved acceptance directory. Never recursively delete a computed/unverified path. Remove only separately created DNS mapping/challenge residue, scoped network rules and test certificate/account resources; do not remove existing CA/host firewall rules. Revoke/dispose only the test certificate if required by its issuance procedure; securely discard private material.

Remove only the new AVD/test app state and identified test APK/config artifacts. Owner handles any physical-device uninstall. Verify VM/AVD absent, no test listeners, DB port never exposed, mappings removed and protected services/config hashes unchanged. Do not copy a production DB to check cleanup. Snapshots contain synthetic credentials and must be destroyed with the VM. No destruction is executed now.

## 19. Backup/Recovery Considerations

No production data backup is required for this design because it requires **zero production changes**. Do not dump `medical1` merely to provision a test environment. Record read-only normal config/source fingerprints for an operator change boundary; they are not a health-data backup.

Future setup may snapshot the clean disposable VM before schema import and before fixtures, with bounded storage/retention. Recovery is reset/destroy/recreate the exact isolated guest, not restore production or transplant test DB into normal KOBI. Record any host hypervisor/network/DNS additions and their separate rollback before authorizing them. If a proposed setup unexpectedly requires changing protected XAMPP/TLS/data, stop and redesign rather than create a production backup as implicit permission to modify it.

## 20. Step 60C Setup Sequence

Future setup only, conditional on explicit Step 60B-2 authorization:

1. Resolve domain/trust and VM/guest licensing/platform/storage/acceleration gates; approve exact directory, test LAN, guest IP/DNS and teardown manifest. Do not begin ambiguous setup.
2. Establish the approved hypervisor/Windows guest with resource limits, no host data shares and restrictive guest networking. Validate guest/host separation before placing credentials.
3. Install independently configured Apache/PHP/MariaDB in guest; verify versions/extensions, local DB bind, unchanged required SQL mode and no protected-host connectivity. Record any version differences and compatibility checks.
4. Transfer allowlisted byte-identical app/resources/dependencies; recreate isolated storage, server config and secrets; no normal `.env`/data.
5. Operator creates only guest acceptance DB and restricted accounts; preflight import stream; import complete schema, verify tables/FKs/CHECKs/indexes/triggers/mode and restricted runtime grants. Stop on failure.
6. Issue test-only certificate through approved DNS-01 procedure, install guest vhost/full chain, map private DNS to guest and validate normal host/native trust. No production listeners/trust stores changed.
7. Verify host public/API route and rejected missing-bearer paths, safe errors, no unexpected cookies, exact guest target and forwarding. Do not create a real login until approved synthetic fixtures exist.
8. Operator creates section 21 synthetic fixtures via a guarded guest-only fixture procedure; verify initial state/relations. Disable operator credentials from runtime.
9. Create/boot only the approved new AVD, after acceleration checks; verify DNS/TLS to guest before entering test credentials.
10. Build staging APK with exact FQDN/hash, install only on approved target, genuine synthetic login and server confirmation of principal/target.
11. Begin separately authorized Step 60C: restoration/refresh, full navigation, state writes/corrections, isolation/failure/timezone/persistence/native privacy/accessibility tests.
12. Capture safe results and run manifest-guarded teardown, confirming protected resources unchanged. Do not declare acceptance from host cURL/mock tests alone.

No step above was executed. If certificates/native trust/routing fails, stop; no HTTP/certificate override or alternate production endpoint.

## 21. Step 60C Test Data Plan

Proposed IDs below apply **only to the new empty guest database**; abort rather than overwrite if collisions exist. Let D be the actual setup/test UTC date; compute each slot's local date using the server and its stored zone. Do not freeze fixtures at today's document date or change system time.

| Fixture | Exact intended relation / initial state |
| --- | --- |
| Users 100001/100002 | `mobile-acceptance-a@example.invalid` / `mobile-acceptance-b@example.invalid`; active, verified, local password hash/password_set_at; generated credentials held securely |
| Profile 100101 | “SYNTHETIC MOBILE PROFILE A”; owner user 100001; active; no real DOB/contact |
| Profile 100102 | “SYNTHETIC MOBILE PROFILE B”; owner user 100002; inaccessible to A |
| Profile 100103 | “SYNTHETIC VIEWER PROFILE”; B owner/A viewer for denied-write/capability checks |
| Profile 100104 | “SYNTHETIC SWITCH PROFILE”; A owner, for accessible A→B selection race without foreign-profile confusion |
| Medicine 100201 | Profile 100101; brand `TEST MEDICATION A — NOT FOR USE`; current; started no later than each eligible slot-local day; no actual dosage advice |
| Slots 100301/100302 | Medicine 100201; UTC times 08:00/23:59; starts D−1, ends D+2; active; **zero occurrence rows** initially |
| Slot 100303 | Separate boundary synthetic medicine in profile 100101, America/New_York, dates spanning actual local today; displayed date from server, not phone |
| Slots 100304–100306 | Synthetic inactive, future-start and expired cases; known zone; exact effective dates based on local test day |
| Foreign/viewer/switch medicines/slots | Separate children in profiles 100102/100103/100104; manifest-bound IDs, no shared-profile shortcut |

Use 100301 unset → Taken → Skipped → Taken; verify one slot/day row and retained creation identity. Use 100302 unset → Skipped independently. Repeat identical state through the approved isolated API driver if UI hides the already-current action; no product edits or synthetic taps on absent controls. Double-tap/pending switch use approved target behavior; inspect persistence only in guest.

Timezone boundary: schedule a real test near a known UTC/local-day difference if practical, supplement with existing deterministic isolated tests; do not claim an actual crossed boundary from mocked clocks. For broader V1, use a minimal valid historical snapshot and approved public fixtures; preserve original disclaimer/contract. Teardown removes all fixtures with guest DB destruction, not individual normal records.

## 22. Risk Register

| Risk | Likelihood before controls | Impact | Mitigation / stop condition |
| --- | --- | --- | --- |
| Accidental production DB connection | Medium if normal config copied | Critical | No copied env/mount; guest-only DB; block protected-host service access; assert target before writes |
| Privileged credentials reused | High if normal account reused | Critical | New restricted guest runtime/operator accounts, no host grants |
| Certificate trust failure | High with current localhost leaf | High | Owned FQDN/public chain/native handshake gate; no override |
| Android hostname resolution failure | Medium | High | Private DNS visible to target; preserve exact SAN name; test native request |
| Firewall exposure | High if shared host allowances reused | High | Separate guest default-deny, approved origins only, local DB, no WAN forward |
| Apache vhost collision | High with existing server edits | High | Separate VM/IP/443/root/PID/config; no host service edit |
| Stale Flutter endpoint | Medium | High | Review staging defines/artifact/hash before credentials; `.invalid` is compile-only |
| Secrets in APK/archive/logs | Medium | High | Explicit copy allowlist, client public defines only, server-only secrets, safe evidence |
| Synthetic/test data leakage | Medium | Medium | No real data, private access, no payload logging/shares; bounded retention |
| Cleanup wrong target/failure | Medium | High | VM UUID/path manifest, guard resolved target, no wildcard deletion, verify teardown |
| VM/AVD acceleration conflict | Unresolved; status 6 | High | Approved compatible platform, boot proof; no security/BIOS workaround |
| Disk/RAM exhaustion | Material with current free resources | High | Provision space, bounded snapshots and memory; preflight both VM and AVD budgets |
| Guest schema/version mismatch | Unverified complete import | High | Exact metadata/constraint validation, preserve SQL mode, stop on errors |

## 23. Exact Prerequisites

**Design blockers to resolve before unrestricted Step 60B-2 setup:**

1. Owner-approved usable Windows-guest hypervisor, compatible with current host hypervisor/security, licensed guest image and admin/setup procedure. Installed `vmcompute.exe` does not establish this; no VM tool was proven usable.
2. Approved host storage location/capacity and memory budget; current free disk is below the proposed safe 80 GiB provisioning budget. Do not silently lower safety margins or fill a host disk.
3. Actual owner-controlled DNS zone/test FQDN, authority for DNS-01 issuance and a private DNS mechanism reachable by Android. No actual hostname/certificate currently fulfils this design.

**Required setup/acceptance gates:** approval of guest network/IP rules and blocked production path; compatible independent PHP/MariaDB versions with required mode/complete schema; restricted runtime/operator grant plan; synthetic seed/teardown manifests; owner-approved emulator acceleration/AVD (or supplied physical device); test APK input/install authorization. Domain/guest license/platform availability cannot be inferred from source files.

## 24. Final Readiness Decision

**READY WITH DESIGN BLOCKER**, not READY FOR SETUP without conditions. The design can proceed without another product-development phase, but the actual VM platform/storage and hostname/trust assets are missing/unconfirmed. Setup is not authorized by this report. Step 60B-2 can safely begin only with explicit authorization and those prerequisites resolved; otherwise limit the next step to resolving them, not improvising a weaker shared-XAMPP design.

This is a cautious environment decision, not an implementation rejection. Step 60C cannot start now. No production, TLS, DB, firewall, device or application change was performed.

## 25. Validation Performed

Fresh read-only checks: host OS/CPU/memory/disk/network/listeners/virtualization metadata; standard VM tool paths; current Public-profile Apache/mysqld firewall rules; Apache syntax/vhost; OpenSSL/leaf SAN; PHP version/loaded ini/extensions; normal MariaDB ini path/port/data/mode directives; AVD directories/system-image metadata/emulator acceleration; Flutter/Dart/ADB versions and Gradle wrapper; current client endpoint/native transport; DB service/repository write paths; source/schema/seed/reference-resource inventory.

Current normal DB layout/privilege/auth metadata and host-only HTTPS success are explicitly prior Step 60B evidence. No fresh DB connection/query, normal API call, credential family or test was needed in this design task. No AVD/device was started. Emulator `-accel-check` can emit ordinary tool temp/crash metadata; no device/configuration was created. Read-only commands received scoped tool access where CIM/firewall inspection required it.

Existing 60A/59C passing regression/build results remain historical; no full suite/build, provisioning test or complete-schema import was rerun. No source changed to address a finding. Public documentation was consulted only for trust/issuance/virtualization semantics; machine-specific readiness comes from the inspected evidence, not those documents.

## 26. Files Changed

Created only `docs/task-60b1-mobile-acceptance-environment-design.md`. SHA-256 baseline: **689 existing nonignored project files**; final comparison found no existing-file byte changes/removals and only this report added. Inventory excludes `.git`, vendor, storage and ignored/generated tool caches. No environment files/directories/archives/setup scripts were created.

## 27. Confirmation of No Implementation

No app/Flutter/API/native source/configuration, Apache/XAMPP/PHP/MariaDB configuration, vhost, VM, user, directory, data fixture, copy, DNS/hosts, AVD, APK install or infrastructure implementation occurred. Proposed paths/commands/settings are report text only. Step 60B-2/60C was not begun.

## 28. Confirmation of No Database Mutation

No SQL executed in this task; no DB/user/grants/schema/data/mode change. No production dump/backup/query/write. `database/structure.sql` unchanged. No normal health or authentication record created/read/altered for the design.

## 29. Confirmation of No TLS/Firewall Changes

No certificate/key/trust store/TLS/firewall/network-profile/router/Apache change. No bypass, cleartext fallback, production certificate reuse, Android security change or exposed port was introduced. Private keys/passwords/tokens were not read or printed.

## 30. Confirmation of No Git Operation

No Git operation, staging, commit, push, reset, checkout, merge, rebase or history mutation. File changes were checked using path/SHA-256 inventory only.

**STOP after Step 60B-1. No setup or Step 60C execution.**
