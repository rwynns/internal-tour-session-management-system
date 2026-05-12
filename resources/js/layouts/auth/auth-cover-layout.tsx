import type { AuthLayoutProps } from '@/types';

export default function AuthCoverLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="grid min-h-svh lg:grid-cols-2">
            <div className="flex flex-col items-center justify-center bg-[#0bb4b1]/50 p-6 md:p-10">
                <div className="w-full max-w-md rounded-2xl bg-white p-8 shadow-2xl">
                    <div className="mb-6 flex flex-col gap-1 text-center">
                        <h1 className="text-2xl font-bold">{title}</h1>
                        {description && (
                            <p className="text-sm text-balance text-muted-foreground">
                                {description}
                            </p>
                        )}
                    </div>
                    {children}
                </div>
            </div>

            <div className="relative hidden bg-muted lg:block">
                <img
                    src="/images/illustration.jpeg"
                    alt="Tour illustration"
                    className="absolute inset-0 h-full w-full object-cover"
                />
            </div>
        </div>
    );
}
