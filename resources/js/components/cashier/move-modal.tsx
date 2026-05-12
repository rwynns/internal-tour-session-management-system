import { move } from '@/actions/App/Http/Controllers/CashierDashboardController';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { cn } from '@/lib/utils';
import type { GuestAllocation, SessionWithAllocations } from '@/types';
import { useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';

type MoveModalProps = {
    session: SessionWithAllocations | null;
    sessions: SessionWithAllocations[];
    onClose: () => void;
};

export function MoveModal({ session, sessions, onClose }: MoveModalProps) {
    const [selectedAllocation, setSelectedAllocation] = useState<GuestAllocation | null>(null);

    const form = useForm({ target_session_id: '' });

    // Reset state when modal closes (session becomes null)
    useEffect(() => {
        if (!session) {
            form.reset();
            setSelectedAllocation(null);
        }
    }, [session]);

    const activeAllocations = session?.active_allocations?.filter((a) => a.status === 'active') ?? [];

    const eligibleSessions = sessions.filter(
        (s) => s.id !== session?.id && s.status === 'active' && new Date(s.start_time) >= new Date(),
    );

    const formatTime = (dateString: string) =>
        new Date(dateString).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', timeZone: 'Asia/Jakarta' });

    function handleSelectAllocation(allocation: GuestAllocation) {
        setSelectedAllocation(allocation);
        form.reset();
    }

    function handleBack() {
        setSelectedAllocation(null);
        form.reset();
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();

        if (!selectedAllocation) {
            return;
        }

        form.patch(move(selectedAllocation.id).url, {
            onSuccess: () => {
                form.reset();
                setSelectedAllocation(null);
                onClose();
            },
        });
    }

    const isOpen = session !== null;

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Move Guest</DialogTitle>
                    <DialogDescription>
                        {selectedAllocation
                            ? `Select a target session for ${selectedAllocation.guest_name} (${selectedAllocation.pax} pax).`
                            : `Select an allocation from "${session?.attraction?.name ?? 'this session'}" to move.`}
                    </DialogDescription>
                </DialogHeader>

                {/* ── Step 1: Select allocation ── */}
                {!selectedAllocation && (
                    <div className="flex flex-col gap-2">
                        {activeAllocations.length === 0 ? (
                            <p className="py-4 text-center text-sm text-muted-foreground">
                                No active allocations in this session.
                            </p>
                        ) : (
                            <ul className="divide-y divide-border rounded-md border">
                                {activeAllocations.map((allocation) => (
                                    <li key={allocation.id}>
                                        <button
                                            type="button"
                                            className="flex w-full items-center justify-between px-4 py-3 text-left text-sm transition-colors hover:bg-muted/50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                            onClick={() => handleSelectAllocation(allocation)}
                                        >
                                            <div className="flex flex-col gap-0.5">
                                                <span className="font-medium">{allocation.guest_name}</span>
                                                {allocation.source && (
                                                    <span className="text-xs text-muted-foreground">{allocation.source}</span>
                                                )}
                                            </div>
                                            <span className="shrink-0 rounded-full bg-muted px-2 py-0.5 text-xs font-semibold tabular-nums">
                                                {allocation.pax} pax
                                            </span>
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        )}

                        <DialogFooter className="mt-2">
                            <Button type="button" variant="outline" onClick={onClose}>
                                Cancel
                            </Button>
                        </DialogFooter>
                    </div>
                )}

                {/* ── Step 2: Select target session ── */}
                {selectedAllocation && (
                    <form onSubmit={handleSubmit} className="flex flex-col gap-4">
                        {eligibleSessions.length === 0 ? (
                            <p className="py-4 text-center text-sm text-muted-foreground">
                                No eligible sessions available to move to.
                            </p>
                        ) : (
                            <ul className="max-h-72 divide-y divide-border overflow-y-auto rounded-md border">
                                {eligibleSessions.map((targetSession) => {
                                    const isSelected = form.data.target_session_id === String(targetSession.id);
                                    const isFull = targetSession.current_pax + selectedAllocation.pax > targetSession.max_capacity;

                                    return (
                                        <li key={targetSession.id}>
                                            <button
                                                type="button"
                                                disabled={isFull}
                                                className={cn(
                                                    'flex w-full items-center justify-between px-4 py-3 text-left text-sm transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                                                    isSelected
                                                        ? 'bg-primary/10 ring-1 ring-inset ring-primary'
                                                        : 'hover:bg-muted/50',
                                                    isFull && 'cursor-not-allowed opacity-50',
                                                )}
                                                onClick={() =>
                                                    form.setData('target_session_id', String(targetSession.id))
                                                }
                                            >
                                                <div className="flex flex-col gap-0.5">
                                                    <span className="font-medium">
                                                        {targetSession.attraction?.name ?? 'Unknown Attraction'}
                                                    </span>
                                                    <span className="text-xs text-muted-foreground">
                                                        {formatTime(targetSession.start_time)} –{' '}
                                                        {formatTime(targetSession.end_time)}
                                                    </span>
                                                </div>
                                                <div className="flex shrink-0 flex-col items-end gap-0.5">
                                                    <span className="tabular-nums text-xs text-muted-foreground">
                                                        {targetSession.current_pax}/{targetSession.max_capacity}
                                                    </span>
                                                    {isFull && (
                                                        <span className="text-xs font-semibold text-red-600 dark:text-red-400">
                                                            No space
                                                        </span>
                                                    )}
                                                </div>
                                            </button>
                                        </li>
                                    );
                                })}
                            </ul>
                        )}

                        {form.errors.target_session_id && (
                            <p className="text-sm text-destructive">{form.errors.target_session_id}</p>
                        )}

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={handleBack}>
                                Back
                            </Button>
                            <Button
                                type="submit"
                                disabled={!form.data.target_session_id || form.processing}
                            >
                                {form.processing ? 'Moving…' : 'Move Guest'}
                            </Button>
                        </DialogFooter>
                    </form>
                )}
            </DialogContent>
        </Dialog>
    );
}

export default MoveModal;
