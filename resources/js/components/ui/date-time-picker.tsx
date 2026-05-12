/**
 * DateTimePicker — Shadcn Calendar + time input
 *
 * Menerima value sebagai string "YYYY-MM-DDTHH:mm" (datetime-local format)
 * dan memanggil onChange dengan format yang sama.
 */
import * as React from 'react';
import { format, parse, isValid } from 'date-fns';
import { id } from 'date-fns/locale';
import { CalendarIcon, ClockIcon } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Input } from '@/components/ui/input';

interface DateTimePickerProps {
    value: string; // "YYYY-MM-DDTHH:mm"
    onChange: (value: string) => void;
    placeholder?: string;
    disabled?: boolean;
    hasError?: boolean;
    id?: string;
}

/** Parse "YYYY-MM-DDTHH:mm" → Date | undefined */
function parseDatetimeLocal(value: string): Date | undefined {
    if (!value) return undefined;
    const d = new Date(value);
    return isValid(d) ? d : undefined;
}

/** Format Date → "YYYY-MM-DDTHH:mm" */
function toDatetimeLocal(date: Date, timeStr?: string): string {
    const pad = (n: number) => String(n).padStart(2, '0');
    const dateStr = `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
    const time = timeStr ?? `${pad(date.getHours())}:${pad(date.getMinutes())}`;
    return `${dateStr}T${time}`;
}

export function DateTimePicker({
    value,
    onChange,
    placeholder = 'Pilih tanggal & waktu',
    disabled = false,
    hasError = false,
    id,
}: DateTimePickerProps) {
    const [open, setOpen] = React.useState(false);

    const selectedDate = parseDatetimeLocal(value);
    const timeStr = value ? value.slice(11, 16) : '00:00';

    function handleDaySelect(day: Date | undefined) {
        if (!day) return;
        onChange(toDatetimeLocal(day, timeStr));
        // Jangan tutup popover agar user bisa langsung atur waktu
    }

    function handleTimeChange(e: React.ChangeEvent<HTMLInputElement>) {
        const newTime = e.target.value;
        if (selectedDate) {
            onChange(toDatetimeLocal(selectedDate, newTime));
        } else {
            // Jika belum ada tanggal, pakai hari ini
            onChange(toDatetimeLocal(new Date(), newTime));
        }
    }

    const displayLabel = selectedDate
        ? `${format(selectedDate, 'dd MMM yyyy', { locale: id })}, ${timeStr}`
        : null;

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    id={id}
                    type="button"
                    variant="outline"
                    disabled={disabled}
                    className={cn(
                        'h-10 w-full justify-start gap-2 text-left font-normal',
                        !displayLabel && 'text-muted-foreground',
                        hasError && 'border-destructive focus-visible:ring-destructive/30',
                    )}
                >
                    <CalendarIcon className="size-4 shrink-0 text-muted-foreground" />
                    <span className="flex-1 truncate">
                        {displayLabel ?? placeholder}
                    </span>
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-auto p-0" align="start">
                <Calendar
                    mode="single"
                    selected={selectedDate}
                    onSelect={handleDaySelect}
                    initialFocus
                />
                {/* Time picker */}
                <div className="border-t border-border px-3 py-3">
                    <div className="flex items-center gap-2">
                        <ClockIcon className="size-4 shrink-0 text-muted-foreground" />
                        <span className="text-sm text-muted-foreground">Waktu</span>
                        <Input
                            type="time"
                            value={timeStr}
                            onChange={handleTimeChange}
                            className="h-8 w-28 text-sm"
                        />
                    </div>
                </div>
            </PopoverContent>
        </Popover>
    );
}
