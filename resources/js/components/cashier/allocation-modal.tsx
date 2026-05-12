import { useForm } from '@inertiajs/react';
import { store } from '@/actions/App/Http/Controllers/CashierDashboardController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { SessionWithAllocations } from '@/types';

type AllocationModalProps = {
    session: SessionWithAllocations | null;
    onClose: () => void;
};

export function AllocationModal({ session, onClose }: AllocationModalProps) {
    const form = useForm({ guest_name: '', pax: 1, source: '', notes: '' });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();

        if (!session) {
            return;
        }

        form.post(store(session.id).url, {
            onSuccess: () => {
                form.reset();
                onClose();
            },
        });
    }

    function handleOpenChange(open: boolean) {
        if (!open) {
            form.reset();
            form.clearErrors();
            onClose();
        }
    }

    return (
        <Dialog open={session !== null} onOpenChange={handleOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Allocate Guest</DialogTitle>
                    <DialogDescription>
                        {session?.attraction?.name
                            ? `Add a guest to ${session.attraction.name}`
                            : 'Add a guest to this session'}
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="flex flex-col gap-4">
                    {/* Session-level errors */}
                    {form.errors.session && (
                        <p className="text-sm text-destructive">{form.errors.session}</p>
                    )}

                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="guest_name">
                            Guest Name <span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="guest_name"
                            type="text"
                            value={form.data.guest_name}
                            onChange={(e) => form.setData('guest_name', e.target.value)}
                            placeholder="Enter guest name"
                            aria-invalid={!!form.errors.guest_name}
                            disabled={form.processing}
                            autoFocus
                        />
                        <InputError message={form.errors.guest_name} />
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="pax">
                            Number of Guests (Pax) <span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="pax"
                            type="number"
                            min={1}
                            value={form.data.pax}
                            onChange={(e) => form.setData('pax', parseInt(e.target.value, 10) || 1)}
                            aria-invalid={!!form.errors.pax}
                            disabled={form.processing}
                        />
                        <InputError message={form.errors.pax} />
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="source">Source</Label>
                        <Input
                            id="source"
                            type="text"
                            value={form.data.source}
                            onChange={(e) => form.setData('source', e.target.value)}
                            placeholder="e.g. Walk-in, Online, Phone"
                            aria-invalid={!!form.errors.source}
                            disabled={form.processing}
                        />
                        <InputError message={form.errors.source} />
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="notes">Notes</Label>
                        <Textarea
                            id="notes"
                            value={form.data.notes}
                            onChange={(e) => form.setData('notes', e.target.value)}
                            placeholder="Any additional notes..."
                            rows={3}
                            aria-invalid={!!form.errors.notes}
                            disabled={form.processing}
                        />
                        <InputError message={form.errors.notes} />
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => handleOpenChange(false)}
                            disabled={form.processing}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? 'Allocating…' : 'Allocate Guest'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default AllocationModal;
