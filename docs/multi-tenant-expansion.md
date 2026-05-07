# Multi-Tenant Expansion (Conceptual)

This project is currently implemented as a single-tenant application. A natural SaaS-oriented expansion would be to support multiple customers (tenants) while keeping their data, credentials, jobs, and cache entries isolated.

## Proposed Tenant Model

The first step would be to introduce a `tenants` table, for example:

- `id`
- `name`
- `slug`
- `risk4sea_base_url`
- `risk4sea_token`
- timestamps

Each tenant would represent one customer account with its own external API configuration and sync behavior.

## Data Isolation

To make the `ports` dataset tenant-aware, the `ports` table would gain a `tenant_id` column and all relevant indexes would be updated accordingly.

Example direction:

- `tenant_id`
- unique index on `(tenant_id, unlocode)`
- listing indexes such as `(tenant_id, country_code, name)`

This would ensure:

- one tenant cannot see another tenant's ports
- `upsert` remains correct per tenant
- listing queries remain efficient when filtering within a tenant scope

## Application Layer Changes

The sync and listing layers would become tenant-aware:

- `Risk4SeaClient` would use the current tenant's credentials
- `PortSyncService` would sync ports for one tenant at a time
- `PortRepository` would query and upsert within a tenant scope
- `PortListingService` would read only tenant-specific data

At the controller level, every request would resolve the active tenant before querying data.

## Queue and Scheduling

Queued sync jobs would be dispatched per tenant.

Example conceptual flow:

- a tenant-specific sync command or scheduler dispatches `SyncPortsJob($tenantId)`
- the job loads tenant credentials
- the sync runs only for that tenant's records

This allows:

- independent sync schedules per customer
- failure isolation between tenants
- better scalability when the number of tenants grows

## Cache Isolation

Cache keys would need tenant scoping.

For example:

- `tenant:1:ports:countries`
- `tenant:1:ports:list:{hash}`

This prevents cache collisions and ensures each tenant receives only its own cached results.

## Authentication and Authorization

Users would belong to one tenant, or to a permitted set of tenants in a larger admin model.

All UI and API access would be scoped by tenant-aware authorization rules so that:

- normal users can access only their tenant data
- platform admins could inspect multiple tenants when needed

## Why This Design Scales

This approach scales well because it separates concerns cleanly:

- credentials are isolated per tenant
- data is isolated per tenant
- jobs are isolated per tenant
- cache is isolated per tenant

It also matches common SaaS architecture patterns and allows future growth without rewriting the entire application.

## Summary

If this application evolved into a multi-customer SaaS product, the most important change would be introducing tenant-aware scoping across:

- database schema
- sync jobs
- cache keys
- external API credentials
- authorization

The current layered structure of the project already makes that expansion easier, because the sync, listing, and persistence logic are separated into dedicated services and repositories.
