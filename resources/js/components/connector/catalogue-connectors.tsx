import { Plug } from 'lucide-react';
import { AddSourceMenu } from '@/components/connector/add-source-menu';
import {
    ConnectorCard,
    FooterBox,
} from '@/components/connector/connector-card';
import { SourceCard } from '@/components/connector/source-card';
import type { Connection } from '@/components/connector/source-card';
import { WooCommerceDialog } from '@/components/connector/woocommerce-dialog';
import { Button } from '@/components/ui/button';

interface CatalogueConnectorsProps {
    connections: Connection[];
}

export function CatalogueConnectors({ connections }: CatalogueConnectorsProps) {
    return (
        <>
            <div className="flex items-center justify-between">
                <h2 className="text-lg font-bold">Catalogue sources</h2>
                {connections.length > 0 && <AddSourceMenu />}
            </div>

            {connections.length === 0 ? (
                <EmptyState />
            ) : (
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {connections.map((connection) => (
                        <SourceCard
                            key={connection.id}
                            connection={connection}
                        />
                    ))}
                </div>
            )}
        </>
    );
}

/** Shown when the business has not connected any source yet. */
function EmptyState() {
    return (
        <ConnectorCard
            icon={Plug}
            iconClassName="bg-[#7f54b3]"
            name="No sources yet"
            description="Connect a product source so your widgets can search your catalogue."
            status={
                <span className="text-xs font-medium text-muted-foreground">
                    Not connected
                </span>
            }
            footer={
                <FooterBox>
                    <span className="text-muted-foreground">
                        Start with a WooCommerce store
                    </span>
                    <WooCommerceDialog
                        trigger={
                            <Button
                                size="sm"
                                className="shrink-0"
                                data-test="connect-button"
                            >
                                Connect
                            </Button>
                        }
                    />
                </FooterBox>
            }
        />
    );
}
