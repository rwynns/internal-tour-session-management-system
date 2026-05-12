import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { AllocationModal } from '@/components/cashier/allocation-modal';
import { AllocationsList } from '@/components/cashier/allocations-list';
import { MoveModal } from '@/components/cashier/move-modal';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { dashboard } from '@/routes';
import type { Attraction, SessionWithAllocations } from '@/types';

type Filters = {
    attraction_id?: string;
    search?: string;
};

type Props = {
    sessions: SessionWithAllocations[];
    attractions: Attraction[];
    filters: Filters;
};

export default function CashierDashboard({ sessions, attractions, filters }: Props) {
    const [allocationSession, setAllocationSession] = useState<SessionWithAllocations | null>(null);
    const [moveSession, setMoveSession] = useState<SessionWithAllocations | null>(null);
    const [searchInput, setSearchInput] = useState(filters.search ?? '');

    const now = new Date();
    const activeSessions = sessions.filter(
        (s) => s.status === 'active' && new Date(s.start_time) >= now,
    );
    const otherSessions = sessions.filter(
        (s) => s.status !== 'active' || new Date(s.start_time) < now,
    );

    const totalPax = activeSessions.reduce((sum, s) => sum + s.current_pax, 0);
    const totalCapacity = activeSessions.reduce((sum, s) => sum + s.max_capacity, 0);
    const fullSessions = activeSessions.filter((s) => s.current_pax >= s.max_capacity).length;

    const hasFilters = filters.attraction_id || filters.search;

    function applyFilter(key: string, value: string) {
        router.visit(
            dashboard({ query: { ...filters, [key]: value === 'all' || value === '' ? undefined : value } }).url,
            { preserveScroll: true, preserveState: true },
        );
    }

    function handleSearchSubmit(e: React.FormEvent) {
        e.preventDefault();
        applyFilter('search', searchInput);
    }

    function clearFilters() {
        setSearchInput('');
        router.visit(dashboard().url, { preserveScroll: true, preserveState: true });
    }

    return (
        <>
            <Head title="Session Dashboard" />

            <style>{`
                @keyframes slideUp {
                    from { opacity: 0; transform: translateY(14px); }
                    to   { opacity: 1; transform: translateY(0); }
                }
                .card-enter {
                    animation: slideUp 0.38s cubic-bezier(0.16, 1, 0.3, 1) both;
                }

                @keyframes fadeInUp {
                    from { opacity: 0; transform: translateY(6px); }
                    to   { opacity: 1; transform: translateY(0); }
                }
                .stat-enter {
                    animation: fadeInUp 0.45s cubic-bezier(0.16, 1, 0.3, 1) both;
                }

                @keyframes fillBar {
                    from { width: 0%; }
                }
                .fill-bar {
                    animation: fillBar 0.7s cubic-bezier(0.16, 1, 0.3, 1) both;
                }

                @keyframes pulseDot {
                    0%, 100% { opacity: 1; }
                    50%       { opacity: 0.35; }
                }
                .pulse-dot {
                    animation: pulseDot 2.2s ease-in-out infinite;
                }

                .session-card {
                    transition: transform 0.18s cubic-bezier(0.16, 1, 0.3, 1),
                                box-shadow 0.18s cubic-bezier(0.16, 1, 0.3, 1);
                }
                .session-card:hover {
                    transform: translateY(-2px);
                    box-shadow: var(--shadow-md);
                }
            `}</style>

            <div className="flex flex-col gap-0 p-4 lg:p-6">

                {/* ── Header ── */}
                <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <div className="mb-1.5 flex items-center gap-2">
                            <span
                                className="pulse-dot h-1.5 w-1.5 rounded-full bg-primary"
                            />
                            <span className="text-xs font-semibold uppercase tracking-[0.15em] text-primary">
                                Live · Cashier
                            </span>
                        </div>
                        <h1 className="text-3xl font-bold tracking-tight text-foreground">
                            Session Board
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Kelola alokasi tamu untuk sesi hari ini dan ke depan.
                        </p>
                    </div>

                    {/* ── Stats strip ── */}
                    {sessions.length > 0 && (
                        <div className="flex items-stretch gap-px overflow-hidden rounded-xl border border-border bg-border shadow-sm">
                            {[
                                { label: 'Active', value: activeSessions.length, delay: '0ms', accent: false },
                                { label: 'Guests In', value: totalPax, delay: '50ms', accent: false },
                                {
                                    label: 'Fill Rate',
                                    value: totalCapacity > 0 ? `${Math.round((totalPax / totalCapacity) * 100)}%` : '—',
                                    delay: '100ms',
                                    accent: totalCapacity > 0 && totalPax / totalCapacity > 0.8,
                                },
                                {
                                    label: 'Full',
                                    value: fullSessions,
                                    delay: '150ms',
                                    accent: fullSessions > 0,
                                },
                            ].map((stat) => (
                                <div
                                    key={stat.label}
                                    className="stat-enter flex flex-col items-center justify-center bg-card px-5 py-3"
                                    style={{ animationDelay: stat.delay }}
                                >
                                    <span
                                        className="font-mono text-2xl font-medium tabular-nums leading-none"
                                        style={{ color: stat.accent ? 'var(--accent)' : undefined }}
                                    >
                                        {stat.value}
                                    </span>
                                    <span className="mt-1 text-[10px] font-semibold uppercase tracking-widest text-muted-foreground">
                                        {stat.label}
                                    </span>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                {/* ── Filter Bar ── */}
                <div className="mb-6 flex flex-wrap items-center gap-3 rounded-xl border border-border bg-card px-4 py-3 shadow-sm">
                    <span className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                        Filter
                    </span>
                    <div className="h-4 w-px bg-border" />

                    {/* Attraction filter */}
                    <Select
                        value={filters.attraction_id ?? 'all'}
                        onValueChange={(v) => applyFilter('attraction_id', v)}
                    >
                        <SelectTrigger className="h-9 w-48">
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

                    {/* Guest name search */}
                    <form onSubmit={handleSearchSubmit} className="flex items-center gap-2">
                        <input
                            type="text"
                            value={searchInput}
                            onChange={(e) => setSearchInput(e.target.value)}
                            placeholder="Cari nama tamu…"
                            className="h-9 rounded-md border border-border bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/40"
                        />
                        <button
                            type="submit"
                            className="h-9 rounded-md bg-primary px-3 text-xs font-semibold text-primary-foreground hover:opacity-90"
                        >
                            Cari
                        </button>
                    </form>

                    {hasFilters && (
                        <button
                            type="button"
                            onClick={clearFilters}
                            className="ml-auto text-xs font-medium text-muted-foreground underline underline-offset-2 hover:text-foreground"
                        >
                            Hapus filter
                        </button>
                    )}
                </div>

                {/* ── Empty state ── */}
                {sessions.length === 0 && (
                    <div className="flex flex-col items-center justify-center rounded-2xl border border-dashed border-border bg-muted/30 py-28 text-center">
                        <span className="font-mono text-5xl font-medium tabular-nums text-muted-foreground/30">
                            00:00
                        </span>
                        <p className="mt-4 text-base font-semibold text-muted-foreground">
                            No Upcoming Sessions
                        </p>
                        <p className="mt-1 text-sm text-muted-foreground/60">
                            Sesi baru akan muncul di sini setelah dijadwalkan.
                        </p>
                    </div>
                )}

                {/* ── Active / upcoming sessions ── */}
                {activeSessions.length > 0 && (
                    <section className="mb-8">
                        <SectionLabel
                            label="Active & Upcoming"
                            count={activeSessions.length}
                            accent
                        />
                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            {activeSessions.map((session, i) => (
                                <TicketCard
                                    key={session.id}
                                    session={session}
                                    index={i}
                                    onAllocate={() => setAllocationSession(session)}
                                    onMove={() => setMoveSession(session)}
                                />
                            ))}
                        </div>
                    </section>
                )}

                {/* ── Inactive / past sessions ── */}
                {otherSessions.length > 0 && (
                    <section>
                        <SectionLabel
                            label="Inactive / Past"
                            count={otherSessions.length}
                        />
                        <div className="grid grid-cols-1 gap-3 opacity-50 sm:grid-cols-2 lg:grid-cols-3">
                            {otherSessions.map((session, i) => (
                                <TicketCard
                                    key={session.id}
                                    session={session}
                                    index={i}
                                    onAllocate={() => setAllocationSession(session)}
                                    onMove={() => setMoveSession(session)}
                                    muted
                                />
                            ))}
                        </div>
                    </section>
                )}
            </div>

            <AllocationModal
                session={allocationSession}
                onClose={() => setAllocationSession(null)}
            />
            <MoveModal
                session={moveSession}
                sessions={sessions}
                onClose={() => setMoveSession(null)}
            />
        </>
    );
}

/* ── Section label ── */
function SectionLabel({
    label,
    count,
    accent = false,
}: {
    label: string;
    count: number;
    accent?: boolean;
}) {
    return (
        <div className="mb-3 flex items-center gap-3">
            <span
                className="text-[10px] font-semibold uppercase tracking-[0.18em]"
                style={{ color: accent ? 'var(--primary)' : 'var(--muted-foreground)' }}
            >
                {label}
            </span>
            <div className="h-px flex-1 bg-border/60" />
            <span className="font-mono text-[10px] text-muted-foreground/60">
                {count} session{count !== 1 ? 's' : ''}
            </span>
        </div>
    );
}

/* ── Ticket card ── */
type TicketCardProps = {
    session: SessionWithAllocations;
    index: number;
    onAllocate: () => void;
    onMove: () => void;
    muted?: boolean;
};

function TicketCard({ session, index, onAllocate, onMove, muted = false }: TicketCardProps) {
    const [showGuests, setShowGuests] = useState(false);

    const isPast = new Date(session.start_time) < new Date();
    const isInactive = session.status === 'inactive';
    const pct =
        session.max_capacity > 0
            ? Math.min((session.current_pax / session.max_capacity) * 100, 100)
            : 0;
    const isFull = session.current_pax >= session.max_capacity;
    const isNearFull = pct >= 80 && !isFull;
    const isDisabled = isPast || isInactive || muted;
    const isAllocateDisabled = isDisabled || isFull;

    const fmt = (d: string) =>
        new Date(d).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', timeZone: 'Asia/Jakarta' });

    const fmtDate = (d: string) =>
        new Date(d).toLocaleDateString('id-ID', {
            weekday: 'short',
            day: 'numeric',
            month: 'short',
            timeZone: 'Asia/Jakarta',
        });

    // Use CSS vars so it respects dark mode
    const accentColor = isFull
        ? 'var(--destructive)'
        : isNearFull
          ? 'var(--accent)'
          : 'var(--primary)';

    const borderStyle = isFull
        ? { borderColor: 'color-mix(in oklch, var(--destructive) 40%, var(--border))' }
        : isNearFull
          ? { borderColor: 'color-mix(in oklch, var(--accent) 40%, var(--border))' }
          : {};

    return (
        <div
            className="card-enter session-card relative overflow-hidden rounded-xl border border-border bg-card"
            style={{
                animationDelay: `${index * 50}ms`,
                ...borderStyle,
            }}
        >
            {/* Top accent line */}
            <div
                className="h-0.5 w-full"
                style={{ background: accentColor, opacity: isDisabled ? 0.3 : 1 }}
            />

            <div className="p-4">
                {/* Row 1: time + badges */}
                <div className="mb-3 flex items-start justify-between gap-2">
                    <div className="flex items-baseline gap-1.5">
                        <span className="font-mono text-3xl font-medium tabular-nums leading-none text-foreground">
                            {fmt(session.start_time)}
                        </span>
                        <span className="font-mono text-sm text-muted-foreground">
                            – {fmt(session.end_time)}
                        </span>
                    </div>

                    <div className="flex shrink-0 flex-col items-end gap-1">
                        {isInactive && <StatusBadge label="Inactive" />}
                        {isPast && !isInactive && <StatusBadge label="Past" />}
                        {isFull && <StatusBadge label="Full" color="destructive" />}
                        {isNearFull && <StatusBadge label="Almost Full" color="accent" />}
                    </div>
                </div>

                {/* Row 2: attraction + date */}
                <div className="mb-4">
                    <p className="truncate text-base font-semibold leading-tight text-foreground">
                        {session.attraction?.name ?? 'Unknown Attraction'}
                    </p>
                    <p className="mt-0.5 text-xs text-muted-foreground">
                        {fmtDate(session.start_time)}
                    </p>
                </div>

                {/* Dashed divider */}
                <div className="mb-4 border-t border-dashed border-border/70" />

                {/* Occupancy */}
                <div className="mb-4">
                    <div className="mb-1.5 flex items-center justify-between">
                        <span className="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground">
                            Occupancy
                        </span>
                        <span
                            className="font-mono text-sm font-medium tabular-nums"
                            style={{ color: isFull ? 'var(--destructive)' : isNearFull ? 'var(--accent)' : 'var(--foreground)' }}
                        >
                            {session.current_pax}
                            <span className="text-muted-foreground">/</span>
                            {session.max_capacity}
                        </span>
                    </div>

                    <div className="relative h-1.5 w-full overflow-hidden rounded-full bg-muted">
                        <div
                            className="fill-bar absolute inset-y-0 left-0 rounded-full"
                            style={{
                                width: `${pct}%`,
                                background: accentColor,
                                animationDelay: `${index * 50 + 180}ms`,
                            }}
                            role="progressbar"
                            aria-valuenow={session.current_pax}
                            aria-valuemin={0}
                            aria-valuemax={session.max_capacity}
                            aria-label={`Occupancy: ${session.current_pax} of ${session.max_capacity}`}
                        />
                    </div>

                    {!isFull && !isAllocateDisabled && (
                        <p className="mt-1 text-[10px] text-muted-foreground/60">
                            {session.max_capacity - session.current_pax} seat
                            {session.max_capacity - session.current_pax !== 1 ? 's' : ''} remaining
                        </p>
                    )}
                </div>

                {/* Actions */}
                <div className="flex gap-2">
                    <button
                        type="button"
                        disabled={isAllocateDisabled}
                        onClick={onAllocate}
                        className="flex-1 rounded-lg bg-primary px-3 py-2 text-xs font-semibold uppercase tracking-wide text-primary-foreground transition-all hover:opacity-90 active:scale-[0.97] disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        + Allocate
                    </button>
                    <button
                        type="button"
                        disabled={isDisabled}
                        onClick={onMove}
                        className="flex-1 rounded-lg border border-border bg-transparent px-3 py-2 text-xs font-semibold uppercase tracking-wide text-foreground transition-all hover:bg-muted active:scale-[0.97] disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        ⇄ Move
                    </button>
                </div>

                {/* Guest list toggle */}
                {session.current_pax > 0 && (
                    <>
                        <button
                            type="button"
                            onClick={() => setShowGuests((v) => !v)}
                            className="mt-3 flex w-full items-center justify-between rounded-lg border border-dashed border-border/70 px-3 py-2 text-xs text-muted-foreground transition-colors hover:border-border hover:bg-muted/40 hover:text-foreground"
                        >
                            <span className="font-medium">
                                {showGuests ? 'Hide' : 'Show'} guests
                            </span>
                            <span className="font-mono tabular-nums">
                                {session.active_allocations?.length ?? session.current_pax} active
                                <span className="ml-1.5 opacity-50">{showGuests ? '▲' : '▼'}</span>
                            </span>
                        </button>

                        {showGuests && (
                            <div className="mt-2 rounded-lg border border-border/60 bg-background/60 px-3 py-1">
                                <AllocationsList allocations={session.active_allocations ?? []} />
                            </div>
                        )}
                    </>
                )}
            </div>
        </div>
    );
}

/* ── Status badge ── */
function StatusBadge({
    label,
    color = 'muted',
}: {
    label: string;
    color?: 'muted' | 'destructive' | 'accent';
}) {
    const styles: Record<string, React.CSSProperties> = {
        muted: {
            background: 'var(--muted)',
            color: 'var(--muted-foreground)',
            border: '1px solid var(--border)',
        },
        destructive: {
            background: 'color-mix(in oklch, var(--destructive) 12%, transparent)',
            color: 'var(--destructive)',
            border: '1px solid color-mix(in oklch, var(--destructive) 30%, transparent)',
        },
        accent: {
            background: 'color-mix(in oklch, var(--accent) 12%, transparent)',
            color: 'var(--accent)',
            border: '1px solid color-mix(in oklch, var(--accent) 30%, transparent)',
        },
    };

    return (
        <span
            className="rounded px-1.5 py-0.5 text-[9px] font-semibold uppercase tracking-widest"
            style={styles[color]}
        >
            {label}
        </span>
    );
}

CashierDashboard.layout = {
    breadcrumbs: [{ title: 'Dashboard', href: dashboard().url }],
};
