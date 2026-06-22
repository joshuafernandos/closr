import { FileSpreadsheet, ShoppingBag } from 'lucide-react';
import {
    ComingSoonPill,
    ConnectorCard,
    FooterBox,
} from '@/components/connector/connector-card';
import { WooCommerceCard } from '@/components/connector/woocommerce-card';

export function CatalogueConnectors({ url }: { url: string | null }) {
    return (
        <>
            <div className="flex items-center justify-between">
                <h2 className="text-lg font-bold">Catalogue</h2>
            </div>

            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <WooCommerceCard url={url} />

                <ConnectorCard
                    icon={ShoppingBag}
                    iconClassName="bg-[#95bf47]"
                    name="Shopify"
                    description="Sync products straight from your Shopify store."
                    status={<ComingSoonPill />}
                    dimmed
                    footer={
                        <FooterBox>
                            <span className="text-muted-foreground">
                                OAuth + product sync
                            </span>
                        </FooterBox>
                    }
                />

                <ConnectorCard
                    icon={FileSpreadsheet}
                    iconClassName="bg-neutral-600"
                    name="CSV import"
                    description="Upload a product CSV to build your catalogue."
                    status={<ComingSoonPill />}
                    dimmed
                    footer={
                        <FooterBox>
                            <span className="text-muted-foreground">
                                Drag &amp; drop spreadsheet
                            </span>
                        </FooterBox>
                    }
                />
            </div>
        </>
    );
}
