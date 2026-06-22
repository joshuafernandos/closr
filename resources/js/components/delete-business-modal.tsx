import { Form } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { destroy } from '@/routes/businesses';
import type { Business } from '@/types';

type Props = {
    business: Business;
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export default function DeleteBusinessModal({ business, open, onOpenChange }: Props) {
    const [confirmationName, setConfirmationName] = useState('');

    const canDeleteBusiness = confirmationName === business.name;

    const handleOpenChange = (nextOpen: boolean) => {
        onOpenChange(nextOpen);

        if (!nextOpen) {
            setConfirmationName('');
        }
    };

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent>
                <Form
                    key={String(open)}
                    {...destroy.form(business.slug)}
                    className="space-y-6"
                    onSuccess={() => handleOpenChange(false)}
                >
                    {({ errors, processing }) => (
                        <>
                            <DialogHeader>
                                <DialogTitle>Are you sure?</DialogTitle>
                                <DialogDescription>
                                    This action cannot be undone. This will
                                    permanently delete the business{' '}
                                    <strong>"{business.name}"</strong>.
                                </DialogDescription>
                            </DialogHeader>

                            <div className="space-y-4 py-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="confirmation-name">
                                        Type <strong>"{business.name}"</strong> to
                                        confirm
                                    </Label>
                                    <Input
                                        id="confirmation-name"
                                        name="name"
                                        data-test="delete-business-name"
                                        value={confirmationName}
                                        onChange={(event) =>
                                            setConfirmationName(
                                                event.target.value,
                                            )
                                        }
                                        placeholder="Enter business name"
                                        autoComplete="off"
                                    />
                                    <InputError message={errors.name} />
                                </div>
                            </div>

                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button variant="secondary">Cancel</Button>
                                </DialogClose>

                                <Button
                                    variant="destructive"
                                    type="submit"
                                    data-test="delete-business-confirm"
                                    disabled={!canDeleteBusiness || processing}
                                >
                                    Delete business
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
