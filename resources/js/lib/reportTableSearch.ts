/**
 * Client-side substring match over a row object (used with StandardDataTable search on report pages).
 */
export function reportRowMatchesSearch(row: unknown, needle: string): boolean {
    const t = needle.trim().toLowerCase();
    if (!t) {
        return true;
    }
    try {
        return JSON.stringify(row).toLowerCase().includes(t);
    } catch {
        return true;
    }
}
