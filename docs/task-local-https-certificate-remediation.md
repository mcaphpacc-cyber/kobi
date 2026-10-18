# Local HTTPS certificate remediation

## 1. Initial diagnosis

Assessment date: 16 October 2026 (Asia/Kolkata). Target: `https://localhost/kobi/public/login`, the HTTPS form of the existing normal local KOBI hostname. Configured application URL remains `http://localhost`, base path `/kobi/public`; no application configuration was changed.

Current outcome: **BLOCKED — interactive Windows Root installation is now confirmed by the user's successful exact-thumbprint `certutil` output, but automation and the in-app browser still cannot validate that trust.** Original Apache/PHP files are restored and Apache has now been restarted with those originals. Successful cutover checks below describe the tested new configuration before rollback, not completed final activation. See section 18 for the latest reconciliation.

Initial PHP/cURL verification failed with error 60/self-signed certificate. Diagnosis was completed before certificate/configuration changes. This task addresses local TLS only; Step 50B deployment/activation is not part of it.

## 2. Original certificate details

- Certificate: `D:\xampp\apache\conf\ssl.crt\server.crt`.
- Key: `D:\xampp\apache\conf\ssl.key\server.key` (contents never output or copied into KOBI).
- Subject and issuer: CN `localhost`; self-signed.
- Validity: `2009-11-10T23:48:47Z` to `2019-11-08T23:48:47Z`.
- SAN: absent.
- SHA-256 fingerprint: `016973380c0f1df00bd9593ed8d5efa3706cd6df7993f6141272b80522acdd23`.
- Original certificate/key pair: matching.
- Original private-key ACL inherited broad local Users read / Authenticated Users modify rights. The retained rollback key was subsequently restricted under elevation.

## 3. Exact cause of failure

The leaf was expired, self-signed and lacked a modern localhost SAN. The original key was not mismatched. PHP uses an OpenSSL CA bundle separate from Windows/browser trust; no KOBI local CA was trusted initially. Apache's SSL ServerName was the sample `www.example.com:443`, causing existing hostname warnings, while actual KOBI uses localhost. No application TLS-verification disable was found in the inspected application paths.

## 4. Existing Apache SSL configuration

Apache 2.4.54 / native OpenSSL 1.1.1p, using `D:\xampp\apache\conf\httpd.conf` with `conf/extra/httpd-ssl.conf`. DocumentRoot: `D:/xampp/htdocs`; SSL listens on 443, normal HTTP on 80. Original certificate/key references were `conf/ssl.crt/server.crt` and `conf/ssl.key/server.key`.

This XAMPP Apache was a console process, not a registered `Apache2.4` Windows service. Native `-k restart` reported AH00436/no installed service. The final restart used an elevated helper checking the PID file, executable path and parent/child identity, stopping only that XAMPP Apache process tree and starting the same binary/configuration hidden. No new web server/service was installed.

## 5. Existing PHP/cURL trust configuration

Loaded CLI ini: `D:\xampp\php\php.ini`. Apache's `PHPINIDir` points to `D:/xampp/php`; the module uses PHP 8.2.0. PHP/cURL uses OpenSSL 3.0.7. Original `curl.cainfo` and `openssl.cafile` both referenced `D:\xampp\apache\bin\curl-ca-bundle.crt` (215,352 bytes). No OpenSSL capath override was set.

PHP's [curl.cainfo setting](https://www.php.net/manual/en/curl.configuration.php) and [openssl.cafile setting](https://www.php.net/manual/en/openssl.configuration.php) select file-based CA trust independently of Windows. The remedy preserves the original bundle and adds the local CA to a separate combined bundle.

## 6. Remediation performed

Created the dedicated directory `D:\xampp\apache\conf\kobi-local-tls`, outside all inspected DocumentRoot/Alias mappings. Restricted its ACL to the current user, SYSTEM and Administrators; keys inherit only those rights. Backed up the relevant configuration before cutover.

Generated a dedicated local-development CA and localhost leaf using existing OpenSSL tools, with no package installation. Both use RSA 3072/SHA-256. A staging CSR initially included an issuer-only authority-key extension; OpenSSL rejected it before cutover. The request was corrected to contain request-appropriate SAN extensions; final issuer extensions are applied when signing. The certificate architecture did not change.

Initial non-elevated configuration attempts were rolled back when Windows denied protected operations. The elevated helper completed the Apache/PHP cutover and retained-key ACL protection. Interactive Root installation is confirmed, but the automation trust view still disagrees; see sections 8 and 18. No TLS validation was disabled.

## 7. Certificate architecture after remediation

