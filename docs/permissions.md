# Permissions

## Workspace roles
- owner
- admin
- member
- guest

## Object permissions
- view
- edit
- manage

Permissions may be granted to:
- a user
- a group
- all workspace members

## Inheritance
Nodes inherit access from their parent unless explicit ACL entries override or narrow access.

Effective permission is calculated from:
1. workspace role
2. inherited ACLs
3. direct ACLs
4. ownership/admin bypass rules

Search must return only nodes the requester can `view`.
