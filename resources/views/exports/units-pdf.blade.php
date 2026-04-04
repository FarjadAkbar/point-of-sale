<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Units</title>
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
    <h1>Units — {{ $team->name }}</h1>
    <p>Generated {{ $generatedAt }}</p>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Short</th>
                <th>Decimals</th>
                <th>Base relation</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($units as $u)
                <tr>
                    <td>{{ $u->id }}</td>
                    <td>{{ $u->name }}</td>
                    <td>{{ $u->short_name }}</td>
                    <td>{{ $u->allow_decimal ? 'Yes' : 'No' }}</td>
                    <td>
                        @if ($u->is_multiple_of_base && $u->baseUnit)
                            1 {{ $u->name }} = {{ $u->base_unit_multiplier }} × {{ $u->baseUnit->name }}
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
