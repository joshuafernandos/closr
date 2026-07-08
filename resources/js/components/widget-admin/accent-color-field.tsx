import { useState } from 'react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const HEX = /^#[0-9a-fA-F]{6}$/;

/** A colour picker + hex input backing an `accent_color` form field. */
export function AccentColorField({
    name = 'accent_color',
    defaultValue = '#171717',
}: {
    name?: string;
    defaultValue?: string;
}) {
    const [value, setValue] = useState(defaultValue);

    const safe = HEX.test(value) ? value : '#171717';

    return (
        <div className="grid gap-2">
            <Label htmlFor="accent_color_hex">Accent colour</Label>
            <input type="hidden" name={name} value={value} />
            <div className="flex items-center gap-3">
                <input
                    type="color"
                    aria-label="Accent colour"
                    value={safe}
                    onChange={(event) => setValue(event.target.value)}
                    className="size-10 shrink-0 cursor-pointer rounded-md border bg-transparent p-1"
                    data-test="accent-color"
                />
                <Input
                    id="accent_color_hex"
                    value={value}
                    onChange={(event) => setValue(event.target.value)}
                    placeholder="#171717"
                    className="max-w-40 font-mono"
                />
                <span
                    className="size-6 rounded-full border"
                    style={{ background: safe }}
                />
            </div>
        </div>
    );
}
