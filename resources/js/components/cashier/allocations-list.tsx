import { Button } from '@/components/ui/button';
import type { GuestAllocation } from '@/types';
import { router } from '@inertiajs/react';
import { cancel } from '@/actions/App/Http/Controllers/CashierDashboardController';
import { useState } from 'react';

type AllocationsListProps = {
    allocations: GuestAllocation[];
};

export function AllocationsList({ allocations }: AllocationsListProps) {
    const [errors, setErrors] = useState<Record<number, string>>({});
    const [processing, setProcessing] = useState<Record<number, boolean>>({});

    const activeAllocations = allocations.filter((a) => a.status === 'active');

    function handleCancel(allocation: GuestAllocation) {
        if (!window.confirm('Are you sure you want to cancel this allocation?')) {
            return;
        }

        setProcessing((prev) => ({ ...prev, [allocation.id]: true }));
        setErrors((prev) => {
            const next = { ...prev };
            delete next[allocation.id];
            return next;
        });

        router.patch(
            cancel(allocation.id).url,
            {},
            {
                onError: (responseErrors) => {
                    const message =
                        responseErrors.allocation ??
                        responseErrors.status ??
                        Object.values(responseErrors)[0] ??
                        'The cancellation could not be completed. Please try again.';
                    setErrors((prev) => ({ ...prev, [allocation.id]: message }));
                },
                onFinish: () => {
                    setProcessing((prev) => {
                        const next = { ...prev };
                        delete next[allocation.id];
                        return next;
                    });
                },
            },
        );
    }

    if (activeAllocations.length === 0) {
        return (
            <p className="text-muted-foreground py-2 text-sm">No active allocations.</p>
        );
    }

    return (
        <ul className="divide-border divide-y">
            {activeAllocations.map((allocation) => (
                <li key={allocation.id} className="py-2">
                    <div className="flex items-start justify-between gap-2">
                        <div className="min-w-0 flex-1">
                            <p className="truncate text-sm font-medium">{allocation.guest_name}</p>
                            <p className="text-muted-foreground text-xs">
                                {allocation.pax} {allocation.pax === 1 ? 'guest' : 'guests'}
                                {allocation.source ? ` · ${allocation.source}` : ''}
                            </p>
                            {errors[allocation.id] && (
                                <p className="mt-1 text-xs text-red-600 dark:text-red-400">
                                    {errors[allocation.id]}
                                </p>
                            )}
                        </div>
                        <Button
                            size="sm"
                            variant="ghost"
                            className="text-destructive hover:text-destructive shrink-0 disabled:cursor-not-allowed disabled:opacity-50"
                            disabled={processing[allocation.id] ?? false}
                            onClick={() => handleCancel(allocation)}
                        >
                            {processing[allocation.id] ? 'Cancelling…' : 'Cancel'}
                        </Button>
                    </div>
                </li>
            ))}
        </ul>
    );
}

export default AllocationsList;
