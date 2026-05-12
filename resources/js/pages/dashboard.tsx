import { Head, Link } from '@inertiajs/react';
import { IconCalendarEvent, IconMapPin, IconUsers, IconAlertCircle } from '@tabler/icons-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { index as attractionsIndex } from '@/routes/attractions';
import { index as sessionsIndex } from '@/routes/sessions';
import { dashboard } from '@/routes';
import type { Session } from '@/types';

type Stats = {
    active_attractions: number;
    sessions_today: number;
    guests_today: number;
    capacity_today: number;
    full_sessions: number;
    allocations_today: number;
};

type Props = {
    stats: Stats;
    todaySessions: Session[];
    upcomingSessions: Session[];
};

export default function Dashboard({ stats, todaySessions, upcomingSessions }: Props) {
    const fillRate =
        stats.capacity_today > 0
            ? Math.round((stats.guests_today / stats.capacity_today) * 100)
            : 0;

    return (
        <>
            <Head title="Dashboard" />

            <div className="flex flex-col gap-6 p-4 lg:p-6">
                {/* ── Page Header ── */}
                <div>
                    <p className="mb-0.5 text-xs font-semibold uppercase tracking-widest text-primary/70">
                        Recreation Admin
                    </p>
                    <h1 className="text-3xl font-bold tracking-tight text-foreground">
                        Dashboard
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Overview operasional hari ini.
                    </p>
                </div>

                {/* ── Summary Cards ── */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        icon={<IconMapPin className="size-5" />}
                        label="Attractions Aktif"
                        value={stats.active_attractions}
                    />
                    <StatCard
                        icon={<IconCalendarEvent className="size-5" />}
                        label="Sesi Hari Ini"
                        value={stats.sessions_today}
                    />
                    <StatCard
                        icon={<IconUsers className="size-5" />}
                        label="Total Tamu"
                        value={stats.guests_today}
                        subtitle={`dari ${stats.capacity_today} kapasitas (${fillRate}%)`}
                    />
                    <StatCard
                        icon={<IconAlertCircle className="size-5" />}
                        label="Sesi Penuh"
                        value={stats.full_sessions}
                        accent={stats.full_sessions > 0}
                    />
                </div>

                {/* ── Today's Sessions Occupancy ── */}
                <div className="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
                    <div className="flex items-center justify-between border-b border-border px-5 py-4">
                        <div>
                            <h2 className="text-base font-semibold text-foreground">
                                Occupancy Sesi Hari Ini
                            </h2>
                            <p className="text-xs text-muted-foreground">
                                {todaySessions.length} sesi terjadwal
                            </p>
                        </div>
                        <Link
                            href={sessionsIndex().url}
                            className="text-xs font-medium text-primary hover:underline"
                        >
                            Lihat Semua →
                        </Link>
                    </div>

                    {todaySessions.length === 0 ? (
                        <div className="flex flex-col items-center justify-center py-16 text-center">
                            <span className="text-3xl">📅</span>
                            <p className="mt-2 text-sm font-medium text-muted-foreground">
                                Tidak ada sesi hari ini
                            </p>
                        </div>
                    ) : (
                        <div className="divide-y divide-border/60">
                            {todaySessions.map((session) => (
                                <SessionRow key={session.id} session={session} />
                            ))}
                        </div>
                    )}
                </div>

                {/* ── Upcoming Sessions ── */}
                {upcomingSessions.length > 0 && (
                    <div className="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
                        <div className="border-b border-border px-5 py-4">
                            <h2 className="text-base font-semibold text-foreground">
                                Sesi Mendatang (Hari Ini)
                            </h2>
                            <p className="text-xs text-muted-foreground">
                                Sesi yang belum dimulai dan masih menerima tamu
                            </p>
                        </div>
                        <div className="divide-y divide-border/60">
                            {upcomingSessions.map((session) => (
                                <SessionRow key={session.id} session={session} />
                            ))}
                        </div>
                    </div>
                )}

                {/* ── Quick Actions ── */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <Link
                        href={attractionsIndex().url}
                        className="group flex items-center gap-4 rounded-xl border border-border bg-card p-5 shadow-sm transition-all hover:border-primary/30 hover:shadow-md"
                    >
                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary transition-colors group-hover:bg-primary/20">
                            <IconMapPin className="size-5" />
                        </div>
                        <div>
                            <p className="font-semibold text-foreground">Kelola Attractions</p>
                            <p className="text-xs text-muted-foreground">
                                Tambah, edit, atau nonaktifkan atraksi
                            </p>
                        </div>
                    </Link>
                    <Link
                        href={sessionsIndex().url}
                        className="group flex items-center gap-4 rounded-xl border border-border bg-card p-5 shadow-sm transition-all hover:border-primary/30 hover:shadow-md"
                    >
                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary transition-colors group-hover:bg-primary/20">
                            <IconCalendarEvent className="size-5" />
                        </div>
                        <div>
                            <p className="font-semibold text-foreground">Kelola Sessions</p>
                            <p className="text-xs text-muted-foreground">
                                Atur jadwal, kapasitas, dan status sesi
                            </p>
                        </div>
                    </Link>
                </div>
            </div>
        </>
    );
}

/* ── Stat Card ── */
function StatCard({
    icon,
    label,
    value,
    subtitle,
    accent = false,
}: {
    icon: React.ReactNode;
    label: string;
    value: number;
    subtitle?: string;
    accent?: boolean;
}) {
    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-2">
                <span className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                    {label}
                </span>
                <span className="text-muted-foreground">{icon}</span>
            </CardHeader>
            <CardContent>
                <p
                    className={`text-3xl font-bold tabular-nums ${
                        accent ? 'text-destructive' : 'text-foreground'
                    }`}
                >
                    {value}
                </p>
                {subtitle && (
                    <p className="mt-1 text-xs text-muted-foreground">{subtitle}</p>
                )}
            </CardContent>
        </Card>
    );
}

/* ── Session Row ── */
function SessionRow({ session }: { session: Session }) {
    const pct =
        session.max_capacity > 0
            ? Math.min((session.current_pax / session.max_capacity) * 100, 100)
            : 0;
    const isFull = session.current_pax >= session.max_capacity;
    const isNearFull = pct >= 75 && !isFull;
    const isPast = new Date(session.start_time) < new Date();
    const isInactive = session.status === 'inactive';

    const fmt = (d: string) =>
        new Date(d).toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });

    const barColor = isFull
        ? 'bg-destructive'
        : isNearFull
          ? 'bg-amber-500'
          : 'bg-primary';

    return (
        <div className={`flex items-center gap-4 px-5 py-3 ${isPast || isInactive ? 'opacity-50' : ''}`}>
            {/* Time */}
            <div className="w-24 shrink-0">
                <span className="font-mono text-sm font-semibold tabular-nums text-foreground">
                    {fmt(session.start_time)}
                </span>
                <span className="mx-1 text-muted-foreground">–</span>
                <span className="font-mono text-sm tabular-nums text-muted-foreground">
                    {fmt(session.end_time)}
                </span>
            </div>

            {/* Attraction name */}
            <div className="min-w-0 flex-1">
                <p className="truncate text-sm font-medium text-foreground">
                    {session.attraction?.name ?? 'Unknown'}
                </p>
            </div>

            {/* Status badges */}
            <div className="flex shrink-0 items-center gap-2">
                {isInactive && (
                    <span className="rounded bg-muted px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">
                        Inactive
                    </span>
                )}
                {isPast && !isInactive && (
                    <span className="rounded bg-muted px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">
                        Selesai
                    </span>
                )}
                {isFull && (
                    <span className="rounded bg-destructive/10 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-destructive">
                        Penuh
                    </span>
                )}
            </div>

            {/* Occupancy bar */}
            <div className="w-32 shrink-0">
                <div className="mb-1 flex items-center justify-between">
                    <span className="font-mono text-xs font-medium tabular-nums text-foreground">
                        {session.current_pax}/{session.max_capacity}
                    </span>
                    <span className="text-[10px] text-muted-foreground">{Math.round(pct)}%</span>
                </div>
                <div className="h-1.5 w-full overflow-hidden rounded-full bg-muted">
                    <div
                        className={`h-full rounded-full transition-all ${barColor}`}
                        style={{ width: `${pct}%` }}
                        role="progressbar"
                        aria-valuenow={session.current_pax}
                        aria-valuemin={0}
                        aria-valuemax={session.max_capacity}
                    />
                </div>
            </div>
        </div>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
