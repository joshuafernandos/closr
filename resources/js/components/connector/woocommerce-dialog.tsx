import { Form } from '@inertiajs/react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import ConnectorController from '@/actions/App/Http/Controllers/ConnectorController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

interface WooCommerceDialogProps {
    /** Optional trigger element. Omit when controlling `open` externally. */
    trigger?: ReactNode;
    open?: boolean;
    onOpenChange?: (open: boolean) => void;
}

export function WooCommerceDialog({
    trigger,
    open: controlledOpen,
    onOpenChange,
}: WooCommerceDialogProps) {
    const [internalOpen, setInternalOpen] = useState(false);

    const open = controlledOpen ?? internalOpen;
    const setOpen = onOpenChange ?? setInternalOpen;

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            {trigger && <DialogTrigger asChild>{trigger}</DialogTrigger>}
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Connect WooCommerce</DialogTitle>
                    <DialogDescription>
                        Enter your store URL and read-only API keys. We verify
                        them before saving.
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...ConnectorController.store.form()}
                    options={{ preserveScroll: true }}
                    resetOnSuccess={['secret']}
                    onSuccess={() => setOpen(false)}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="url">Store URL</Label>
                                <Input
                                    id="url"
                                    name="url"
                                    type="url"
                                    required
                                    placeholder="https://yourstore.com"
                                />
                                <InputError message={errors.url} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="key">Consumer key</Label>
                                <Input
                                    id="key"
                                    name="key"
                                    type="text"
                                    required
                                    autoComplete="off"
                                    placeholder="ck_..."
                                />
                                <InputError message={errors.key} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="secret">Consumer secret</Label>
                                <Input
                                    id="secret"
                                    name="secret"
                                    type="password"
                                    required
                                    autoComplete="off"
                                    placeholder="cs_..."
                                />
                                <InputError message={errors.secret} />
                            </div>

                            <Button
                                type="submit"
                                disabled={processing}
                                className="w-full"
                                data-test="connect-button"
                            >
                                {processing && <Spinner />}
                                Connect store
                            </Button>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