| Item | Local CA | Localhost leaf |
|---|---|---|
| Public certificate | `kobi-local-tls/ca.pem` and DER `ca.cer` | `kobi-local-tls/localhost.pem` |
| Private key | `kobi-local-tls/ca.key` | `kobi-local-tls/localhost.key` |
| Subject CN | KOBI Local Development CA 2026 | localhost |
| Issuer CN | KOBI Local Development CA 2026 | KOBI Local Development CA 2026 |
| Validity (UTC) | 2026-10-15 19:12:53 → 2031-10-14 19:12:53 | 2026-10-15 19:13:43 → 2027-10-15 19:13:43 |
| Basic constraints | CA:true, pathlen:0 | CA:false |
| Purpose | Local TLS server certificate signing only | TLS server authentication |
| Name restriction/SAN | Critical permitted DNS subtree `localhost` | SAN `DNS:localhost` only |

CA SHA-256: `df4e428dae4ce38ed85541a1285404c65eeb4159887d63dbb378a8a3b05a62aa`.

Leaf SHA-256: `83f096f60d695d66513b57da98b4ba6cc41763b65b045afcd01f3db76cc18087`.

CA Windows thumbprint: `05A0E8F4847592CFC6A093E7885A965E4795E75D`.

Both certificate/key pairs passed checks. OpenSSL's chain/purpose/hostname verification passed. No unnecessary public domain or IP SAN was added. This CA/certificate is strictly local development infrastructure, not a public Internet certificate. Extension semantics follow the [OpenSSL X.509 extension documentation](https://docs.openssl.org/3.0/man5/x509v3_config/).

## 8. Trust-store configuration

During cutover PHP used `D:\xampp\apache\conf\kobi-local-tls\php-ca-bundle.pem` for both `curl.cainfo` and `openssl.cafile`. The combined bundle begins with the exact original bundle contents and adds exactly one CA. The original bundle file was not modified. Positive requests use these effective ini defaults, not a request-specific trust bypass.

Intended Windows scope: Current User → Trusted Root Certification Authorities, adding only the public local CA, not the leaf/private keys. Programmatic operations did not produce a retained active-user trust entry: `Import-Certificate` returned `0x80092004`, and after the elevated certificate-store API operation the normal Windows chain builder still reported PartialChain and the active Root store contained no matching CA. Elevated diagnostics/API success alone was therefore not treated as trust success.

