<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sales commission agents</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111; }
        h1 { font-size: 16px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; }
        th { background: #f0f0f0; font-weight: bold; }
        tr:nth-child(even) { background: #fafafa; }
    </style>
</head>
<body>
    <h1>Sales commission agents — {{ $team->name }}</h1>
    <p>Generated {{ $generatedAt }}</p>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Contact</th>
                <th>Commission %</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($agents as $a)
                <tr>
                    <td>{{ $a->id }}</td>
                    <td>{{ trim(implode(' ', array_filter([$a->prefix, $a->first_name, $a->last_name]))) }}</td>
                    <td>{{ $a->email ?? '—' }}</td>
                    <td>{{ $a->contact_no ?? '—' }}</td>
                    <td>{{ $a->cmmsn_percent }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
