<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Customers</title>
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
    <h1>Customers — {{ $team->name }}</h1>
    <p>Generated {{ $generatedAt }}</p>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Code</th>
                <th>Role</th>
                <th>Entity</th>
                <th>Name</th>
                <th>Mobile</th>
                <th>Email</th>
                <th>City</th>
                <th>Opening</th>
                <th>Credit limit</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($customers as $customer)
                <tr>
                    <td>{{ $customer->id }}</td>
                    <td>{{ $customer->customer_code }}</td>
                    <td>{{ $customer->party_role }}</td>
                    <td>{{ $customer->entity_type }}</td>
                    <td>{{ $customer->display_name }}</td>
                    <td>{{ $customer->mobile }}</td>
                    <td>{{ $customer->email }}</td>
                    <td>{{ $customer->city }}</td>
                    <td>{{ $customer->opening_balance }}</td>
                    <td>{{ $customer->credit_limit ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
