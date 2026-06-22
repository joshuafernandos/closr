import { Form } from '@inertiajs/react';
import { Plug } from 'lucide-react';
import ConnectorController from '@/actions/App/Http/Controllers/ConnectorController';
import {
    ConnectedPill,
    ConnectorCard,
    FooterBox,
} from '@/components/connector/connector-card';
import { WooCommerceDialog } from '@/components/connector/woocommerce-dialog';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';

export function WooCommerceCard({ url }: { url: string | null }) {
    if (url) {
        return (
            <ConnectorCard
                icon={Plug}
                iconClassName="bg-[#7f54b3]"
                name="WooCommerce"
                description="Search your store catalogue in conversations."
                status={<ConnectedPill />}
                mostUsed
                footer={
                    <FooterBox>
                        <span className="min-w-0 truncate font-medium">
                            {url}
                        </span>
                        <Form
                            {...ConnectorController.destroy.form()}
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

    return (
        <ConnectorCard
            icon={Plug}
            iconClassName="bg-[#7f54b3]"
            name="WooCommerce"
            description="Search your store catalogue in conversations."
            status={
                <span className="text-xs font-medium text-muted-foreground">
                    Not connected
                </span>
            }
            footer={
                <FooterBox>
                    <span className="text-muted-foreground">
                        Store URL + read-only API keys
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
