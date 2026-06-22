import { Head, Link } from '@inertiajs/react';
import { Eye, LogOut, Pencil, Plus } from 'lucide-react';
import { useState } from 'react';
import CreateBusinessModal from '@/components/create-business-modal';
import Heading from '@/components/heading';
import LeaveBusinessModal from '@/components/leave-business-modal';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { edit, index } from '@/routes/businesses';
import type { Business } from '@/types';

type Props = {
    businesses: Business[];
};

export default function BusinessesIndex({ businesses }: Props) {
    const [leaveBusinessDialogOpen, setLeaveBusinessDialogOpen] = useState(false);
    const [businessLeaving, setBusinessLeaving] = useState<Business | null>(null);

    const openLeaveBusinessDialog = (business: Business) => {
        setBusinessLeaving(business);
        setLeaveBusinessDialogOpen(true);
    };

    return (
        <>
            <Head title="Businesses" />

            <h1 className="sr-only">Businesses</h1>

            <div className="flex flex-col space-y-6">
                <div className="flex items-center justify-between">
                    <Heading
                        variant="small"
                        title="Businesses"
                        description="Manage your businesses and business memberships"
                    />

                    <CreateBusinessModal>
                        <Button data-test="businesses-new-business-button">
                            <Plus /> New business
                        </Button>
                    </CreateBusinessModal>
                </div>

                <div className="space-y-3">
                    {businesses.map((business) => {
                        const canLeaveBusiness =
                            !business.isPersonal && business.role !== 'owner';

                        return (
                            <div
                                key={business.id}
                                data-test="business-row"
                                className="flex items-center justify-between gap-4 rounded-lg border p-4"
                            >
                                <div className="flex items-center gap-4">
                                    <div>
                                        <div className="flex items-center gap-2">
                                            <span className="font-medium">
                                                {business.name}
                                            </span>
                                            {business.isPersonal ? (
                                                <Badge variant="secondary">
                                                    Personal
                                                </Badge>
                                            ) : null}
                                        </div>
                                        <span className="text-sm text-muted-foreground">
                                            {business.roleLabel}
                                        </span>
                                    </div>
                                </div>

                                <TooltipProvider>
                                    <div className="flex items-center gap-2">
                                        {canLeaveBusiness ? (
                                            <Tooltip>
                                                <TooltipTrigger asChild>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        data-test="business-leave-button"
                                                        onClick={() =>
                                                            openLeaveBusinessDialog(
                                                                business,
                                                            )
                                                        }
                                                    >
                                                        <LogOut className="h-4 w-4" />
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    <p>Leave business</p>
                                                </TooltipContent>
                                            </Tooltip>
                                        ) : null}

                                        {business.role === 'member' ? (
                                            <Tooltip>
                                                <TooltipTrigger asChild>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        data-test="business-view-button"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={edit(
                                                                business.slug,
                                                            )}
                                                        >
                                                            <Eye className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    <p>View business</p>
                                                </TooltipContent>
                                            </Tooltip>
                                        ) : (
                                            <Tooltip>
                                                <TooltipTrigger asChild>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        data-test="business-edit-button"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={edit(
                                                                business.slug,
                                                            )}
                                                        >
                                                            <Pencil className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    <p>Edit business</p>
                                                </TooltipContent>
                                            </Tooltip>
                                        )}
                                    </div>
                                </TooltipProvider>
                            </div>
                        );
                    })}

                    {businesses.length === 0 ? (
                        <p className="py-8 text-center text-muted-foreground">
                            You don't belong to any businesses yet.
                        </p>
                    ) : null}
                </div>
            </div>

            <LeaveBusinessModal
                business={businessLeaving}
                open={leaveBusinessDialogOpen}
                onOpenChange={setLeaveBusinessDialogOpen}
            />
        </>
    );
}

BusinessesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Businesses',
            href: index(),
        },
    ],
};
