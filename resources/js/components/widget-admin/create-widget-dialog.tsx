import { Form } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/widgets';
import { AccentColorField } from './accent-color-field';
import { SourceSelect } from './source-select';
import { TemplatePicker } from './template-picker';
import type { WidgetSource } from './types';

export function CreateWidgetDialog({ sources }: { sources: WidgetSource[] }) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button data-test="create-widget">
                    <Plus />
                    Create widget
                </Button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Create a widget</DialogTitle>
                </DialogHeader>

                <Form
                    {...store.form()}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    type="text"
                                    required
                                    placeholder="Storefront assistant"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <TemplatePicker />
                            <InputError message={errors.template} />

                            <SourceSelect sources={sources} />
                            <InputError message={errors.catalogue_origin_id} />

                            <AccentColorField />
                            <InputError message={errors.accent_color} />

                            <Button
                                type="submit"
                                disabled={processing}
                                className="w-full"
                            >
                                {processing && <Spinner />}
                                Create widget
                            </Button>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
