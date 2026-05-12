import { Head, Link, router, useForm } from '@inertiajs/react';
import { toast } from 'sonner';
import {
    index,
    create,
    edit,
    destroy,
    toggleActive,
} from '@/actions/App/Http/Controllers/AttractionController';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { Attraction, PaginatedResponse } from '@/types';

type SortDirection = 'asc' | 'desc';

type Props = {
    attractions: PaginatedResponse<Attraction>;
    sort?: string;
    direction?: SortDirection;
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

function DeleteButton({ attraction }: { attraction: Attraction }) {
    const { delete: deleteAttr, processing } = useForm({});

    function handleDelete() {
        toast('Hapus Attraction ini?', {
            description: `"${attraction.name}" akan dihapus secara permanen.`,
            action: {
                label: 'Ya, Hapus',
                onClick: () => {
                    deleteAttr(destroy.url(attraction.id), { preserveScroll: true });
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

/** Animated toggle switch for active/inactive status */
function ActiveToggle({ attraction }: { attraction: Attraction }) {
    const { patch, processing } = useForm({});

    function handleToggle() {
        const willActivate = !attraction.is_active;
        const action = willActivate ? 'mengaktifkan' : 'menonaktifkan';

        toast(`${willActivate ? 'Aktifkan' : 'Nonaktifkan'} Attraction ini?`, {
            description: `Anda akan ${action} "${attraction.name}".`,
            action: {
                label: 'Ya, Lanjutkan',
                onClick: () => {
                    patch(toggleActive.url(attraction.id), { preserveScroll: true });
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
            aria-checked={attraction.is_active}
            disabled={processing}
            onClick={handleToggle}
            title={attraction.is_active ? 'Klik untuk menonaktifkan' : 'Klik untuk mengaktifkan'}
            className={`relative inline-flex h-5 w-9 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 ${
                attraction.is_active ? 'bg-emerald-500' : 'bg-muted-foreground/30'
            }`}
        >
            <span
                className={`pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-sm ring-0 transition-transform duration-200 ease-in-out ${
                    attraction.is_active ? 'translate-x-4' : 'translate-x-0'
                } ${processing ? 'opacity-60' : ''}`}
            />
        </button>
    );
}

export default function AttractionsIndex({ attractions, sort, direction }: Props) {
    function handleSort(column: string, dir: SortDirection) {
        router.visit(index.url({ query: { sort: column, direction: dir } }), {
            preserveScroll: true,
            preserveState: true,
        });
    }

    function handlePage(url: string | null) {
        if (!url) return;
        router.visit(url, { preserveScroll: true, preserveState: true });
    }

    // Offset for numbering across pages
    const pageOffset = (attractions.current_page - 1) * attractions.per_page;

    return (
        <>
            <Head title="Attractions" />

            <div className="flex flex-col gap-6 p-4 lg:p-6">
                {/* ── Page Header ── */}
                <div className="flex items-end justify-between">
                    <div className="flex items-end gap-4">
                        <div>
                            <p className="mb-0.5 text-xs font-semibold uppercase tracking-widest text-primary/70">
                                Recreation Admin
                            </p>
                            <h1 className="text-3xl font-bold tracking-tight text-foreground">
                                Attractions
                            </h1>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Kelola daftar atraksi dan aktivitas yang tersedia di venue.
                            </p>
                        </div>
                        <span className="mb-1 inline-flex items-center rounded-full bg-primary/10 px-3 py-0.5 text-sm font-semibold text-primary ring-1 ring-primary/20">
                            {attractions.total}{' '}
                            {attractions.total === 1 ? 'attraction' : 'attractions'}
                        </span>
                    </div>
                    <Button asChild className="gap-2 shadow-sm">
                        <Link href={create.url()}>
                            <span className="text-base leading-none">+</span>
                            Tambah Attraction
                        </Link>
                    </Button>
                </div>

                {/* ── Table Card ── */}
                <div className="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
                    <Table>
                        <TableHeader>
                            <TableRow className="border-b border-border bg-muted/40 hover:bg-muted/40">
                                <TableHead className="w-12 py-3 pl-5 text-center">
                                    <span className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                                        No
                                    </span>
                                </TableHead>
                                <TableHead className="py-3">
                                    <SortableHeader
                                        column="name"
                                        label="Nama"
                                        currentSort={sort}
                                        currentDirection={direction}
                                        onSort={handleSort}
                                    />
                                </TableHead>
                                <TableHead className="py-3">
                                    <span className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                                        Deskripsi
                                    </span>
                                </TableHead>
                                <TableHead className="py-3">
                                    <SortableHeader
                                        column="duration_minutes"
                                        label="Durasi"
                                        currentSort={sort}
                                        currentDirection={direction}
                                        onSort={handleSort}
                                    />
                                </TableHead>
                                <TableHead className="py-3">
                                    <span className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                                        Status
                                    </span>
                                </TableHead>
                                <TableHead className="py-3 pr-5 text-right">
                                    <span className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                                        Aksi
                                    </span>
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {attractions.data.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={6} className="py-16 text-center">
                                        <div className="flex flex-col items-center gap-2">
                                            <span className="text-3xl">🗺️</span>
                                            <p className="text-sm font-medium text-muted-foreground">
                                                Belum ada attraction
                                            </p>
                                            <p className="text-xs text-muted-foreground/60">
                                                Tambahkan attraction pertama untuk memulai.
                                            </p>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ) : (
                                attractions.data.map((attraction, idx) => (
                                    <TableRow
                                        key={attraction.id}
                                        className="group border-b border-border/60 transition-colors last:border-0 hover:bg-muted/30"
                                    >
                                        {/* Row number */}
                                        <TableCell className="w-12 py-3.5 pl-5 text-center">
                                            <span className="inline-flex h-6 w-6 items-center justify-center rounded-md bg-muted text-xs font-semibold tabular-nums text-muted-foreground">
                                                {pageOffset + idx + 1}
                                            </span>
                                        </TableCell>

                                        <TableCell className="py-3.5">
                                            <span className="font-semibold text-foreground">
                                                {attraction.name}
                                            </span>
                                        </TableCell>

                                        <TableCell className="max-w-xs py-3.5">
                                            <span className="line-clamp-1 text-sm text-muted-foreground">
                                                {attraction.description ?? (
                                                    <span className="italic opacity-40">
                                                        Tidak ada deskripsi
                                                    </span>
                                                )}
                                            </span>
                                        </TableCell>

                                        <TableCell className="py-3.5">
                                            <span className="inline-flex items-center gap-1 text-sm">
                                                <span className="font-semibold text-foreground">
                                                    {attraction.duration_minutes}
                                                </span>
                                                <span className="text-xs text-muted-foreground">
                                                    menit
                                                </span>
                                            </span>
                                        </TableCell>

                                        {/* Status column: toggle switch + label */}
                                        <TableCell className="py-3.5">
                                            <div className="flex items-center gap-2.5">
                                                <ActiveToggle attraction={attraction} />
                                                <span
                                                    className={`text-xs font-medium ${
                                                        attraction.is_active
                                                            ? 'text-emerald-600 dark:text-emerald-400'
                                                            : 'text-muted-foreground'
                                                    }`}
                                                >
                                                    {attraction.is_active ? 'Aktif' : 'Nonaktif'}
                                                </span>
                                            </div>
                                        </TableCell>

                                        <TableCell className="py-3.5 pr-5">
                                            <div className="flex items-center justify-end gap-2 opacity-70 transition-opacity group-hover:opacity-100">
                                                <Link
                                                    href={edit.url(attraction.id)}
                                                    className="rounded px-2.5 py-1 text-xs font-medium text-foreground ring-1 ring-border transition-all hover:bg-muted"
                                                >
                                                    Edit
                                                </Link>
                                                <DeleteButton attraction={attraction} />
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </div>

                {/* ── Pagination ── */}
                {attractions.last_page > 1 && (
                    <div className="flex items-center justify-between">
                        <p className="text-sm text-muted-foreground">
                            Halaman{' '}
                            <span className="font-semibold text-foreground">
                                {attractions.current_page}
                            </span>{' '}
                            dari{' '}
                            <span className="font-semibold text-foreground">
                                {attractions.last_page}
                            </span>
                        </p>
                        <div className="flex gap-1.5">
                            {attractions.links.map((link, i) => {
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

AttractionsIndex.layout = {
    breadcrumbs: [{ title: 'Attractions', href: index.url() }],
};
