import {
    CircleCheckIcon,
    InfoIcon,
    Loader2Icon,
    OctagonXIcon,
    TriangleAlertIcon,
} from 'lucide-react';
import { useTheme } from 'next-themes';
import { Toaster as Sonner, type ToasterProps } from 'sonner';

const Toaster = ({ ...props }: ToasterProps) => {
    const { theme = 'system' } = useTheme();

    return (
        <Sonner
            theme={theme as ToasterProps['theme']}
            position="top-center"
            className="toaster group"
            icons={{
                success: <CircleCheckIcon className="size-4" />,
                info: <InfoIcon className="size-4" />,
                warning: <TriangleAlertIcon className="size-4" />,
                error: <OctagonXIcon className="size-4" />,
                loading: <Loader2Icon className="size-4 animate-spin" />,
            }}
            style={
                {
                    // Toast background & text
                    '--normal-bg': 'var(--popover)',
                    '--normal-text': 'var(--popover-foreground)',
                    '--normal-border': 'var(--border)',
                    '--border-radius': 'var(--radius)',

                    // Description text — explicitly set agar tidak nyaru
                    '--normal-description': 'var(--muted-foreground)',

                    // Action button — destructive merah
                    '--toast-button-bg': 'var(--destructive)',
                    '--toast-button-color': 'var(--destructive-foreground)',

                    // Cancel button — muted dengan foreground kontras
                    '--toast-cancel-button-bg': 'var(--muted)',
                    '--toast-cancel-button-color': 'var(--foreground)',
                } as React.CSSProperties
            }
            {...props}
        />
    );
};

export { Toaster };
