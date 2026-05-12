import { Head, Link, router, useForm } from '@inertiajs/react';
import React, { useState } from 'react';
import { toast } from 'sonner';
import {
    index,
    create,
    edit,
    destroy,
    updateStatus,
} from '@/actions/App/Http/Controllers/SessionController';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { Attraction, GuestAllocation, PaginatedResponse, Session, SessionStatus } from '@/types';

type SortDirection = 'asc' | 'desc';

type Filters = {
    attraction_id?: string;
    sort?: string;
    direction?: SortDirection;
};

type Props = {
    sessions: PaginatedResponse<Session>;
    attractions: Attraction[];
    filters: Filters;
};

function SortableHeader({
    column,
    label,
    currentSort,
    currentDirection,
    onSort,
}: {
    column: string;
    label: string;
    currentSort?: string;
    currentDirection?: SortDirection;
    onSort: (column: string, direction: SortDirection) => void;
}) {
    const isActive = currentSort === column;
    const nextDirection: SortDirection = isActive && currentDirection === 'asc' ? 'desc' : 'asc';

    return (
        <button
            type="button"
            onClick={() => onSort(column, nextDirection)}
            className="group flex items-center gap-1.5 font-semibold text-xs uppercase tracking-wider transition-colors hover:text-primary"
        >
            {label}
            <span
                className={`text-[10px] transition-all ${
                    isActive
                        ? 'text-primary'
                        : 'text-muted-foreground/50 group-hover:text-muted-foreground'
                }`}
            >
                {isActive ? (currentDirection === 'asc' ? '▲' : '▼') : '⇅'}
            </span>
        </button>
    );
}

/** Toggle switch untuk active/inactive — sama persis dengan Attraction */
function SessionToggle({ session }: { session: Session }) {
    const [processing, setProcessing] = React.useState(false);
    const isActive = session.status === 'active';

    function handleToggle() {
        const willActivate = !isActive;
        const action = willActivate ? 'mengaktifkan' : 'menonaktifkan';
        const newStatus: SessionStatus = willActivate ? 'active' : 'inactive';

        toast(`${willActivate ? 'Aktifkan' : 'Nonaktifkan'} Session ini?`, {
            description: `Anda akan ${action} session "${session.attraction?.name ?? ''}" ini.`,
            action: {
                label: 'Ya, Lanjutkan',
                onClick: () => {
                    setProcessing(true);
                    router.patch(
                        updateStatus.url(session.id),
                        { status: newStatus },
                        {
                            preserveScroll: true,
                            onFinish: () => setProcessing(false),
                        },
                    );
                },
            },
            cancel: {
                label: 'Batal',
                onClick: () => {},
            },
            duration: 8000,
        });
    }

    return (
        <button
            type="button"
            role="switch"
            aria-checked={isActive}
            disabled={processing}
            onClick={handleToggle}
            title={isActive ? 'Klik untuk menonaktifkan' : 'Klik untuk mengaktifkan'}
            className={`relative inline-flex h-5 w-9 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 ${
                isActive ? 'bg-emerald-500' : 'bg-muted-foreground/30'
            }`}
        >
            <span
                className={`pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-sm ring-0 transition-transform duration-200 ease-in-out ${
                    isActive ? 'translate-x-4' : 'translate-x-0'
                } ${processing ? 'opacity-60' : ''}`}
            />
        </button>
    );
}

function DeleteButton({ session }: { session: Session }) {
    const { delete: deleteSession, processing } = useForm({});

    function handleDelete() {
        toast('Hapus Session ini?', {
            description: 'Session akan dihapus secara permanen dan tidak dapat dikembalikan.',
            action: {
                label: 'Ya, Hapus',
                onClick: () => {
                    deleteSession(destroy.url(session.id), { preserveScroll: true });
                },
            },
            cancel: {
                label: 'Batal',
                onClick: () => {},
            },
            duration: 8000,
        });
    }

    return (
        <button
            type="button"
            disabled={processing}
            onClick={handleDelete}
            className="rounded px-2.5 py-1 text-xs font-medium text-destructive ring-1 ring-destructive/30 transition-all hover:bg-destructive hover:text-destructive-foreground disabled:opacity-40"
        >
            {processing ? '…' : 'Hapus'}
        </button>
    );
}

