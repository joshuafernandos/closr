import { Head } from '@inertiajs/react';
import { FileText } from 'lucide-react';
import { CatalogueConnectors } from '@/components/connector/catalogue-connectors';
import { ConnectorsShowcase } from '@/components/connector/connectors-showcase';
import type { Connection } from '@/components/connector/source-card';
import { Button } from '@/components/ui/button';

interface ConnectorsProps {
    connections: Connection[];
}

export default function Connectors({ connections }: ConnectorsProps) {
    return (
        <>
            <Head title="Connectors" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
                {/* Header */}
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div className="space-y-2">
                        <h1 className="text-2xl font-bold tracking-tight">
                            Connectors
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Plug in the channels you already use. <br /> Connect
                            a product source so Closr can search your catalogue
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

                <CatalogueConnectors connections={connections} />
            </div>
        </>
    );
}
