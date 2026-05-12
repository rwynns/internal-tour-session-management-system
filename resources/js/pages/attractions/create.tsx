import { Head, Link, useForm } from '@inertiajs/react';
import { index, store } from '@/actions/App/Http/Controllers/AttractionController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { FormEventHandler } from 'react';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        description: '',
        duration_minutes: '' as unknown as number,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(store.url());
    };

    return (
        <>
            <Head title="Tambah Attraction" />

            <div className="flex min-h-[calc(100vh-8rem)] items-start justify-center p-4 lg:p-8">
                <div className="w-full max-w-2xl">
                    {/* ── Back link ── */}
                    <Link
                        href={index.url()}
                        className="mb-6 inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
                    >
                        <span className="text-base leading-none">←</span>
                        Kembali ke Attractions
                    </Link>

                    {/* ── Card ── */}
                    <div className="overflow-hidden rounded-2xl border border-border bg-card shadow-md">
                        {/* Decorative header strip */}
                        <div className="relative overflow-hidden bg-primary px-8 py-7">
                            <div className="absolute -right-8 -top-8 h-32 w-32 rounded-full bg-white/10" />
                            <div className="absolute -bottom-6 right-16 h-20 w-20 rounded-full bg-white/5" />
                            <div className="relative">
                                <div className="mb-1 flex items-center gap-2">
                                    <span className="rounded-md bg-white/20 px-2 py-0.5 text-xs font-semibold uppercase tracking-wider text-white/90">
                                        Baru
                                    </span>
                                </div>
                                <h1 className="text-2xl font-bold tracking-tight text-white">
                                    Tambah Attraction
                                </h1>
                                <p className="mt-1 text-sm text-white/70">
                                    Tambahkan tur atau aktivitas baru ke venue.
                                </p>
                            </div>
                        </div>

                        {/* Form body */}
                        <form onSubmit={submit} className="space-y-6 p-8">
                            {/* Name */}
                            <div className="space-y-2">
                                <Label
                                    htmlFor="name"
                                    className="text-sm font-semibold text-foreground"
                                >
                                    Nama Attraction{' '}
                                    <span className="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="name"
                                    type="text"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    required
                                    placeholder="cth. Museum Tour, Batik Workshop…"
                                    className={`h-10 w-full transition-shadow focus:shadow-sm ${errors.name ? 'border-destructive focus:ring-destructive/30' : ''}`}
                                    autoFocus
                                />
                                <InputError message={errors.name} />
                            </div>

                            {/* Description */}
                            <div className="space-y-2">
                                <Label
                                    htmlFor="description"
                                    className="text-sm font-semibold text-foreground"
                                >
                                    Deskripsi{' '}
                                    <span className="text-xs font-normal text-muted-foreground">
                                        (opsional)
                                    </span>
                                </Label>
                                <Textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    placeholder="Jelaskan secara singkat pengalaman yang akan didapat tamu…"
                                    rows={3}
                                    className="w-full resize-none"
                                />
                                <InputError message={errors.description} />
                            </div>

                            {/* Duration */}
                            <div className="space-y-2">
                                <Label
                                    htmlFor="duration_minutes"
                                    className="text-sm font-semibold text-foreground"
                                >
                                    Durasi{' '}
                                    <span className="text-destructive">*</span>
                                </Label>
                                <div className="relative">
                                    <Input
                                        id="duration_minutes"
                                        type="number"
                                        min={1}
                                        value={data.duration_minutes}
                                        onChange={(e) =>
                                            setData(
                                                'duration_minutes',
                                                parseInt(e.target.value, 10),
                                            )
                                        }
                                        required
                                        placeholder="60"
                                        className={`h-10 w-full pr-16 ${errors.duration_minutes ? 'border-destructive focus:ring-destructive/30' : ''}`}
                                    />
                                    <span className="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-sm font-medium text-muted-foreground">
                                        menit
                                    </span>
                                </div>
                                <InputError message={errors.duration_minutes} />
                            </div>

                            {/* Divider */}
                            <div className="border-t border-border" />

                            {/* Actions */}
                            <div className="flex items-center justify-between">
                                <Link
                                    href={index.url()}
                                    className="text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
                                >
                                    Batal
                                </Link>
                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className="min-w-[160px] gap-2"
                                >
                                    {processing ? (
                                        <>
                                            <span className="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-current border-t-transparent" />
                                            Menyimpan…
                                        </>
                                    ) : (
                                        'Simpan Attraction'
                                    )}
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </>
    );
}

Create.layout = {
    breadcrumbs: [
        { title: 'Attractions', href: index.url() },
        { title: 'Tambah', href: '#' },
    ],
};
