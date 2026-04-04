/** Trim trailing zeros for tax % display (matches backend TaxPercentDisplay). */
export function formatTaxPercent(
    value: string | number | null | undefined,
): string {
    if (value === null || value === undefined || value === '') {
        return '0';
    }

    const n =
        typeof value === 'number' ? value : Number.parseFloat(String(value));

    if (Number.isNaN(n)) {
        return String(value);
    }

    const s = n.toFixed(4).replace(/\.?0+$/, '');

    return s || '0';
}

/** Label for a tax rate in group pickers; avoids "SGST@8%@8.0000%". */
export function taxRateOptionLabel(opt: {
    name: string;
    amount: string;
}): string {
    const name = opt.name.trim();
    const pct = formatTaxPercent(opt.amount);

    if (name !== '' && name.includes('@') && name.includes('%')) {
        return name;
    }

    return name === '' ? `${pct}%` : `${name} (${pct}%)`;
}
