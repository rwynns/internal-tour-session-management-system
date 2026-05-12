import { cn } from '@/lib/utils';

type OccupancyBarProps = {
    currentPax: number;
    maxCapacity: number;
    className?: string;
};

export function OccupancyBar({ currentPax, maxCapacity, className }: OccupancyBarProps) {
    const percentage = maxCapacity > 0 ? Math.min(Math.max((currentPax / maxCapacity) * 100, 0), 100) : 0;
    const isFull = currentPax >= maxCapacity;

    return (
        <div className={cn('space-y-1', className)}>
            <div className="flex items-center justify-between text-sm">
                <span className={cn('font-medium tabular-nums', isFull ? 'text-red-600 dark:text-red-400' : 'text-foreground')}>
                    {currentPax}/{maxCapacity}
                </span>
                {isFull && (
                    <span className="text-xs font-semibold uppercase tracking-wide text-red-600 dark:text-red-400">
                        Full
                    </span>
                )}
            </div>
            <div className="h-2 w-full overflow-hidden rounded-full bg-muted">
                <div
                    className={cn(
                        'h-full rounded-full transition-all duration-300',
                        isFull ? 'bg-red-500 dark:bg-red-600' : 'bg-blue-500 dark:bg-blue-600',
                    )}
                    style={{ width: `${percentage}%` }}
                    role="progressbar"
                    aria-valuenow={currentPax}
                    aria-valuemin={0}
                    aria-valuemax={maxCapacity}
                    aria-label={`Occupancy: ${currentPax} of ${maxCapacity}`}
                />
            </div>
        </div>
    );
}

export default OccupancyBar;
