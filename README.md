# Dataroom v0.4

Company knowledge + file storage built around one core idea: **folders, documents and files live in the same tree**.

## v0.4: multi-user access
Dataroom now adds a real workspace authorization layer on top of the v0.3 editor/navigation experience.

### Workspace roles
- **Owner** — full control; cannot be removed or demoted.
- **Admin** — manages members, invitations and restricted content.
- **Member** — can create and edit workspace content they can access.
- **Viewer** — read-only access.

### Invitations
Owners/admins can create a 7-day invitation tied to an email address. The API returns a one-time invitation URL token; the recipient logs in with that email and accepts the invitation.

Email delivery itself is not wired yet; v0.4 exposes a copyable invite link so the authorization workflow is usable before transactional email is added.

### Restricted nodes
Any folder, document or file can be switched from **workspace** to **restricted** visibility by an owner/admin.

Restricted grants:
- Can view
- Can edit

Restrictions inherit down the tree. A user must satisfy every restricted ancestor in the path, so a child grant cannot bypass a protected parent folder.

### Permission-aware operations
Authorization is enforced server-side for:
- browsing and breadcrumbs
- document read/write
- folder/document creation
- file upload
- file preview/download
- node deletion
- search results

Meilisearch results are permission-filtered before returning to the client, with the PostgreSQL fallback using the same access checks.

## Existing stack
- Laravel 13 / PHP 8.4
- Next.js 16 + Tiptap 3
- PostgreSQL
- Redis
- Meilisearch
- bunny.net Storage

## Upgrade

```bash
docker compose exec api php artisan migrate
```

No new infrastructure service is required for v0.4.
