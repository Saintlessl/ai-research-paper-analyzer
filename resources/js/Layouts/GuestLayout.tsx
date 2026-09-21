import React, { PropsWithChildren } from 'react';
import { Link } from '@inertiajs/react';
import ApplicationLogo from '@/Components/ApplicationLogo';
import { NeuCard } from '@/Components/ui/NeuCard';
import { ThemeToggle } from '@/Components/ui/ThemeToggle';

export default function Guest({ children }: PropsWithChildren) {
    return (
        <div className="flex min-h-screen flex-col items-center bg-neu-bg pt-6 sm:justify-center sm:pt-0">
            {/* Absolute positioning for ThemeToggle in top right */}
            <div className="absolute top-4 right-4 z-10">
                <ThemeToggle />
            </div>

            <div>
                <Link href="/" className="flex flex-col items-center gap-4">
                    <ApplicationLogo className="h-16 w-16 text-neu-accent-fill" />
                    <span className="font-bold tracking-tight text-xl text-neu-text">ScholarLens</span>
                </Link>
            </div>

            <div className="mt-8 w-full sm:max-w-md px-4">
                <NeuCard elevation="raised-lg" padding="lg">
                    {children}
                </NeuCard>
            </div>
        </div>
    );
}
