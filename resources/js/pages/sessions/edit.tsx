import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { index, update } from '@/actions/App/Http/Controllers/SessionController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { DateTimePicker } from '@/components/ui/date-time-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { Attraction, Session } from '@/types';
import type { FormEventHandler } from 'react';

interface EditProps {
    session: Session;
    attractions: Attraction[];
}

function addMinutes(datetimeLocal: string, minutes: number): string {
    const d = new Date(datetimeLocal);
    d.setMinutes(d.getMinutes() + minutes);
    const pad = (n: number) => String(n).padStart(2, '0');
    return (
        `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}` +
        `T${pad(d.getHours())}:${pad(d.getMinutes())}`
    );
}

export default function Edit({ session, attractions }: EditProps) {
    const { data, setData, put, processing, errors } = useForm({
        attraction_id: session.attraction_id,
        start_time: session.start_time.slice(0, 16),
        end_time: session.end_time.slice(0, 16),
        max_capacity: session.max_capacity,
    });

    const initialAttraction = attractions.find((a) => a.id === session.attraction_id);
    const [selectedDuration, setSelectedDuration] = useState<number | null>(
        initialAttraction?.duration_minutes ?? null,
    );
    const [endTimeAutoFilled, setEndTimeAutoFilled] = useState(false);

    function handleAttractionChange(val: string) {
        const id = parseInt(val, 10);
        setData('attraction_id', id);

        const attraction = attractions.find((a) => a.id === id);
        const duration = attraction?.duration_minutes ?? null;
        setSelectedDuration(duration);

        if (duration && data.start_time) {
            setData('end_time', addMinutes(data.start_time, duration));
            setEndTimeAutoFilled(true);
        }
    }

    function handleStartTimeChange(val: string) {
        setData('start_time', val);
        if (selectedDuration && val) {
            setData('end_time', addMinutes(val, selectedDuration));
            setEndTimeAutoFilled(true);
        }
    }

    function handleEndTimeChange(val: string) {
        setData('end_time', val);
        setEndTimeAutoFilled(false);
    }

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(update.url(session));
    };

    return (
        <>
            <Head title="Edit Session" />

            <div className="flex min-h-[calc(100vh-8rem)] items-start justify-center p-4 lg:p-8">
                <div className="w-full max-w-2xl">
                    <Link
                        href={index.url()}
                        className="mb-6 inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
                    >
                        <span className="text-base leading-none">←</span>
                        Kembali ke Sessions
                    </Link>

                    <div className="overflow-hidden rounded-2xl border border-border bg-card shadow-md">
                        {/* Header strip */}
                        <div className="relative overflow-hidden bg-accent px-8 py-7">
                            <div className="absolute -right-8 -top-8 h-32 w-32 rounded-full bg-white/10" />
                            <div className="absolute -bottom-6 right-16 h-20 w-20 rounded-full bg-white/5" />
                            <div className="relative">
                                <div className="mb-1">
                                    <span className="rounded-md bg-white/20 px-2 py-0.5 text-xs font-semibold uppercase tracking-wider text-white/90">
                                        Edit
                                    </span>
                                </div>
                                <h1 className="text-2xl font-bold tracking-tight text-white">
                                    {session.attraction?.name ?? 'Session'}
                                </h1>
                                <p className="mt-1 text-sm text-white/70">
                                    Perbarui jadwal dan kapasitas session ini.
                                </p>
                            </div>
                        </div>

                        <form onSubmit={submit} className="space-y-6 p-8">
                            {/* Attraction */}
                            <div className="space-y-2">
                                <Label className="text-sm font-semibold text-foreground">
                                    Attraction <span className="text-destructive">*</span>
                                </Label>
                                <Select
                                    value={String(data.attraction_id)}
                                    onValueChange={handleAttractionChange}
                                    required
                                >
                                    <SelectTrigger
                                        className={`h-10 w-full ${errors.attraction_id ? 'border-destructive' : ''}`}
                                    >
                                        <SelectValue placeholder="Pilih attraction…" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {attractions.map((a) => (
                                            <SelectItem key={a.id} value={String(a.id)}>
                                                {a.name}{' '}
                                                <span className="text-muted-foreground">
                                                    ({a.duration_minutes} menit)
                                                </span>
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {selectedDuration !== null && (
                                    <p className="flex items-center gap-1.5 text-xs text-primary">
                                        <span className="inline-flex h-4 w-4 items-center justify-center rounded-full bg-primary/10 text-[10px] font-bold">
                                            i
                                        </span>
                                        Durasi standar:{' '}
                                        <span className="font-semibold">{selectedDuration} menit</span>.
                                        Ubah waktu mulai untuk recalculate otomatis.
                                    </p>
                                )}
                                <InputError message={errors.attraction_id} />
                            </div>

                            {/* Waktu — side by side */}
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label className="text-sm font-semibold text-foreground">
                                        Waktu Mulai <span className="text-destructive">*</span>
                                    </Label>
                                    <DateTimePicker
                                        value={data.start_time}
                                        onChange={handleStartTimeChange}
                                        placeholder="Pilih tanggal & waktu"
                                        hasError={!!errors.start_time}
                                    />
                                    <InputError message={errors.start_time} />
                                </div>

                                <div className="space-y-2">
                                    <div className="flex items-center justify-between">
                                        <Label className="text-sm font-semibold text-foreground">
                                            Waktu Selesai <span className="text-destructive">*</span>
                                        </Label>
                                        {endTimeAutoFilled && (
                                            <span className="rounded-full bg-primary/10 px-2 py-0.5 text-[10px] font-semibold text-primary">
                                                otomatis
                                            </span>
                                        )}
                                    </div>
                                    <DateTimePicker
                                        value={data.end_time}
                                        onChange={handleEndTimeChange}
                                        placeholder="Pilih tanggal & waktu"
                                        hasError={!!errors.end_time}
                                    />
                                    <p className="text-xs text-muted-foreground">
                                        Bisa diubah manual jika diperlukan.
                                    </p>
                                    <InputError message={errors.end_time} />
                                </div>
                            </div>

                            {/* Kapasitas */}
                            <div className="space-y-2">
                                <Label
                                    htmlFor="max_capacity"
                                    className="text-sm font-semibold text-foreground"
                                >
                                    Kapasitas Maksimal <span className="text-destructive">*</span>
                                </Label>
                                <div className="relative">
                                    <Input
                                        id="max_capacity"
                                        type="number"
                                        min={1}
                                        max={1000}
                                        value={data.max_capacity}
                                        onChange={(e) =>
                                            setData('max_capacity', parseInt(e.target.value, 10))
                                        }
                                        required
                                        placeholder="50"
                                        className={`h-10 w-full pr-16 ${errors.max_capacity ? 'border-destructive' : ''}`}
                                    />
                                    <span className="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-sm font-medium text-muted-foreground">
                                        tamu
                                    </span>
                                </div>
                                <InputError message={errors.max_capacity} />
                            </div>

                            <div className="border-t border-border" />

                            <div className="flex items-center justify-between">
                                <Link
                                    href={index.url()}
                                    className="text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
                                >
                                    Batal
                                </Link>
                                <Button type="submit" disabled={processing} className="min-w-[160px] gap-2">
                                    {processing ? (
                                        <>
                                            <span className="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-current border-t-transparent" />
                                            Menyimpan…
                                        </>
                                    ) : (
                                        'Simpan Perubahan'
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

Edit.layout = {
    breadcrumbs: [
        { title: 'Sessions', href: index.url() },
        { title: 'Edit', href: '#' },
    ],
};
