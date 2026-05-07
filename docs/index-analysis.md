# Index Analysis

This note documents the current `ports` table indexes and the observed query plan behavior in the local environment.

## Table Indexes

The `ports` table currently uses these indexes:

- `ports_unlocode_unique` on `unlocode`
- `ports_name_index` on `name`
- `ports_country_code_index` on `country_code`
- `ports_country_code_name_index` on `(country_code, name)`

These indexes support the two main workloads of the assignment:

1. fast exact lookups / upserts by `unlocode`
2. fast filtering by `country_code` with listing ordered by `name`

## Query Plan Results

The local environment uses SQLite, so the exact text of the plan differs from MySQL, but the index usage pattern is still useful for analysis.

### 1. Exact search by UNLOCODE

Query:

```sql
SELECT id, unlocode, name, country_name, country_code, updated_at
FROM ports
WHERE unlocode = 'ESLCG'
ORDER BY name
LIMIT 100 OFFSET 0;
```

Plan:

```text
SEARCH ports USING INDEX ports_unlocode_unique (unlocode=?)
```

Conclusion:

- the unique `unlocode` index is used correctly
- this supports both exact search and `upsert` efficiently

### 2. Filter by country code and order by name

Query:

```sql
SELECT id, unlocode, name, country_name, country_code, updated_at
FROM ports
WHERE country_code = 'ESP'
ORDER BY name
LIMIT 100 OFFSET 0;
```

Plan:

```text
SEARCH ports USING INDEX ports_country_code_name_index (country_code=?)
```

Conclusion:

- the composite `(country_code, name)` index is used
- this is the best fit for the country dropdown filter plus alphabetical listing

### 3. Search by port name with `%term%`

Query:

```sql
SELECT id, unlocode, name, country_name, country_code, updated_at
FROM ports
WHERE name LIKE '%port%'
ORDER BY name
LIMIT 100 OFFSET 0;
```

Plan:

```text
SCAN ports USING INDEX ports_name_index
```

Conclusion:

- the `name` index exists, but `%term%` search cannot fully use it as a selective lookup
- this is the expected tradeoff of substring search
- for the assignment size, this is acceptable
- for larger datasets, a prefix search, full-text search, or dedicated search engine would scale better

### 4. Country filter plus `%term%` name search

Query:

```sql
SELECT id, unlocode, name, country_name, country_code, updated_at
FROM ports
WHERE country_code = 'ESP'
  AND name LIKE '%port%'
ORDER BY name
LIMIT 100 OFFSET 0;
```

Plan:

```text
SEARCH ports USING INDEX ports_country_code_name_index (country_code=?)
```

Conclusion:

- the composite index still helps by narrowing the result set to one country first
- the `%term%` part remains the limiting factor, but the query is still better than a full-table scan across all countries

## Final Assessment

The current indexing strategy is appropriate for the assignment:

- `unique(unlocode)` supports exact match and `upsert`
- `index(country_code)` supports direct filtering
- `index(name)` helps ordering and limited name search scenarios
- `index(country_code, name)` is the most useful listing index for the UI and API

The main known limitation is substring search with `LIKE '%term%'`, which is acceptable for this project size but would be the first area to revisit in a production-scale system.
