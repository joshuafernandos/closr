import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft, Trash2 } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { AccentColorField } from '@/components/widget-admin/accent-color-field';
import { EmbedSnippet } from '@/components/widget-admin/embed-snippet';
import { SourceSelect } from '@/components/widget-admin/source-select';
import { TemplatePicker } from '@/components/widget-admin/template-picker';
import type {
    WidgetSource,
    WidgetSummary,
} from '@/components/widget-admin/types';
import { destroy, index as widgets, update } from '@/routes/widgets';

interface EditWidgetProps {
    widget: WidgetSummary;
    sources: WidgetSource[];
    widgetUrl: string;
    embedUrl: string;
}

export default function EditWidget({
    widget,
    sources,
    widgetUrl,
    embedUrl,
}: EditWidgetProps) {
    const [previewKey, setPreviewKey] = useState(0);

    return (
        <>
            <Head title={`Edit ${widget.name}`} />

            <div className="flex h-full flex-1 flex-col gap-6 bg-muted/30 p-4 md:p-6">
                <div className="flex items-center gap-3">
                    <Link
                        href={widgets()}
                        className="text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="size-5" />
                    </Link>
                    <h1 className="text-2xl font-bold tracking-tight">
                        {widget.name}
                    </h1>
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Configuration */}
                    <Card>
                        <CardContent className="p-5">
                            <Form
                                {...update.form(widget.key)}
                                options={{ preserveScroll: true }}
                                onSuccess={() =>
                                    setPreviewKey((key) => key + 1)
                                }
                                className="space-y-5"
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
                                                defaultValue={widget.name}
                                            />
                                            <InputError message={errors.name} />
                                        </div>

                                        <TemplatePicker
                                            defaultValue={widget.template}
                                        />
                                        <InputError message={errors.template} />

                                        <SourceSelect
                                            sources={sources}
                                            defaultValue={
                                                widget.catalogueOriginId
                                            }
                                        />
                                        <InputError
                                            message={errors.catalogue_origin_id}
                                        />

                                        <AccentColorField
                                            defaultValue={widget.accentColor}
                                        />
                                        <InputError
                                            message={errors.accent_color}
                                        />

                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            {processing && <Spinner />}
                                            Save changes
                                        </Button>
                                    </>
                                )}
                            </Form>
                        </CardContent>
                    </Card>

                    {/* Preview + embed */}
                    <div className="space-y-6">
                        <Card>
                            <CardContent className="space-y-3 p-5">
                                <Label>Live preview</Label>
                                <div className="overflow-hidden rounded-xl border bg-muted/30">
                                    <iframe
                                        key={previewKey}
                                        src={widgetUrl}
                                        title="Widget preview"
                                        className="h-[560px] w-full"
                                    />
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardContent className="p-5">
                                <EmbedSnippet
                                    embedUrl={embedUrl}
                                    widgetKey={widget.key}
                                />
                            </CardContent>
                        </Card>
                    </div>
                </div>

                {/* Danger zone */}
                <Card className="border-destructive/30">
                    <CardContent className="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                        <div className="space-y-1">
                            <p className="font-semibold">Delete this widget</p>
                            <p className="text-sm text-muted-foreground">
                                Removes the widget and its embed. Shopper
                                conversations are kept.
                            </p>
                        </div>
                        <Form
                            {...destroy.form(widget.key)}
                            className="shrink-0"
                        >
                            {({ processing }) => (
                                <Button
                                    type="submit"
                                    variant="destructive"
                                    disabled={processing}
                                    data-test="delete-widget"
                                >
                                    {processing ? <Spinner /> : <Trash2 />}
                                    Delete widget
                                </Button>
                            )}
                        </Form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

EditWidget.layout = () => ({
    breadcrumbs: [
        { title: 'Widgets', href: widgets() },
        { title: 'Edit', href: widgets() },
    ],
});
