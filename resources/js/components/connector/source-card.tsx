import { Form } from '@inertiajs/react';
import { FileSpreadsheet, Plug, ShoppingBag } from 'lucide-react';
import type { ComponentType } from 'react';
import ConnectorController from '@/actions/App/Http/Controllers/ConnectorController';
import {
    ConnectedPill,
    ConnectorCard,
    FooterBox,
} from '@/components/connector/connector-card';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';

export interface Connection {
    id: number;
    driver: string;
    name: string;
    url: string;
}

const DRIVERS: Record<
    string,
    {
        label: string;
        icon: ComponentType<{ className?: string }>;
        iconClassName: string;
    }
> = {
    woocommerce: {
        label: 'WooCommerce',
        icon: Plug,
        iconClassName: 'bg-[#7f54b3]',
    },
    shopify: {
        label: 'Shopify',
        icon: ShoppingBag,
        iconClassName: 'bg-[#95bf47]',
    },
    csv: {
        label: 'CSV import',
        icon: FileSpreadsheet,
        iconClassName: 'bg-neutral-600',
    },
};

/** A connected catalogue source, with a Disconnect action. */
export function SourceCard({ connection }: { connection: Connection }) {
    const meta = DRIVERS[connection.driver] ?? {
        label: connection.driver,
        icon: Plug,
        iconClassName: 'bg-neutral-600',
    };

    return (
        <ConnectorCard
            icon={meta.icon}
            iconClassName={meta.iconClassName}
            name={connection.name || meta.label}
            description={`${meta.label} catalogue source.`}
            status={<ConnectedPill />}
            footer={
                <FooterBox>
                    <span className="min-w-0 truncate font-medium">
                        {connection.url || meta.label}
                    </span>
                    <Form
                        {...ConnectorController.destroy.form({
                            origin: connection.id,
                        })}
                        options={{ preserveScroll: true }}
                        className="shrink-0"
                    >
                        {({ processing }) => (
                            <Button
                                type="submit"
                                variant="outline"
                                size="sm"
                                disabled={processing}
                                data-test="disconnect-button"
                            >
                                {processing && <Spinner />}
                                Disconnect
                            </Button>
                        )}
                    </Form>
                </FooterBox>
            }
        />
    );
}
