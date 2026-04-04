<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Warranties</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        h1 { font-size: 16px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; }
        th { background: #f0f0f0; font-weight: bold; }
        tr:nth-child(even) { background: #fafafa; }
    </style>
</head>
<body>
    <h1>Warranties — {{ $team->name }}</h1>
    <p>Generated {{ $generatedAt }}</p>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Description</th>
                <th>Duration</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($warranties as $w)
                <tr>
                    <td>{{ $w->id }}</td>
                    <td>{{ $w->name }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($w->description ?? '—', 80) }}</td>
                    <td>{{ $w->duration_value }} {{ $w->duration_unit }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
