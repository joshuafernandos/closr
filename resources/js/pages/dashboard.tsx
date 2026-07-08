import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';
import {
    DashboardStats,
    type DashboardStats as DashboardStatsType,
} from '@/components/dashboard/dashboard-stats';
import {
    OnboardingSteps,
    type OnboardingState,
} from '@/components/dashboard/onboarding-steps';
import PendingInvitationsModal from '@/components/pending-invitations-modal';
import { dashboard } from '@/routes';
import type { DashboardInvitation } from '@/types';

type Props = {
    pendingInvitations?: DashboardInvitation[];
    onboarding: OnboardingState;
    stats: DashboardStatsType;
};

export default function Dashboard({
    pendingInvitations = [],
    onboarding,
    stats,
}: Props) {
    const { auth } = usePage().props;
    const [showInvitations, setShowInvitations] = useState(
        pendingInvitations.length > 0,
    );

    return (
        <>
            <Head title="Dashboard" />
            <PendingInvitationsModal
                invitations={pendingInvitations}
                open={pendingInvitations.length > 0 && showInvitations}
                onOpenChange={setShowInvitations}
            />
            <div className="flex h-full flex-1 flex-col gap-6 bg-muted/30 p-4 md:p-6">
                <div className="space-y-1">
                    <h1 className="text-2xl font-bold tracking-tight">
                        Overview
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Welcome back, {auth.user.name}.
                    </p>
                </div>
                <OnboardingSteps onboarding={onboarding} />
                <DashboardStats stats={stats} />
            </div>
        </>
    );
}

Dashboard.layout = () => ({
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
});
