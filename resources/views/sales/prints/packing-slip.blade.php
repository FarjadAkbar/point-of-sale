@php
    $money = static fn ($v) => number_format((float) $v, 2);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — {{ $sale->invoice_no ?? 'Sale #'.$sale->id }}</title>
    <style>
        body { font-family: ui-sans-serif, system-ui, sans-serif; margin: 24px; color: #111; }
        h1 { font-size: 1.35rem; margin: 0 0 6px; }
        .muted { color: #555; font-size: 0.875rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 0.8125rem; vertical-align: top; }
        th { background: #f4f4f5; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p class="muted">
        <strong>Invoice:</strong> {{ $sale->invoice_no ?? '—' }}
        &nbsp;|&nbsp;
        <strong>Date:</strong> {{ $sale->transaction_date?->format('Y-m-d H:i') ?? '—' }}
    </p>
    <p class="muted">
        <strong>Customer:</strong> {{ $sale->customer?->display_name ?? '—' }}
        <br>
        <strong>Location:</strong> {{ $sale->businessLocation?->name ?? '—' }}
    </p>
    @if ($sale->shipping_address)
        <p class="muted"><strong>Ship to:</strong><br>{{ $sale->shipping_address }}</p>
    @endif

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Product</th>
                <th>SKU</th>
                <th>Qty</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sale->lines as $i => $line)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $line->product?->name ?? '—' }}</td>
                    <td>{{ $line->product?->sku ?? '—' }}</td>
                    <td>{{ $money($line->quantity) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
