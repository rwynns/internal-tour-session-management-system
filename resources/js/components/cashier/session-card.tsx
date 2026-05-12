import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import type { SessionWithAllocations } from '@/types';
import { OccupancyBar } from './occupancy-bar';

type SessionCardProps = {
    session: SessionWithAllocations;
    onAllocate: () => void;
    onMove: () => void;
};

export function SessionCard({ session, onAllocate, onMove }: SessionCardProps) {
    const isPast = new Date(session.start_time) < new Date();
    const isInactive = session.status === 'inactive';
    const isDisabled = isPast || isInactive;

    const formatTime = (dateString: string) =>
        new Date(dateString).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', timeZone: 'Asia/Jakarta' });

    const timeSlot = `${formatTime(session.start_time)} - ${formatTime(session.end_time)}`;
    const attractionName = session.attraction?.name ?? 'Unknown Attraction';

    return (
        <Card className={cn('flex flex-col gap-4', isDisabled && 'opacity-60')}>
            <CardHeader className="pb-0">
                <div className="flex items-start justify-between gap-2">
                    <CardTitle className={cn('text-base', isDisabled && 'text-muted-foreground')}>
                        {attractionName}
                    </CardTitle>
                    {isInactive && (
                        <Badge variant="secondary" className="shrink-0">
                            Inactive
                        </Badge>
                    )}
                </div>
                <p className={cn('text-sm', isDisabled ? 'text-muted-foreground' : 'text-muted-foreground')}>
                    {timeSlot}
                </p>
            </CardHeader>

            <CardContent className="pb-0">
                <OccupancyBar currentPax={session.current_pax} maxCapacity={session.max_capacity} />
            </CardContent>

            <CardFooter className="flex gap-2 pt-0">
                <Button
                    size="sm"
                    variant="default"
                    className="flex-1 disabled:cursor-not-allowed disabled:opacity-50"
                    disabled={isDisabled}
                    onClick={onAllocate}
                >
                    Allocate Guest
                </Button>
                <Button
                    size="sm"
                    variant="outline"
                    className="flex-1 disabled:cursor-not-allowed disabled:opacity-50"
                    disabled={isDisabled}
                    onClick={onMove}
                >
                    Move Guest
                </Button>
            </CardFooter>
        </Card>
    );
}

export default SessionCard;