The user confirmed installation and supplied a successful interactive Root-store query for the exact thumbprint; automation still sees no matching Root entry even after restarting Codex. No certificate-warning bypass was attempted. Windows/browser trust is not yet PASS. Machine-wide trust has not been intentionally altered. See [Microsoft's certificate-import documentation](https://learn.microsoft.com/en-us/powershell/module/pki/import-certificate?view=windowsserver2025-ps) for the selected user-store scope.

## 9. Apache verification

The following results were obtained during the tested new-certificate cutover, before restoring the original configuration files for rollback. The latest rollback restart is now verified; Apache is running with the restored original configuration (see section 18).

- Final `httpd -t`: **Syntax OK**.
- Elevated restart result: `APPLIED_AND_RESTARTED`, parent PID 18092, recorded at `2026-10-15T19:22:53.8951750Z`.
- Actual HTTPS response: HTTP 200, effective URL stayed `https://localhost/kobi/public/login`, expected login-page content present.
- cURL certificate inspection of the live peer matched the new leaf fingerprint, issuer, current validity and `DNS:localhost` SAN.
- During the new-certificate cutover the old expired certificate was not served. The original certificate configuration was subsequently restored as required by rollback.
- Latest successful-start log entries showed normal operations/worker startup, with no new SSL hostname/configuration warnings. Earlier abrupt-parent-exit notices correspond to the controlled restarts; prior sample-hostname warnings belong to the old configuration.

No redirect/fallback to HTTP was used to validate HTTPS. Existing HTTP listener/application URL were deliberately preserved. The [Apache SSL how-to](https://httpd.apache.org/docs/2.4/ssl/ssl_howto.html) describes the certificate/key directive arrangement used here.

## 10. PHP/cURL verification

Actual request to normal `https://localhost/kobi/public/login` with `CURLOPT_SSL_VERIFYPEER=true`, `CURLOPT_SSL_VERIFYHOST=2`, no redirects and no per-request CAINFO override:

These are the verified cutover results. PHP's original CA settings have since been restored; final PHP trust is therefore **BLOCKED**, rather than represented as a current completed remediation PASS.

- HTTP **200**.
- `CURLINFO_SSL_VERIFYRESULT`: **0**.
- cURL errno: **0**, error string empty.
- Expected Login page present.
- Loaded ini and both effective CA settings matched the intended files.

Strict negative controls also passed: `https://127.0.0.1/...` was rejected because the leaf has no IP SAN; using the old bundle rejected the untrusted issuer. Both returned error 60 rather than succeeding insecurely. Thus the original error 60 is resolved for the correct endpoint/configured trust, while invalid trust/hostname still fails.

## 11. Browser verification

**Pending / not PASS.** The running in-app browser rejected the HTTPS navigation with `net::ERR_CERT_AUTHORITY_INVALID`. No warning was bypassed or security flag changed. Windows default `Invoke-WebRequest` independently reported PartialChain, confirming that the pending issue is real OS trust, not merely an assumed browser-cache problem.

Desktop/mobile HTTPS sanity checks remain deferred because browser trust still fails after the confirmed interactive CA installation. No login, password, API credential or deployment smoke test is part of this verification.

## 12. Security checks

New CA/leaf private keys remain outside `D:\xampp\htdocs` and KOBI, in the ACL-restricted Apache configuration directory. Effective key ACLs contain only current-user/SYSTEM/Administrators FullControl; inherited key-file permissions come from the protected TLS directory. The original retained rollback key now has explicit restricted ACLs for the same principals, with its original security descriptor recorded for rollback.

No private key, password, token or API secret was output or placed in the report/repository. No TLS peer/hostname check was disabled, no permissive SSL callback added, no insecure fallback introduced, no application source altered and no unrelated package installed. Root trust is scoped to a constrained development CA, not arbitrary certificates. No private-key endpoint or Alias was added.

## 13. Files/configuration changed

Outside KOBI:

- `D:\xampp\apache\conf\extra\httpd-ssl.conf`: ServerName → `localhost:443`; certificate/key → `kobi-local-tls/localhost.pem` and `.key`.
- `D:\xampp\php\php.ini`: only `curl.cainfo` and `openssl.cafile` → the new combined bundle.
- `D:\xampp\apache\conf\ssl.key\server.key`: permissions restricted; original contents/path retained.
- `D:\xampp\apache\conf\kobi-local-tls\`: local CA/leaf material, OpenSSL configurations, combined PHP bundle, protected rollback backups/state, scoped cutover/rollback and diagnostic helpers/results. No generated TLS material is under KOBI.
- Windows trust installation: interactive Root presence confirmed by the user; automation/browser acceptance remains blocked after Codex restart.

Inside KOBI: only this requested report, `docs/task-local-https-certificate-remediation.md`.

Rollback checkpoint: original `httpd-ssl.conf` and `php.ini` have been copied back and their SHA-256 hashes match the backups. Staged certificates, bundle and protected backups remain outside KOBI. Windows reported “The operation was canceled by the user” when launching `elevated-rollback.ps1`; that helper did not run. That earlier scoped restart did not complete at the time. The latest resume has now completed the configuration rollback and exact-process Apache restart (section 18); the retained-key ACL remains tighter than its original descriptor. The retained old key currently keeps the more restrictive permissions applied during cutover. No further elevation retry was attempted after cancellation.

## 14. Files/configuration deliberately not changed

KOBI `.env`, application configuration, routes/controllers/services/repositories, authentication/API implementation, views/assets, schema/migrations, limiter key/store and previous implementation/deployment reports. Original certificate/key contents and original CA bundle preserved. No machine-wide trust, hosts-file, DNS, cipher/protocol policy, HTTP listener or global application HTTPS redirect was intentionally changed.

## 15. Database verification

No direct database connection, SQL command, backup, migration or DB test was executed. No schema/data/user/health change was made; no tables/triggers created; no limiter key provisioned. Ordinary anonymous Login page HTTPS requests were the only KOBI checks; no authentication credentials were submitted. Existing application rendering may perform its usual internal initialization; it was not instrumented or changed.

Step 50B deployment and production authentication smoke tests have **not** been executed.

## 16. Git verification

No Git command/operation was performed. No commit/push/pull/reset/checkout/switch/merge/rebase/amend/index/history change. No key/certificate/CA material was placed in KOBI. Aggregate SHA-256 comparison of `app`, `bootstrap`, `routes`, `database`, `public`, `resources` and `.env` matched the pre-task baseline exactly.

## 17. Rollback information

Protected backup directory: `D:\xampp\apache\conf\kobi-local-tls\rollback-20261016`.

Contains original `httpd-ssl.conf`, `php.ini`, `curl-ca-bundle.crt`, original file hashes, trust-store thumbprint snapshots, original certificate/key paths and retained-key original ACL descriptor in `before.json`. No private key was copied into KOBI; the old key/certificate remain at their original non-web locations.

An authorized administrator can run `D:\xampp\apache\conf\kobi-local-tls\manage-cutover.ps1 -Mode Rollback` to restore original Apache/PHP files, remove only the new CA from that user's Root store and restore the old key ACL, followed by an XAMPP Apache restart. If interactive Windows trust was installed, removal must use that same user's store and be verified. This restores the old, known-expired HTTPS state; it does not provide working trusted TLS.

Staging failures during this task were rolled back before retrying the same certificate architecture with the required Windows permissions/API. No second certificate architecture was introduced. Backups and old files are retained; nothing is overwritten or deleted merely to clean up this task.

## 18. Final status

**BLOCKED — Windows/browser trust is still not established for the automation environment after a full Codex restart. Required configuration rollback and Apache restart are now complete.**

Latest verification on 16 October 2026 (Asia/Kolkata):

- The user's interactive `certutil -user -store Root 05A0E8F4847592CFC6A093E7885A965E4795E75D` successfully lists the intended public CA, serial `7ca42c17adfe4efb7c7b9f3e5ef8f59fe185adcf`. Its missing private-key provider is expected for a public CA certificate.
- After restarting Codex, automation still finds zero exact-thumbprint matches in CurrentUser/LocalMachine Root, including a check outside the sandbox. The mismatch between interactive and automation trust views remains unresolved. A stale-process explanation was tested by restarting Codex and did not resolve it; it is not an established root cause.
- The original SSL/PHP configuration hashes matched their backups before the latest cutover. Apache was stopped at that checkpoint. Only the previously prepared SSL hostname/certificate/key paths and two PHP CA settings were reapplied. No certificate-store operation or ACL change was made during this resume.
- Apache started with parent PID 16940; syntax passed. PHP/cURL returned HTTPS 200, verification result 0, errno 0, expected Login content, and live leaf SHA-256 `83f096f60d695d66513b57da98b4ba6cc41763b65b045afcd01f3db76cc18087`. Both verification options remained enabled; no request-specific CA override or HTTP fallback was used.
- Windows default HTTPS still failed to establish the SSL connection. A fresh in-app-browser tab independently failed with `net::ERR_CERT_AUTHORITY_INVALID`. No certificate warning was bypassed. Desktop/mobile page sanity checks remain deferred.
- Because full acceptance failed, the original `httpd-ssl.conf` and `php.ini` were restored and their hashes verified against the backups. Only the exact newly started XAMPP Apache process tree was stopped, after executable/PID checks; the original configuration was restarted hidden. Restored parent PID 6844, child PID 16764, syntax OK, normal worker startup logged at approximately 01:31 IST. The expected original sample-hostname warning returned. No Windows trust entry was modified or removed.
- PHP CLI now again reports the original `D:\xampp\apache\bin\curl-ca-bundle.crt` for both trust settings. The old, known-invalid certificate configuration is the rollback state, not a trusted HTTPS solution. Historical new-certificate/PHP successes must not be read as current final PASS.

Current acceptance:

| Check | Result |
|---|---|
| Apache starts / original configuration restored | PASS |
| Staged certificate validity, SAN, key pairing, OpenSSL chain | PASS |
| New configuration PHP/cURL verified request | PASS during cutover; rolled back |
| Windows trust in automation | BLOCKED |
| Browser trust / desktop and mobile HTTPS sanity | BLOCKED / deferred |
| Final cURL error 60 resolution | BLOCKED after required rollback |
| Verification enabled / no insecure bypass | PASS |
| Private keys outside web root/repository and restricted | PASS |
| Application/database/Git untouched | PASS within the task's stated verification scope |

Smallest safe next action: diagnose the discrepancy between the interactive user's Root store and the certificate store visible to automation (including comparing the interactive `hostname`, `whoami /user`, and exact-thumbprint store query). Do not repeat CA installation, regenerate certificates, weaken TLS checks, or reapply cutover until that discrepancy is understood. The user's earlier pasted store result did not include the requested hostname/user identity. No UAC prompt or rollback job is pending. The original key's tighter ACL remains retained; its original descriptor is preserved in the protected backup.

Only this report changed inside KOBI. No direct DB connection, SQL command, schema/data modification, authentication credential submission, application code change, limiter provisioning or Git command was performed. Step 50B deployment and activation have NOT been executed. Stop here.

## TLS Trust Identity Diagnosis

Date: 16 October 2026 (Asia/Kolkata). **DIAGNOSIS ONLY — BLOCKED: same Windows identity confirmed, differing observable Root entries confirmed; underlying store discrepancy and browser transport trust details remain unestablished.** This section records read-only investigation after rollback; no further cutover or remediation was performed.

### 1–3. Interactive hostname, identity and SID

The user supplied successful independent command output:

- Hostname: `LAPTOP-PTFFHOMV`.
- Account: `LAPTOP-PTFFHOMV\Sanjog`.
- SID: `S-1-5-21-2242542228-2552922399-2298678612-1001`.
- Token integrity: Medium (`S-1-16-8192`). Administrators and Local-account/member-of-Administrators groups are deny-only; the remaining listed groups match the Medium automation token's groups.
- Windows PowerShell executable: `C:\Windows\System32\WindowsPowerShell\v1.0\powershell.exe`.
- Windows PowerShell Desktop 5.1.26100.9711, BuildVersion 10.0.26100.9711, CLR 4.0.30319.42000, WSMan 3.0, remoting 2.3, serialization 1.1.0.1.
- CurrentUser Root contains exactly the queried CA identity; LocalMachine Root query produced no matching entry (repeated in the supplied output).
- DNS for localhost returns `::1` and `127.0.0.1`.

Result: interactive and observed automation hostname/user/SID are **SAME**. This rules out a different Windows account as the explanation for the shell's differing CA visibility. It does not establish the browser network worker's effective trust context.
### 4–6. Automation hostname, identity and SID

- Hostname: `LAPTOP-PTFFHOMV`.
- Account: `LAPTOP-PTFFHOMV\Sanjog`.
- SID: `S-1-5-21-2242542228-2552922399-2298678612-1001`.
- User profile: `C:\Users\Sanjog`.
- Default shell: PowerShell Core 7.6.5, Win32NT, reported OS Microsoft Windows 10.0.26300. Executable observed outside sandbox: `C:\Users\Sanjog\.cache\codex-runtimes\codex-primary-runtime\dependencies\native\powershell\pwsh.exe`.
- `where.exe powershell`: `C:\Windows\System32\WindowsPowerShell\v1.0\powershell.exe`. This resolves Windows PowerShell, not the running `pwsh.exe`; executable names are distinguished.
- Sandboxed token: Low integrity (`S-1-16-4096`). Outside-sandbox token: Medium integrity (`S-1-16-8192`). Both report the same user SID; Administrators and Local-account/member-of-Administrators groups are deny-only. The outside-sandbox request did not elevate Windows to administrator.
- Other reported token groups: Everyone; Performance Log Users; Users; INTERACTIVE; CONSOLE LOGON; Authenticated Users; This Organization; Local account; LOCAL; NTLM Authentication. No SYSTEM/service identity was observed for the inspected automation processes.
- Read-only CIM owner/SID queries succeeded for Codex PIDs 476, 15200, 20072, 16564, 1176, 16856 and 340, and Apache parent 6844/child 16764: all the same Sanjog SID. These PID values are observations, not permanent identities.
- Codex desktop parent PID 18736 is `ChatGPT.exe` in Windows package `OpenAI.Codex_26.1007.9446.0_x64__2p2nqsd0c76g0`, owned by Sanjog. `node_repl.exe` PID 8268 is also owned by Sanjog. Package-name API queries reported no package identity for the inspected shell, node_repl and Codex CLI process. This does not establish registry virtualization or browser trust isolation.
- Browser provider exposes `Codex In-app Browser`, ID 2, type `iab`. Its transport/verification worker PID and effective token are not exposed by the supported API. Unrelated Chrome/WebView process owners are not treated as proof of the in-app network worker's identity.

### 7–8. Certificate stores and CA identity

Exact CA thumbprint: `05A0E8F4847592CFC6A093E7885A965E4795E75D`.

Automation sees zero matches in CurrentUser Root and LocalMachine Root, both inside and outside the sandbox. Outside-sandbox `certutil -user -store Root <thumbprint>` returns `0x80090011 NTE_NOT_FOUND`. The Root registry paths exist under HKCU and the explicit automation SID's HKEY_USERS hive; this exact certificate entry is absent in that view. Absence of this one entry is not proof that the whole Windows Root store is inaccessible.

Automation sees the exact CA in both CurrentUser CA and LocalMachine CA (Intermediate Certification Authorities). Metadata matches the staged public CA and the user's previous interactive Root result: Subject/Issuer `O=KOBI Local Development Only, CN=KOBI Local Development CA 2026`; validity 16 October 2026 00:42:53 IST to 15 October 2031 00:42:53 IST (UTC 15 October 2026 19:12:53 to 14 October 2031 19:12:53). No private key was exported or inspected.

Interactive CurrentUser Root: PRESENT in both the prior certutil output and the new exact-thumbprint PowerShell output. Interactive LocalMachine Root: no matching CA. In the two interactive Root locations inspected, the CA is installed only in CurrentUser. Automation reports zero matching Root entries in both locations. Thus the CA identity matches, the user SID matches, and the observable CurrentUser Root result differs. Exact CA absence in automation is established; inability to access the entire interactive Root store is NOT established.

### 9–10. Browser and PHP/cURL certificate comparison

Historical successful cutover: PHP/cURL validated the leaf with SHA-256 `83f096f60d695d66513b57da98b4ba6cc41763b65b045afcd01f3db76cc18087`, subject localhost, issuer KOBI Local Development CA 2026, serial `11E8087A5EA2EEBF9384F8BE4D280E1C`, SAN DNS:localhost, valid 15 October 2026 19:13:43 UTC to 15 October 2027 19:13:43 UTC; HTTP 200, verification result 0. These are historical results, not the current rolled-back state.

Current unchanged endpoint: strict PHP/cURL returns error 60, verification result 18, self-signed certificate, HTTP 0, using the original CA bundle. OpenSSL s_client with `-verify_return_error` independently rejects a self-signed peer at depth 0 with CN localhost. Its rejected handshake does not expose a PEM peer certificate even with showcerts/prexit; no permissive handshake was used to obtain one. PHP certinfo is empty after rejection.

Restored Apache file references `conf/ssl.crt/server.crt`; that file has subject/issuer CN localhost, serial `B5C752C98781B503`, SHA-256 `016973380c0f1df00bd9593ed8d5efa3706cd6df7993f6141272b80522acdd23`, validity 10 November 2009 23:48:47 UTC to 8 November 2019 23:48:47 UTC, no extensions/SAN. Configuration plus rejected-handshake metadata is consistent with the original certificate, but the live fingerprint was not independently extracted on this diagnosis.

A new browser navigation to the exact unchanged HTTPS endpoint returns `net::ERR_CERT_AUTHORITY_INVALID`. The supported browser API exposes no peer certificate/chain, security-panel metadata, DNS resolver or remote socket address. Listed browser capabilities are visibility and viewport; neither provides TLS inspection. Browser leaf subject/issuer/serial/fingerprint/SAN/chain and browser-versus-PHP certificate equality remain UNKNOWN. The error alone cannot prove equality. No warning was bypassed and no alternate browser was substituted.

### 11. DNS and endpoint

Both automation shell DNS checks resolve localhost to `::1` and `127.0.0.1`. PHP/cURL's actual connection is IPv4 `127.0.0.1:443`, with URL/SNI hostname localhost. Browser target is exactly `https://localhost/kobi/public/login` (port 443), but its actual address/IPv4-versus-IPv6 choice is not exposed. Same requested hostname/port/path: YES. Same resolved socket endpoint: UNKNOWN. Interactive DNS output matches the same two localhost addresses. No hosts/DNS/firewall settings changed.

### 12. Browser trust mechanism

Not identifiable through the available supported browser interface. The desktop app is packaged, but the actual browser verifier's Windows token/store/backend is not exposed. There is no evidence sufficient to claim a bundled CA database, SYSTEM account, alternate user, isolated container or independent trust implementation. Low-integrity shell execution is observed, but the same missing Root entry at Medium integrity rules out treating Low integrity alone as the established cause.

### 13–14. Root cause and remaining uncertainty

Established finding: the SAME hostname, account, SID and Medium-integrity group context produce differing observable CurrentUser Root results between interactive execution and automation. The automation check was repeated outside the sandbox using the exact Windows PowerShell 5.1 executable and version/build reported by the user; it still found zero matches in either Root store. This rules out PowerShell 7 versus 5.1 as the sole explanation and rules out Low integrity alone. It does not identify registry redirection, brokering, profile loading, certificate-store policy or browser-verifier implementation as the cause. No evidence supports blaming a SYSTEM/service account. No exact underlying cause of the historical new-certificate browser rejection is established. Current rejection of the rolled-back invalid certificate is expected and cannot validate the new CA.

### 15. Smallest safe next action — recommendation only

Interactive comparison is complete. The smallest next diagnostic is a read-only comparison of the exact CA registry entry in the interactive HKCU/explicit-SID Root hive versus automation, together with a supported way to inspect the in-app verifier's actual peer certificate and effective trust source. The latter capability is not available in the current browser tool surface. These are recommendations only and have not been performed in the interactive session. No trust-store remediation is justified until the remaining store-view discrepancy is explained. Do not reinstall/move certificates, change stores, change browser settings, regenerate material or reapply TLS configuration based on an unproven explanation.

Safety: only this requested report was edited. Apache/PHP configuration hashes still match original rollback backups. No TLS configuration, certificate, trust store, browser setting, hosts or firewall changes; no verification disable; no database access/writes/schema/data changes; no KOBI application/configuration/authentication/API change; no Git command. Treatment HOLD and Medicine pilot OFF settings were not changed or activated; live governance state was not queried. Step 50B remains NOT EXECUTED. No new files created.

Final same-executable check: automated Windows PowerShell 5.1.26100.9711 (Desktop/CLR 4.0.30319.42000) reports the same Sanjog SID and zero Root matches outside the sandbox. Its reported OS version is 10.0.26300.0; the user's PowerShell BuildVersion is a PowerShell component build, so it is not treated as evidence of a different Windows host/OS. Original SSL/PHP hashes were rechecked and remain unchanged. No remediation was applied.

## TLS Trust Store and Browser Verifier Diagnosis

Date: 16 October 2026 (Asia/Kolkata). **DIAGNOSIS ONLY — BLOCKED**: exact registry-view divergence is confirmed; the browser verifier's effective trust source and the underlying cause are not established. This section supersedes the earlier recommendation to obtain interactive registry output, which has now been supplied. No remediation was applied.

### 1–4. Registry comparison and automation visibility

Known CA: `05A0E8F4847592CFC6A093E7885A965E4795E75D`.

Exact relative registry key: `Software\Microsoft\SystemCertificates\Root\Certificates\05A0E8F4847592CFC6A093E7885A965E4795E75D`.

| Context | Current User Root parent | Exact HKCU CA entry | Exact HKLM CA entry |
|---|---|---|---|
| Interactive user, supplied Test-Path output | Exists | Exists | Absent |
| Automation outside sandbox, Medium integrity | Readable, zero certificate subkeys | Absent | Absent; Root parent readable, 14 subkeys |
| Automation sandbox, Low integrity, explicit .NET registry views | OpenSubKey reports requested access not allowed | Not independently readable through this API | Absent; 32/64-bit parent views readable, 14 subkeys |

Outside-sandbox PowerShell also reads the explicit `HKEY_USERS\S-1-5-21-2242542228-2552922399-2298678612-1001\Software\Microsoft\SystemCertificates\Root\Certificates` parent, with zero subkeys and no known CA entry. Its HKCU result is the same. Both Registry32 and Registry64 HKLM checks agree. The sandboxed HKCU OpenSubKey exception is recorded separately from the Medium-integrity key's absence; absence is not described as access denied.

The interactive commands check existence, not read permission or serialized Blob size. Previous interactive certificate-provider metadata confirms Subject/Issuer `O=KOBI Local Development Only, CN=KOBI Local Development CA 2026`, thumbprint as above, validity 16 October 2026 00:42:53 IST to 15 October 2031 00:42:53 IST. These values match the staged public CA. Encoded certificate/serialized registry Blob size was not collected interactively; automation has no matching Root Blob to measure. No binary registry values or private keys were output/exported.

Identity remains the same host `LAPTOP-PTFFHOMV`, account `LAPTOP-PTFFHOMV\Sanjog`, SID `S-1-5-21-2242542228-2552922399-2298678612-1001`. The interactive token and the outside-sandbox automation token are Medium integrity. Running the same Windows PowerShell 5.1 version did not reconcile the views. A different user, bitness of the queried HKLM view, or Low integrity alone is not an established explanation.

Conclusion: the exact CA entry is present in interactive HKCU and absent in automation's observable HKCU under the same SID. The Medium automation process can read a Root parent, so it is inaccurate to claim it cannot read any Root registry/store. Whether this is an isolated/brokered registry view, profile context, snapshot, policy or another cause remains UNKNOWN. No isolation bypass or hive-loading attempt was made.

### 5–6. Browser processes, trust mechanism and isolation

Read-only CIM process/owner/path queries identify this desktop tree:

- Main `ChatGPT.exe`, PID 18736, parent PID 4632; executable `C:\Program Files\WindowsApps\OpenAI.Codex_26.1007.9446.0_x64__2p2nqsd0c76g0\app\ChatGPT.exe`; owner Sanjog; Medium integrity (RID 8192), AppContainer flag 0.
- Child `ChatGPT.exe`, PID 19080, parent 18736, same executable and owner; command-line type utility, utility subtype `network.mojom.NetworkService`; Medium integrity (RID 8192), AppContainer flag 0.
- An inspected renderer, PID 2012, parent 18736, same executable/owner; integrity RID 0, AppContainer flag 0.
- The observed desktop network/renderer process types are consistent with a Chromium-style multiprocess runtime. They do not identify whether the rejected URL was verified by that process, another broker, or a particular certificate-verifier implementation.

Only allowlisted TLS/security/profile flag presence was output. No inspected process showed the checked `--ignore-certificate-errors`, `--ignore-certificate-errors-spki-list`, `--allow-insecure-localhost`, `--disable-web-security`, `--use-system-cert-verifier`, `--use-mock-cert-verifier`, `--cert-verifier-builtin`, `--ssl-key-log-file`, `--no-sandbox` or `--disable-setuid-sandbox` switches. A crashpad-handler child has a `--user-data-dir` switch; its value was not printed. This is not proof of a separate CA database. No relevant certificate/TLS feature token was exposed by the network-service command-line check. Full command lines and profile contents were not dumped.

The automation shell has none of these allowlisted environment variables set: `CODEX_CA_CERTIFICATE`, `SSL_CERT_FILE`, `SSL_CERT_DIR`, `NODE_EXTRA_CA_CERTS`, `NODE_TLS_REJECT_UNAUTHORIZED`, `CURL_CA_BUNDLE`, `REQUESTS_CA_BUNDLE`. This is the shell's environment only; the verifier process environment was not obtained or assumed to match. No unrelated environment values were read/output.

Supported browser tooling identifies an in-app provider and exposes rendered DOM/logs/screenshots plus visibility/viewport controls; it does not expose peer certificates, remote socket address, verifier PID, selected root database or transport environment in this session. No raw CDP attachment, browser profile inspection, setting change or permission expansion was attempted.

Official documentation review using the OpenAI Docs skill:

- [Browser documentation](https://learn.chatgpt.com/docs/browser) describes the built-in browser and optional, separately approved Developer mode/CDP diagnostics. The fetched page does not document this installation's certificate trust backend. Developer mode was NOT enabled or used.
- [Authentication documentation](https://learn.chatgpt.com/docs/auth) documents custom CA environment variables for Codex authentication/HTTPS. This does not establish that the in-app browser verifier consumes them; no variable was set and no such remedy is recommended on this evidence.

Therefore Windows trust, a separate/bundled root database, host-store independence and browser container isolation remain UNKNOWN. The observed network process is not an AppContainer, but that fact alone does not rule out brokering or other isolation. No SYSTEM/service owner was found for the identified processes.

### 7. Certificate and endpoint evidence — historical versus current

Expected staged leaf and historical successful PHP/cURL peer SHA-256: `83f096f60d695d66513b57da98b4ba6cc41763b65b045afcd01f3db76cc18087`; subject localhost; issuer KOBI Local Development CA 2026; serial `11E8087A5EA2EEBF9384F8BE4D280E1C`; SAN DNS:localhost; validity 15 October 2026 19:13:43 UTC to 15 October 2027 19:13:43 UTC. Historical PHP/cURL achieved HTTPS 200, verification result 0, while the browser rejected the requested endpoint. Peer/hostname verification remained enabled. This is historical cutover evidence only.

Current restored Apache configuration points to `conf/ssl.crt/server.crt`. Its file metadata is subject/issuer CN localhost, serial `B5C752C98781B503`, SHA-256 `016973380c0f1df00bd9593ed8d5efa3706cd6df7993f6141272b80522acdd23`, validity 10 November 2009 23:48:47 UTC to 8 November 2019 23:48:47 UTC, no SAN/extensions. Earlier strict post-rollback handshake evidence rejected a self-signed localhost peer; it did not expose a complete fingerprint. Current restored SSL/PHP hashes still match the protected original backups.

Browser peer certificate: UNKNOWN; no direct peer metadata is available. Current rejection is expected for the rolled-back invalid certificate and cannot be used to diagnose new-CA acceptance. No additional cutover or certificate retrieval bypass was performed.

Both contexts resolve localhost to `::1` and `127.0.0.1`. Historical/current PHP evidence uses `https://localhost/kobi/public/login`, IPv4 `127.0.0.1:443`. The browser requests the same URL, but its actual IP and certificate remain unavailable. Equality of browser/PHP transport endpoints is not proven.

### 8. Hypothesis matrix

| Hypothesis | Status | Evidence / limit |
|---|---|---|
| A. Automation cannot access the interactive user's Current User Root store | UNKNOWN as a whole-store claim | Exact interactive CA key is not visible in Medium automation; its Root parent is readable. Low sandbox's explicit HKCU access is denied. The cause/scope is not established. |
| B. Browser uses a separate trust database | UNKNOWN | No supported database/backend metadata available. |
| C. Browser automation runs in an isolated environment | UNKNOWN for the browser verifier | Shell sandbox restriction and differing observable registry results are confirmed; the identified network process has AppContainer=0. This does not prove browser container isolation. |
| D. Browser uses a bundled CA store | UNKNOWN | No evidence establishing a bundled CA source. |
| E. Browser is brokered through another process/service | UNKNOWN for actual certificate verification | A separate desktop network utility process is confirmed; routing/verifier ownership for this URL is not exposed. |
| F. Browser sees a different certificate/endpoint | UNKNOWN | Same requested URL; browser peer/IP unavailable. |
| G. Trust available to PHP/cURL but not browser automation | CONFIRMED as historical observed behavior | Historical verified PHP request succeeded while browser rejected the same requested URL. This does not establish peer equality or the cause. |
| H. Interactive Windows Root trust is not propagated into browser automation | UNKNOWN as a causal mechanism | Interactive Root presence and automation-view absence are confirmed; propagation semantics and browser trust source are not exposed. |

### 9–10. Confirmed finding and remaining unknowns

Confirmed: same user/SID, different observable exact HKCU certificate-entry state; the matching Root CA is not merely in the wrong store interactively. No exact underlying browser-trust root cause is established. In particular, do not describe a bundled browser store, package registry virtualization, service identity, missing propagation or stale cache as proven. Remaining unknowns are the origin of the registry-view divergence, the effective browser verifier/trust source, and its peer certificate/IP.

### 11–13. Smallest safe next step, safety and why not applied

No certificate/trust/configuration remediation is justified by the current evidence. The smallest safe next step is a vendor-supported diagnostic of the in-app browser's effective verifier/root source and the same-SID registry-view discrepancy, using the recorded process/token/registry evidence. If additional diagnostic access is required, it must be separately scoped and approved; do not silently enable Developer mode, attach through raw CDP, install into LocalMachine Root, or set a CLI CA variable as a guessed browser fix.

This next step is safe because it seeks the missing read-only evidence without enlarging certificate trust or changing the server. No remediation was applied because this task explicitly authorizes diagnosis only and the cause is not established.

One broader shell diagnostic was rejected by automatic approval review before execution because its proposed configuration-file excerpt could expose secrets. It was replaced by process-only output and narrow boolean/known-enum checks; no rejected excerpt ran and no secret was output.

KOBI safety: original SSL/PHP file hashes are unchanged. Read-only source checks confirm `pilot_enabled=false` and `pilot_internal_enabled=false`; Treatment configuration defaults `TREATMENT_PUBLICATION_MODE` to `hold`, with no corresponding override found in the inspected .env text. The failed probe for a literal `publication_mode => 'hold'` was only a pattern mismatch against the existing env-helper expression, not evidence of an enabled publication mode. A nonexistent `public_enabled` key was not interpreted as a feature state. No application/configuration, authentication, API, limiter, certificate, registry, browser setting, hosts, DNS or firewall changes occurred. No direct database connection/SQL/schema/data writes or Git operations occurred. Medicine/Treatment activation state was not modified; live DB governance was not queried. Step 50B remains NOT EXECUTED.

Only `docs/task-local-https-certificate-remediation.md` was updated. No other project file was changed or created during this diagnosis. Stop; no proposed remediation was applied.
