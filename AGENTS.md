\# KOBI Core — Codex Development Instructions



\## 1. Project



KOBI Core is a HealthTech / medical knowledge and personal health platform.



The project is developed using:



\- PHP 8.2+

\- MariaDB / MySQL

\- Custom MVC framework

\- Repository Pattern

\- Service Layer

\- Dependency Injection

\- Bootstrap 5.3

\- Server-rendered PHP views

\- AJAX / JSON APIs where appropriate



The existing KOBI architecture and conventions must be preserved.



\---



\## 2. Core Architecture



The established application flow is:



Controller

&#x20;   ↓

Service

&#x20;   ↓

Repository

&#x20;   ↓

Database



Respect this separation.



\### Controllers



Controllers should:



\- Handle HTTP requests.

\- Perform explicit authentication/authorization checks where required.

\- Validate CSRF for state-changing requests.

\- Collect request input.

\- Call appropriate services.

\- Render views using the existing controller/view mechanism.

\- Redirect using the existing global `redirect()` helper.



Controllers should not contain database queries or business logic that belongs in services/repositories.



\### Services



Services contain business logic and application rules.



Do not bypass the service layer from controllers unless an existing KOBI convention explicitly requires it.



\### Repositories



Repositories contain database access.



Use the existing `BaseRepository` methods and repository conventions.



Do not introduce a second database-access pattern.



\---



\## 3. Existing KOBI Conventions



Preserve these established conventions:



\- Use `AuthService::id()` for the authenticated user ID.

\- Use `HealthProfileRepository::findAccessible($profileId, $userId)` when accessing health profiles.

\- Perform explicit authentication and authorization checks.

\- Use `csrfField()` for CSRF-protected forms.

\- Use `$this->view()` for server-rendered views.

\- Use the global `redirect()` helper for redirects.

\- Preserve the existing global JavaScript architecture.

\- Use AJAX / JSON endpoints where the existing application architecture calls for them.



Do not replace these conventions with alternative approaches.



\---



\## 4. Authentication and Account Security



KOBI currently supports:



\- Email/password authentication.

\- Email verification.

\- Password reset.

\- Google authentication.

\- Google account linking.

\- Connected-account management.

\- Safe Google account unlinking.



The application uses:



\- `users.email\_verified\_at`

\- `user\_auth\_identities`

\- `user\_auth\_tokens`

\- `users.password\_set\_at`



\### Important authentication rules



Do not introduce automatic account merging based solely on matching email addresses.



A Google identity must not automatically be linked to an existing KOBI account merely because the Google email matches the KOBI email.



External authentication identities belong in:



`user\_auth\_identities`



Do not introduce provider-specific columns into `users`.



Do not break the existing email-verification flow.



Do not change authentication behavior unless the task explicitly requires it.



\---



\## 5. Database Rules



The database schema available in:



`database/structure.sql`



is the authoritative schema reference for the current project.



Before making database-related changes:



1\. Inspect `database/structure.sql`.

2\. Inspect the relevant repository/service/controller code.

3\. Understand the existing relationships and constraints.

4\. Do not infer nonexistent tables or columns.

5\. Do not rely on an outdated schema from memory.



If a schema change is required:



\- Clearly identify the required SQL/schema modification.

\- Keep the schema consistent with the application code.

\- Do not silently make destructive schema changes.

\- Do not drop existing tables/columns/data unless explicitly instructed.

\- Preserve existing foreign keys, indexes, unique constraints, and relationships unless the task explicitly requires changing them.



The developer is responsible for applying database changes to the local database unless explicitly instructed otherwise.



Do not execute destructive database operations against a live/production database.



\---



\## 6. Git Safety



The current Git repository contains the stable/tested KOBI codebase.



Codex is working in the local development workspace.



\### Do NOT:



\- push changes

\- commit changes

\- reset the repository

\- force-reset files

\- discard user changes

\- checkout another branch

\- merge branches

\- rebase

\- rewrite Git history



unless the user explicitly instructs you to do so.



The developer will manually review and commit/push approved changes.



Never assume that a change should be committed.



\---



\## 7. Local Development Safety



Work only within the supplied KOBI project.



Do not modify files outside the project unless explicitly instructed.



Do not modify environment-specific credentials.



Never expose or print:



\- passwords

\- API keys

\- OAuth client secrets

\- database passwords

\- session secrets

\- `.env` credentials

\- other private credentials



Do not replace real credentials with sample credentials.



\---



\## 8. Existing Code First



Before creating a new class, method, table, route, service, repository, or helper:



1\. Search the existing project.

2\. Determine whether equivalent functionality already exists.

3\. Reuse existing functionality when appropriate.

4\. Extend existing architecture rather than duplicating it.



Do not create duplicate methods or alternative implementations simply because an existing implementation is not immediately visible.



