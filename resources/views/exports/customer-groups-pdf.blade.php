<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Customer groups</title>
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
    <h1>Customer groups — {{ $team->name }}</h1>
    <p>Generated {{ $generatedAt }}</p>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Calculation type</th>
                <th>Amount %</th>
                <th>Selling price group</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($customerGroups as $group)
                <tr>
                    <td>{{ $group->id }}</td>
                    <td>{{ $group->name }}</td>
                    <td>{{ $group->price_calculation_type }}</td>
                    <td>{{ $group->amount ?? '—' }}</td>
                    <td>{{ $group->sellingPriceGroup?->name ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
