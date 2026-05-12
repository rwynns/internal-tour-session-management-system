import type { InertiaLinkProps } from '@inertiajs/react';
import type { Icon } from '@tabler/icons-react';
import type { LucideIcon } from 'lucide-react';

export type BreadcrumbItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
};

export type NavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | Icon | null;
    isActive?: boolean;
};
