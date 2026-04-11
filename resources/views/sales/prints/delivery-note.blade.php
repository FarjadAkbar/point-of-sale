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
        .box { margin-top: 12px; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 0.875rem; }
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
    @if ($sale->delivered_to)
        <p class="muted"><strong>Delivered to:</strong> {{ $sale->delivered_to }}</p>
    @endif
    @if ($sale->delivery_person)
        <p class="muted"><strong>Delivery person:</strong> {{ $sale->delivery_person }}</p>
    @endif
    @if ($sale->shipping_status)
        <p class="muted"><strong>Shipping status:</strong> {{ $sale->shipping_status }}</p>
    @endif
    @if ($sale->shipping_address)
        <div class="box">
            <strong>Delivery address</strong><br>
            {{ $sale->shipping_address }}
        </div>
    @endif
    @if ($sale->shipping_customer_note)
        <div class="box">
            <strong>Shipping note</strong><br>
            {{ $sale->shipping_customer_note }}
        </div>
    @endif

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Product</th>
                <th>SKU</th>
                <th>Qty</th>
                <th>Line total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sale->lines as $i => $line)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $line->product?->name ?? '—' }}</td>
                    <td>{{ $line->product?->sku ?? '—' }}</td>
                    <td>{{ $money($line->quantity) }}</td>
                    <td>{{ $money($line->line_total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if ($sale->payments->isNotEmpty())
        <h2 style="margin-top:24px;font-size:1rem;">COD / payments</h2>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Method</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sale->payments as $p)
                    <tr>
                        <td>{{ $p->paid_on?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td>{{ $p->method ?? '—' }}</td>
                        <td>{{ $money($p->amount) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
