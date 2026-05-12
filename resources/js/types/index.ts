export type * from './auth';
export type * from './navigation';
export type * from './ui';

export type Attraction = {
    id: number;
    name: string;
    description: string | null;
    duration_minutes: number;
    is_active: boolean;
    sessions_count?: number;
    created_at: string;
    updated_at: string;
};

export type SessionStatus = 'active' | 'inactive';

export type Session = {
    id: number;
    attraction_id: number;
    attraction?: Attraction;
    start_time: string;
    end_time: string;
    max_capacity: number;
    current_pax: number;
    status: SessionStatus;
    allocations?: GuestAllocation[];
    active_allocations?: GuestAllocation[];
    created_at: string;
    updated_at: string;
};

export type AllocationStatus = 'active' | 'cancelled';

export type GuestAllocation = {
    id: number;
    session_id: number;
    guest_name: string;
    pax: number;
    source: string | null;
    notes: string | null;
    status: AllocationStatus;
    allocated_by: number;
    session?: Session;
    created_at: string;
    updated_at: string;
};

export type SessionWithAllocations = Session & { allocations?: GuestAllocation[] };

export type PaginatedResponse<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
};