function OccupancyBar({ current, max }: { current: number; max: number }) {
    return (
        <span className="text-sm text-foreground tabular-nums">
            {current}
            <span className="mx-0.5 text-muted-foreground">/</span>
            {max}
        </span>
    );
}

function formatTime(iso: string) {
    return new Date(iso).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
}

function formatDate(iso: string) {
    return new Date(iso).toLocaleDateString('id-ID', {
        weekday: 'short',
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

export default function SessionsIndex({ sessions, attractions, filters }: Props) {
    const { sort, direction, attraction_id } = filters;
    const [expandedId, setExpandedId] = useState<number | null>(null);

    function handleSort(column: string, dir: SortDirection) {
        router.visit(
            index.url({
                query: {
                    sort: column,
                    direction: dir,
                    ...(attraction_id ? { attraction_id } : {}),
                },
            }),
            { preserveScroll: true, preserveState: true },
        );
    }

    function handlePage(url: string | null) {
        if (!url) return;
        router.visit(url, { preserveScroll: true, preserveState: true });
    }

    const pageOffset = (sessions.current_page - 1) * sessions.per_page;

    return (
        <>
            <Head title="Sessions" />

            <div className="flex flex-col gap-6 p-4 lg:p-6">
                {/* ── Page Header ── */}
                <div className="flex items-end justify-between">
                    <div className="flex items-end gap-4">
                        <div>
                            <p className="mb-0.5 text-xs font-semibold uppercase tracking-widest text-primary/70">
                                Recreation Admin
                            </p>
                            <h1 className="text-3xl font-bold tracking-tight text-foreground">
                                Sessions
                            </h1>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Kelola jadwal dan slot waktu untuk setiap attraction.
                            </p>
                        </div>
                        <span className="mb-1 inline-flex items-center rounded-full bg-primary/10 px-3 py-0.5 text-sm font-semibold text-primary ring-1 ring-primary/20">
                            {sessions.total} {sessions.total === 1 ? 'session' : 'sessions'}
                        </span>
                    </div>
                    <Link
                        href={create.url()}
                        className="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground shadow-sm transition-all hover:opacity-90 active:scale-[0.98]"
                    >
                        <span className="text-base leading-none">+</span>
                        Tambah Session
                    </Link>
                </div>

                {/* ── Filter Bar ── */}
                <div className="flex items-center gap-3 rounded-xl border border-border bg-card px-4 py-3 shadow-sm">
                    <span className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                        Filter
                    </span>
                    <div className="h-4 w-px bg-border" />
                    <span className="text-sm font-medium text-foreground">Attraction</span>
                    <Select
                        value={attraction_id ?? 'all'}
                        onValueChange={(val) => {
                            router.visit(
                                index.url({
                                    query: {
                                        ...(val !== 'all' ? { attraction_id: val } : {}),
                                        ...(sort ? { sort } : {}),
                                        ...(direction ? { direction } : {}),
                                    },
                                }),
                                { preserveScroll: true, preserveState: true },
                            );
                        }}
                    >
                        <SelectTrigger className="h-9 w-52">
                            <SelectValue placeholder="Semua Attraction" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua Attraction</SelectItem>
                            {attractions.map((a) => (
                                <SelectItem key={a.id} value={String(a.id)}>
                                    {a.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    {attraction_id && (
                        <button
                            type="button"
                            onClick={() =>
                                router.visit(index.url(), {
                                    preserveScroll: true,
                                    preserveState: true,
                                })
                            }
                            className="ml-auto text-xs font-medium text-muted-foreground underline underline-offset-2 hover:text-foreground"
                        >
                            Hapus filter
                        </button>
                    )}
                </div>

                {/* ── Table Card ── */}
                <div className="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b border-border bg-muted/40">
                                <th className="w-12 border-r border-border/40 py-3 pl-5 text-center">
                                    <span className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                                        No
                                    </span>
                                </th>
                                <th className="max-w-xs border-r border-border/50 py-3 pl-4 text-left">
                                    <SortableHeader
                                        column="start_time"
                                        label="Session"
                                        currentSort={sort}
                                        currentDirection={direction as SortDirection | undefined}
                                        onSort={handleSort}
                                    />
                                </th>
                                <th className="border-r border-border/50 py-3 pl-4 text-left">
                                    <SortableHeader
                                        column="max_capacity"
                                        label="Kapasitas"
                                        currentSort={sort}
                                        currentDirection={direction as SortDirection | undefined}
                                        onSort={handleSort}
                                    />
                                </th>
                                <th className="w-32 border-r border-border/50 py-3 pl-4 text-left">
                                    <span className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                                        Terisi
                                    </span>
                                </th>
                                <th className="border-r border-border/50 py-3 pl-4 text-left">
                                    <span className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                                        Status
                                    </span>
                                </th>
                                <th className="py-3 pl-4 text-left">
                                    <span className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                                        Aksi
                                    </span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {sessions.data.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="py-16 text-center">
                                        <div className="flex flex-col items-center gap-2">
                                            <span className="text-3xl">📅</span>
                                            <p className="text-sm font-medium text-muted-foreground">
                                                Belum ada session
                                            </p>
                                            <p className="text-xs text-muted-foreground/60">
                                                {attraction_id
                                                    ? 'Coba hapus filter atau buat session baru.'
                                                    : 'Buat session pertama untuk memulai.'}
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            ) : (
                                sessions.data.map((session, idx) => (
                                    <React.Fragment key={session.id}>
                                    <tr
                                        className="group border-b border-border/60 transition-colors last:border-0 hover:bg-muted/30"
                                    >
                                        {/* No */}
                                        <td className="w-12 border-r border-border/40 py-4 pl-5 text-center">
                                            <span className="inline-flex h-6 w-6 items-center justify-center rounded-md bg-muted text-xs font-semibold tabular-nums text-muted-foreground">
                                                {pageOffset + idx + 1}
                                            </span>
                                        </td>

                                        {/* Session identity: waktu + attraction */}
                                        <td className="max-w-xs border-r border-border/40 py-4 pl-4 pr-4">
                                            <div className="flex items-start gap-3">
                                                <div className="flex min-w-[68px] flex-col items-center rounded-lg border border-border bg-muted/50 px-2.5 py-1.5 text-center">
                                                    <span className="text-sm font-medium tabular-nums leading-tight text-foreground">
                                                        {formatTime(session.start_time)}
                                                    </span>
                                                    <div className="my-0.5 h-px w-full bg-border" />
                                                    <span className="text-xs tabular-nums text-muted-foreground">
                                                        {formatTime(session.end_time)}
                                                    </span>
                                                </div>
                                                <div className="flex flex-col justify-center pt-0.5">
                                                    <span className="text-sm font-medium text-foreground leading-snug">
                                                        {session.attraction?.name ?? '—'}
                                                    </span>
                                                    <span className="mt-0.5 text-xs text-muted-foreground">
                                                        {formatDate(session.start_time)}
                                                    </span>
                                                </div>
                                            </div>
                                        </td>

                                        {/* Kapasitas */}
                                        <td className="border-r border-border/40 py-4 pl-4">
                                            <span className="text-sm text-foreground">
                                                {session.max_capacity}
                                            </span>
                                            <span className="ml-1 text-xs text-muted-foreground">
                                                tamu
                                            </span>
                                        </td>

                                        {/* Terisi */}
                                        <td className="w-32 border-r border-border/40 py-4 pl-4">
                                            <OccupancyBar
                                                current={session.current_pax}
                                                max={session.max_capacity}
                                            />
                                        </td>

                                        {/* Status */}
                                        <td className="border-r border-border/40 py-4 pl-4">
                                            <div className="flex items-center gap-2.5">
                                                <SessionToggle session={session} />
                                                <span className="text-sm text-foreground">
                                                    {session.status === 'active' ? 'Aktif' : 'Nonaktif'}
                                                </span>
                                            </div>
                                        </td>

                                        {/* Aksi */}
                                        <td className="py-4 pl-4">
                                            <div className="flex items-center gap-2 opacity-70 transition-opacity group-hover:opacity-100">
                                                <Link
                                                    href={edit.url(session.id)}
                                                    className="rounded px-2.5 py-1 text-xs font-medium text-foreground ring-1 ring-border transition-all hover:bg-muted"
                                                >
                                                    Edit
                                                </Link>
                                                <DeleteButton session={session} />
                                                {session.current_pax > 0 && (
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            setExpandedId(
                                                                expandedId === session.id ? null : session.id,
                                                            )
                                                        }
                                                        className="rounded px-2.5 py-1 text-xs font-medium text-primary ring-1 ring-primary/30 transition-all hover:bg-primary/10"
                                                    >
                                                        {expandedId === session.id
                                                            ? 'Hide guests'
                                                            : `${session.current_pax} guest${session.current_pax !== 1 ? 's' : ''}`}
                                                    </button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>

                                    {/* ── Expandable allocations row ── */}
                                    {expandedId === session.id && (
                                        <tr className="border-b border-border/60 bg-muted/20">
                                            <td colSpan={6} className="px-5 py-3">
                                                <AllocationsDetail
                                                    allocations={session.active_allocations ?? []}
                                                />
                                            </td>
                                        </tr>
                                    )}
                                    </React.Fragment>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {/* ── Pagination ── */}
                {sessions.last_page > 1 && (
                    <div className="flex items-center justify-between">
                        <p className="text-sm text-muted-foreground">
                            Halaman{' '}
                            <span className="font-semibold text-foreground">
                                {sessions.current_page}
                            </span>{' '}
                            dari{' '}
                            <span className="font-semibold text-foreground">
                                {sessions.last_page}
                            </span>
                        </p>
                        <div className="flex gap-1.5">
                            {sessions.links.map((link, i) => {
                                const isPrev = link.label === '&laquo; Previous';
                                const isNext = link.label === 'Next &raquo;';
                                const label = isPrev
                                    ? '← Sebelumnya'
                                    : isNext
                                      ? 'Berikutnya →'
                                      : link.label;
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

/* ── Allocations detail panel (admin view) ── */
function AllocationsDetail({ allocations }: { allocations: GuestAllocation[] }) {
    if (allocations.length === 0) {
        return (
            <p className="text-xs text-muted-foreground">No active allocations for this session.</p>
        );
    }

    return (
        <div>
            <p className="mb-2 text-[10px] font-semibold uppercase tracking-widest text-muted-foreground">
                Active Guests
            </p>
            <div className="flex flex-wrap gap-2">
                {allocations.map((a) => (
                    <div
                        key={a.id}
                        className="flex items-center gap-2 rounded-lg border border-border bg-card px-3 py-1.5 text-xs shadow-sm"
                    >
                        <span className="font-medium text-foreground">{a.guest_name}</span>
                        <span className="rounded-full bg-primary/10 px-1.5 py-0.5 font-mono text-[10px] font-semibold text-primary">
                            {a.pax} pax
                        </span>
                        {a.source && (
                            <span className="text-muted-foreground">{a.source}</span>
                        )}
                    </div>
                ))}
            </div>
        </div>
    );
}

SessionsIndex.layout = {
    breadcrumbs: [{ title: 'Sessions', href: index.url() }],
};
