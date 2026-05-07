# UML / Sequence Diagrams

These diagrams describe the two main flows of the application:

1. syncing ports from Risk4Sea into the local database
2. reading ports through the web UI or API with cached listing results

## 1. Sync Flow

```mermaid
sequenceDiagram
    participant User as "User / CLI"
    participant Command as "SyncPorts Command"
    participant Queue as "Queue"
    participant Job as "SyncPortsJob"
    participant Service as "PortSyncService"
    participant Client as "Risk4SeaClient"
    participant API as "Risk4Sea API"
    participant Repo as "PortRepository"
    participant DB as "MySQL / SQLite"
    participant Cache as "Cache"

    User->>Command: Run `php artisan r4s:sync-ports`
    Command->>Queue: Dispatch `SyncPortsJob`
    Queue->>Job: Execute job
    Job->>Service: sync(search?)
    Service->>Client: listPorts(search?)
    Client->>API: GET /api/v1/port-calls/ports
    API-->>Client: JSON response
    Client-->>Service: Normalized port payloads
    Service->>Service: Validate and deduplicate ports
    Service->>Repo: existingUnlocodes(...)
    Repo->>DB: Lookup existing UNLOCODE values
    DB-->>Repo: Existing rows
    Repo-->>Service: Existing UNLOCODE list
    Service->>Repo: upsert(...)
    Repo->>DB: Insert / update ports
    DB-->>Repo: Upsert completed
    Service->>Cache: Forget countries cache
    Service-->>Job: Sync summary
    Job-->>Queue: Job finished
```

### Notes

- the command no longer performs the sync inline; it dispatches a queued job
- the service owns the sync orchestration logic
- the repository owns the database persistence logic
- the countries cache is explicitly cleared after a successful sync

## 2. Listing / Read Flow

```mermaid
sequenceDiagram
    participant User as "Browser / API Client"
    participant Controller as "Web or API Controller"
    participant Listing as "PortListingService"
    participant Cache as "Cache"
    participant DB as "MySQL / SQLite"

    User->>Controller: GET / or GET /api/ports
    Controller->>Listing: list(filters, perPage, page)
    Listing->>Cache: remember(list cache key)

    alt Cache hit
        Cache-->>Listing: Cached page payload
    else Cache miss
        Listing->>DB: Query filtered ports
        DB-->>Listing: Rows + total count
        Listing->>Cache: Store cached payload
    end

    Listing-->>Controller: LengthAwarePaginator
    Controller-->>User: Blade HTML or paginated JSON
```

### Notes

- the web controller and API controller share the same listing service
- paginated listing results are cached by filter set and page number
- the country dropdown uses a separate fixed cache key
- this keeps the read flow fast without changing the public API of the controllers
