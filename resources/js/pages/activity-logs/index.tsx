import { Head, router } from '@inertiajs/react';
import { index } from '@/actions/App/Http/Controllers/ActivityLogController';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { PaginatedResponse } from '@/types';

type ActivityLog = {
    id: number;
    user_id: number | null;
    action: string;
    subject_type: string;
    subject_id: number | null;
    description: string;
    metadata: Record<string, unknown> | null;
    created_at: string;
    user: { id: number; name: string; role: string } | null;
};

type Filters = {
    action?: string;
    subject_type?: string;
};

type Props = {
    logs: PaginatedResponse<ActivityLog>;
    filters: Filters;
};

const ACTION_LABELS: Record<string, { label: string; color: string }> = {
    created:   { label: 'Created',   color: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' },
    updated:   { label: 'Updated',   color: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' },
    deleted:   { label: 'Deleted',   color: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' },
    allocated: { label: 'Allocated', color: 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400' },
    cancelled: { label: 'Cancelled', color: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' },
    moved:     { label: 'Moved',     color: 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400' },
};

const SUBJECT_LABELS: Record<string, string> = {
    Attraction:      'Attraction',
    Session:         'Session',
    GuestAllocation: 'Guest Allocation',
};

function formatDateTime(iso: string) {
    return new Date(iso).toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

export default function ActivityLogsIndex({ logs, filters }: Props) {
    function handleFilter(key: string, value: string) {
        router.visit(
            index.url({
                query: {
                    ...filters,
                    [key]: value === 'all' ? undefined : value,
                },
            }),
            { preserveScroll: true, preserveState: true },
        );
    }

    function handlePage(url: string | null) {
        if (!url) return;
        router.visit(url, { preserveScroll: true, preserveState: true });
    }

    const hasFilters = filters.action || filters.subject_type;

    return (
        <>
            <Head title="Activity Log" />

            <div className="flex flex-col gap-6 p-4 lg:p-6">
                {/* ── Page Header ── */}
                <div className="flex items-end justify-between">
                    <div>
                        <p className="mb-0.5 text-xs font-semibold uppercase tracking-widest text-primary/70">
                            Recreation Admin
                        </p>
                        <h1 className="text-3xl font-bold tracking-tight text-foreground">
                            Activity Log
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Riwayat semua aktivitas yang terjadi di sistem.
                        </p>
                    </div>
                    <span className="mb-1 inline-flex items-center rounded-full bg-primary/10 px-3 py-0.5 text-sm font-semibold text-primary ring-1 ring-primary/20">
                        {logs.total} entri
                    </span>
                </div>

                {/* ── Filter Bar ── */}
                <div className="flex flex-wrap items-center gap-3 rounded-xl border border-border bg-card px-4 py-3 shadow-sm">
                    <span className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                        Filter
                    </span>
                    <div className="h-4 w-px bg-border" />

                    <div className="flex items-center gap-2">
                        <span className="text-sm font-medium text-foreground">Aksi</span>
                        <Select
                            value={filters.action ?? 'all'}
                            onValueChange={(v) => handleFilter('action', v)}
                        >
                            <SelectTrigger className="h-9 w-40">
                                <SelectValue placeholder="Semua Aksi" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua Aksi</SelectItem>
                                {Object.entries(ACTION_LABELS).map(([key, { label }]) => (
                                    <SelectItem key={key} value={key}>{label}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="flex items-center gap-2">
                        <span className="text-sm font-medium text-foreground">Tipe</span>
                        <Select
                            value={filters.subject_type ?? 'all'}
                            onValueChange={(v) => handleFilter('subject_type', v)}
                        >
                            <SelectTrigger className="h-9 w-44">
                                <SelectValue placeholder="Semua Tipe" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua Tipe</SelectItem>
                                {Object.entries(SUBJECT_LABELS).map(([key, label]) => (
                                    <SelectItem key={key} value={key}>{label}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    {hasFilters && (
                        <button
                            type="button"
                            onClick={() => router.visit(index.url(), { preserveScroll: true, preserveState: true })}
                            className="ml-auto text-xs font-medium text-muted-foreground underline underline-offset-2 hover:text-foreground"
                        >
                            Hapus filter
                        </button>
                    )}
                </div>

                {/* ── Log Table ── */}
                <div className="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
                    {logs.data.length === 0 ? (
                        <div className="flex flex-col items-center justify-center py-20 text-center">
                            <span className="text-3xl">📋</span>
                            <p className="mt-2 text-sm font-medium text-muted-foreground">
                                Belum ada aktivitas tercatat
                            </p>
                        </div>
                    ) : (
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-border bg-muted/40">
                                    <th className="py-3 pl-5 pr-4 text-left">
                                        <span className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                                            Waktu
                                        </span>
                                    </th>
                                    <th className="py-3 pr-4 text-left">
                                        <span className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                                            User
                                        </span>
                                    </th>
                                    <th className="py-3 pr-4 text-left">
                                        <span className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                                            Aksi
                                        </span>
                                    </th>
                                    <th className="py-3 pr-4 text-left">
                                        <span className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                                            Tipe
                                        </span>
                                    </th>
                                    <th className="py-3 pr-5 text-left">
                                        <span className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                                            Deskripsi
                                        </span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {logs.data.map((log) => {
                                    const actionMeta = ACTION_LABELS[log.action] ?? {
                                        label: log.action,
                                        color: 'bg-muted text-muted-foreground',
                                    };
                                    return (
                                        <tr
                                            key={log.id}
                                            className="border-b border-border/60 transition-colors last:border-0 hover:bg-muted/20"
                                        >
                                            <td className="py-3.5 pl-5 pr-4">
                                                <span className="font-mono text-xs tabular-nums text-muted-foreground">
                                                    {formatDateTime(log.created_at)}
                                                </span>
                                            </td>
                                            <td className="py-3.5 pr-4">
                                                {log.user ? (
                                                    <div>
                                                        <p className="text-sm font-medium text-foreground">
                                                            {log.user.name}
                                                        </p>
                                                        <p className="text-[10px] capitalize text-muted-foreground">
                                                            {log.user.role.replace('_', ' ')}
                                                        </p>
                                                    </div>
                                                ) : (
                                                    <span className="text-xs italic text-muted-foreground/50">
                                                        System
                                                    </span>
                                                )}
                                            </td>
                                            <td className="py-3.5 pr-4">
                                                <span
                                                    className={`inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider ${actionMeta.color}`}
                                                >
                                                    {actionMeta.label}
                                                </span>
                                            </td>
                                            <td className="py-3.5 pr-4">
                                                <span className="text-xs text-muted-foreground">
                                                    {SUBJECT_LABELS[log.subject_type] ?? log.subject_type}
                                                </span>
                                            </td>
                                            <td className="py-3.5 pr-5">
                                                <p className="text-sm text-foreground">
                                                    {log.description}
                                                </p>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    )}
                </div>

                {/* ── Pagination ── */}
                {logs.last_page > 1 && (
                    <div className="flex items-center justify-between">
                        <p className="text-sm text-muted-foreground">
                            Halaman{' '}
                            <span className="font-semibold text-foreground">{logs.current_page}</span>{' '}
                            dari{' '}
                            <span className="font-semibold text-foreground">{logs.last_page}</span>
                        </p>
                        <div className="flex gap-1.5">
                            {logs.links.map((link, i) => {
                                const isPrev = link.label === '&laquo; Previous';
                                const isNext = link.label === 'Next &raquo;';
                                const label = isPrev ? '← Sebelumnya' : isNext ? 'Berikutnya →' : link.label;
                                return (
                                    <button
                                        key={i}
                                        disabled={!link.url || link.active}
                                        onClick={() => handlePage(link.url)}
                                        className={`rounded-lg px-3 py-1.5 text-sm font-medium transition-all ${
                                            link.active
                                                ? 'bg-primary text-primary-foreground shadow-sm'
                                                : !link.url
                                                  ? 'cursor-not-allowed text-muted-foreground/40'
                                                  : 'text-foreground ring-1 ring-border hover:bg-muted'
                                        }`}
                                    >
                                        {label}
                                    </button>
                                );
                            })}
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}

ActivityLogsIndex.layout = {
    breadcrumbs: [{ title: 'Activity Log', href: index.url() }],
};
