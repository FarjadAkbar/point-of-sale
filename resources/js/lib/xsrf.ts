export function xsrfToken(): string {
    const row = document.cookie.split('; ').find((c) => c.startsWith('XSRF-TOKEN='));

    return row?.split('=')[1] ? decodeURIComponent(row.split('=')[1]) : '';
}