Preserve established naming and conventions.



\---



\## 9. Minimal Architectural Change



Do not introduce:



\- new frameworks

\- new ORM systems

\- new routing systems

\- new JavaScript frameworks

\- new authentication systems

\- unnecessary design patterns

\- unnecessary dependencies



unless explicitly requested.



Prefer the smallest clean change that fits the existing KOBI architecture.



\---



\## 10. Existing JavaScript



Preserve the existing global JavaScript architecture.



Do not introduce inline JavaScript when existing global/page-specific JavaScript mechanisms can handle the behavior.



Before adding JavaScript:



1\. Search for existing related functionality.

2\. Reuse existing global utilities where appropriate.

3\. Follow the existing page-specific JS loading conventions.



\---



\## 11. Security



KOBI handles personal health information.



Security must be treated as a first-class requirement.



For state-changing requests:



\- Validate authentication.

\- Validate authorization.

\- Validate CSRF where applicable.

\- Validate user input.

\- Do not trust client-provided user IDs.

\- Do not expose another user's health information.

\- Do not bypass existing access-control mechanisms.



Never weaken an existing security check simply to make a feature easier to implement.



\---



\## 12. Health Data



Health profiles and health records are sensitive application data.



When implementing health-related functionality:



\- Respect existing profile-access rules.

\- Do not assume that an authenticated user automatically has access to every profile.

\- Use the established accessibility checks.

\- Do not expose health data through unintended API responses.

\- Do not add unnecessary health information to logs.



\---



\## 13. Views and UI



Use the existing Bootstrap 5.3 styling conventions.



Preserve existing:



\- navigation

\- layout

\- responsive behavior

\- forms

\- alerts

\- validation patterns

\- accessibility conventions



Do not redesign unrelated UI while implementing a feature.



\---



\## 14. API / AJAX



When implementing AJAX or JSON functionality:



\- Follow existing KOBI endpoint conventions.

\- Return appropriate JSON responses.

\- Validate request parameters.

\- Do not expose sensitive information.

\- Preserve existing frontend/backend contracts.



Do not convert server-rendered functionality to a SPA or API-only implementation unless explicitly requested.



\---



\## 15. Database Queries



Use the existing repository/base-repository abstraction.



Do not place raw SQL directly inside controllers or views.



When writing SQL:



\- Use parameterized queries.

\- Preserve existing naming conventions.

\- Respect existing indexes and constraints.

\- Consider duplicate/unique-key behavior.

\- Consider authorization implications of queries involving user/profile IDs.



\---



\## 16. Error Handling



Follow existing KOBI error-handling conventions.



Do not expose:



\- SQL errors

\- stack traces

\- filesystem paths

\- credentials

\- internal implementation details



to normal users.



Use meaningful application-level error messages.



Do not swallow exceptions silently.



\---



\## 17. Testing and Verification



After implementation:



1\. Check PHP syntax for modified PHP files.

2\. Inspect the final diff.

3\. Search for references to changed methods/classes/routes.

4\. Check that existing functionality has not been unintentionally changed.

5\. Test relevant success and failure paths where possible.



For authentication/security features, test both:



\- authorized/successful behavior

\- unauthorized/failure behavior



Do not declare a task complete merely because the code was written.



\---



\## 18. Scope Control



Implement only the requested task.



Do not:



\- refactor unrelated code

\- prettify unrelated files

\- rename unrelated classes

\- modify unrelated database tables

\- upgrade dependencies

\- change formatting throughout the project

\- fix unrelated issues discovered during implementation



If you discover an unrelated problem, report it separately rather than silently changing it.



\---



\## 19. When Requirements Are Unclear



If the repository contains conflicting implementations or the requested behavior conflicts with an existing security/architecture rule:



\- Inspect the relevant code first.

\- Do not guess.

\- Do not silently replace the existing architecture.

\- Report the conflict and explain what needs clarification.



For straightforward implementation details, use the existing KOBI conventions rather than asking unnecessary questions.



\---



\## 20. Completion Report



After completing a task, report:



\### Files modified



List every modified file.



\### Files created



List every newly created file.



\### Database changes



Clearly state whether the database schema was changed.



If SQL was required, show the SQL that should be applied separately.



\### Implementation summary



Briefly describe what was implemented.



\### Testing performed



List syntax checks, tests, or manual verification performed.



\### Potential concerns



List any remaining issues or things the developer should manually test.



Do not claim that something was tested if it was not actually tested.



\---



\## 21. Final Principle



KOBI is an existing application.



Do not treat it as a blank project.



Understand the existing implementation first, then make the smallest correct change that integrates with the current architecture.



Preserve working functionality.



Prefer consistency over cleverness.



Security and data integrity take priority over implementation speed.

