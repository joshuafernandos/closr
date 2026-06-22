import { Head, usePage } from '@inertiajs/react';
import { FileText, Plus } from 'lucide-react';
import { CatalogueConnectors } from '@/components/connector/catalogue-connectors';
import { ConnectorsShowcase } from '@/components/connector/connectors-showcase';
import { WooCommerceDialog } from '@/components/connector/woocommerce-dialog';
import { Button } from '@/components/ui/button';

interface ConnectorsProps {
    connection: { driver: string; url: string } | null;
}

export default function Connectors({ connection }: ConnectorsProps) {
    const appName = usePage<{ name: string }>().props.name;

    return (
        <>
            <Head title="Connectors" />

            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div className="space-y-2">
                        <h1 className="text-3xl font-bold tracking-tight">
                            Connectors
                        </h1>
                        <p className="max-w-2xl text-sm text-muted-foreground">
                            Plug in the channels you already use. <br /> Connect a product source so Closr can search your catalogue
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" className="rounded-full">
                            <FileText />
                            Docs
                        </Button>
                    </div>
                </div>

                <ConnectorsShowcase />

                <CatalogueConnectors url={connection?.url ?? null} />
            </div>
        </>
    );
}
