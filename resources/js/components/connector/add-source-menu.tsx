import { FileSpreadsheet, Plug, Plus, ShoppingBag } from 'lucide-react';
import { useState } from 'react';
import { WooCommerceDialog } from '@/components/connector/woocommerce-dialog';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

/** A "+" menu to connect a new catalogue source of a chosen type. */
export function AddSourceMenu() {
    const [wooOpen, setWooOpen] = useState(false);

    return (
        <>
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button
                        size="sm"
                        className="rounded-full"
                        data-test="add-source"
                    >
                        <Plus />
                        Add source
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="w-56">
                    <DropdownMenuItem onSelect={() => setWooOpen(true)}>
                        <Plug className="text-[#7f54b3]" />
                        WooCommerce
                    </DropdownMenuItem>
                    <DropdownMenuItem disabled>
                        <ShoppingBag />
                        Shopify
                        <span className="ml-auto text-[10px] tracking-wide text-muted-foreground uppercase">
                            Soon
                        </span>
                    </DropdownMenuItem>
                    <DropdownMenuItem disabled>
                        <FileSpreadsheet />
                        CSV import
                        <span className="ml-auto text-[10px] tracking-wide text-muted-foreground uppercase">
                            Soon
                        </span>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>

            <WooCommerceDialog open={wooOpen} onOpenChange={setWooOpen} />
        </>
    );
}
