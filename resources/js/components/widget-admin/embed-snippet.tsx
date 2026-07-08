import { Check, Copy } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

/** A read-only, copyable embed snippet for a widget. */
export function EmbedSnippet({
    embedUrl,
    widgetKey,
}: {
    embedUrl: string;
    widgetKey: string;
}) {
    const [copied, setCopied] = useState(false);

    const snippet = `<script src="${embedUrl}" data-closr-key="${widgetKey}" async></script>`;

    const copy = async () => {
        try {
            await navigator.clipboard.writeText(snippet);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        } catch {
            // Clipboard unavailable — the user can still select the text.
        }
    };

    return (
        <div className="grid gap-2">
            <Label>Embed snippet</Label>
            <p className="text-xs text-muted-foreground">
                Paste this before the closing &lt;/body&gt; tag of your
                storefront.
            </p>
            <div className="flex items-start gap-2">
                <code className="block min-w-0 flex-1 rounded-md border bg-muted/50 p-3 font-mono text-xs break-all whitespace-pre-wrap">
                    {snippet}
                </code>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={copy}
                    className="shrink-0"
                    data-test="copy-embed"
                >
                    {copied ? <Check /> : <Copy />}
                    {copied ? 'Copied' : 'Copy'}
                </Button>
            </div>
        </div>
    );
}
