<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Suppliers</title>
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
    <h1>Suppliers — {{ $team->name }}</h1>
    <p>Generated {{ $generatedAt }}</p>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Code</th>
                <th>Type</th>
                <th>Name</th>
                <th>Mobile</th>
                <th>Email</th>
                <th>City</th>
                <th>Opening balance</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($suppliers as $supplier)
                <tr>
                    <td>{{ $supplier->id }}</td>
                    <td>{{ $supplier->supplier_code }}</td>
                    <td>{{ $supplier->contact_type }}</td>
                    <td>{{ $supplier->display_name }}</td>
                    <td>{{ $supplier->mobile }}</td>
                    <td>{{ $supplier->email }}</td>
                    <td>{{ $supplier->city }}</td>
                    <td>{{ $supplier->opening_balance }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
