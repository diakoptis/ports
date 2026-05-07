<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ports - {{ config('app.name', 'Laravel') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>

<body class="bg-body-tertiary">
    <div class="container py-5">
        <div class="mb-4">
            <div>
                <h1 class="h2 mb-1">Ports</h1>
                <p class="text-body-secondary mb-0">Search and filter ports.</p>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('home') }}" class="row g-3">
                    <div class="col-md-5">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" id="search" name="search" value="{{ $filters['search'] }}" class="form-control" placeholder="Search by port name">
                    </div>

                    <div class="col-md-3">
                        <label for="unlocode" class="form-label">Unlocode</label>
                        <input type="text" id="unlocode" name="unlocode" value="{{ $filters['unlocode'] }}" class="form-control" placeholder="Exact match">
                    </div>

                    <div class="col-md-4">
                        <label for="country_code" class="form-label">Country</label>
                        <select id="country_code" name="country_code" class="form-select">
                            <option value="">All countries</option>
                            @foreach ($countries as $country)
                                <option value="{{ $country['country_code'] }}" @selected($filters['country_code'] === $country['country_code'])>
                                    {{ $country['country_name'] }} ({{ $country['country_code'] }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                        <a href="{{ route('home') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="mb-3">
            <p class="text-body-secondary mb-0">
                Showing {{ $ports->count() }} of {{ $ports->total() }} ports.
            </p>
        </div>

        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Unlocode</th>
                            <th scope="col">Name</th>
                            <th scope="col">Country</th>
                            <th scope="col">Country Code</th>
                            <th scope="col">Updated At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($ports as $port)
                            <tr>
                                <td class="fw-semibold">{{ $port->unlocode }}</td>
                                <td>{{ $port->name }}</td>
                                <td>{{ $port->country_name }}</td>
                                <td>{{ $port->country_code }}</td>
                                <td>{{ optional($port->updated_at)->format('Y-m-d H:i') ?? 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-body-secondary">
                                    No ports matched the selected filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($ports->hasPages())
                <div class="card-body border-top">
                    {{ $ports->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
    </div>
</body>

</html>
