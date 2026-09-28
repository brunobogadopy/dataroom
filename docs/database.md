# Database model

## Main tables

### workspaces
`id`, `name`, `slug`, `owner_user_id`, `storage_quota_bytes`, timestamps

### workspace_users
`workspace_id`, `user_id`, `role`, timestamps

### groups
`id`, `workspace_id`, `name`, timestamps

### group_users
`group_id`, `user_id`

### nodes
`id`, `workspace_id`, `parent_id`, `type`, `name`, `slug`, `created_by`, `deleted_at`, timestamps

`type` is one of `folder`, `document`, `file`.

### documents
`node_id`, `current_revision_id`, `status`, `owner_user_id`, `review_due_at`

### document_revisions
`id`, `node_id`, `version`, `content_json`, `plain_text`, `created_by`, `created_at`

### files
`node_id`, `current_version_id`, `mime_type`, `size_bytes`

### file_versions
`id`, `node_id`, `version`, `object_key`, `mime_type`, `size_bytes`, `sha256`, `extracted_text`, `extraction_status`, `extraction_error`, `created_by`, `created_at`

### permissions
`id`, `workspace_id`, `node_id`, `subject_type`, `subject_id`, `permission`

### favorites
`workspace_id`, `user_id`, `node_id`

### activities
`id`, `workspace_id`, `actor_user_id`, `action`, `node_id`, `metadata_json`, `created_at`
