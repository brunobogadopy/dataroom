# Dataroom v0.6

Company knowledge + file storage built around one core idea: **folders, documents and files live in the same tree**.

## v0.6: comments, mentions and notifications

### Comments
Users who can view a document or file can comment on it. Comments are permission-aware and remain attached to the node.

### @mentions
Mention a teammate with their workspace email:

```text
@person@company.com
```

A mention only creates a notification if that user is a member of the workspace and can currently view the referenced node. This prevents restricted-content names or excerpts from leaking through notifications.

### Notifications
Dataroom now includes a global notifications inbox:
- unread/read state
- mark one notification as read
- mark all as read
- jump directly to the mentioned folder, document or file

Notification payloads store the workspace slug and node type at creation time so links remain usable without exposing storage URLs.

### Activity
Creating and deleting comments is recorded in the existing workspace activity log.

### Existing v0.5 foundation
- document history and restore
- file versions
- favorites and recent
- trash and permanent deletion
- activity feed
- CI on pull requests

## Upgrade

```bash
php artisan migrate --force
```

No additional infrastructure service is required for v0.6.
