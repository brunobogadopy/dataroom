create type node_type as enum ('folder', 'document', 'file');
create type workspace_role as enum ('owner', 'admin', 'member', 'guest');
create type permission_level as enum ('view', 'edit', 'manage');

create table workspaces (
  id uuid primary key,
  name text not null,
  slug text not null unique,
  owner_user_id uuid not null,
  storage_quota_bytes bigint not null default 107374182400,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create table nodes (
  id uuid primary key,
  workspace_id uuid not null references workspaces(id) on delete cascade,
  parent_id uuid null references nodes(id) on delete cascade,
  type node_type not null,
  name text not null,
  slug text not null,
  created_by uuid not null,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  deleted_at timestamptz null
);

create unique index nodes_sibling_slug_unique
  on nodes(workspace_id, parent_id, slug)
  where deleted_at is null;

create index nodes_tree_idx on nodes(workspace_id, parent_id);
create index nodes_type_idx on nodes(workspace_id, type);

create table document_revisions (
  id uuid primary key,
  node_id uuid not null references nodes(id) on delete cascade,
  version integer not null,
  content_json jsonb not null,
  plain_text text not null default '',
  created_by uuid not null,
  created_at timestamptz not null default now(),
  unique(node_id, version)
);

create table documents (
  node_id uuid primary key references nodes(id) on delete cascade,
  current_revision_id uuid null references document_revisions(id),
  status text not null default 'draft',
  owner_user_id uuid null,
  review_due_at timestamptz null
);

create table file_versions (
  id uuid primary key,
  node_id uuid not null references nodes(id) on delete cascade,
  version integer not null,
  object_key text not null unique,
  mime_type text not null,
  size_bytes bigint not null,
  sha256 text null,
  extracted_text text null,
  created_by uuid not null,
  created_at timestamptz not null default now(),
  unique(node_id, version)
);

create table files (
  node_id uuid primary key references nodes(id) on delete cascade,
  current_version_id uuid null references file_versions(id),
  mime_type text not null,
  size_bytes bigint not null default 0
);
