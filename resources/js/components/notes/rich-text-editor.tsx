import { Baseline, Bold, Eraser, Highlighter, List } from 'lucide-react';
import { type MouseEvent, useEffect, useRef, useState } from 'react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { cn } from '@/lib/utils';

type Swatch = { label: string; value: string };

const TEXT_COLORS: Swatch[] = [
    { label: 'Black', value: '#111827' },
    { label: 'Red', value: '#dc2626' },
    { label: 'Orange', value: '#ea580c' },
    { label: 'Amber', value: '#d97706' },
    { label: 'Green', value: '#16a34a' },
    { label: 'Teal', value: '#0d9488' },
    { label: 'Blue', value: '#2563eb' },
    { label: 'Purple', value: '#9333ea' },
    { label: 'Pink', value: '#db2777' },
    { label: 'Gray', value: '#6b7280' },
];

const HIGHLIGHT_COLORS: Swatch[] = [
    { label: 'None', value: 'transparent' },
    { label: 'Yellow', value: '#fef08a' },
    { label: 'Orange', value: '#fed7aa' },
    { label: 'Green', value: '#bbf7d0' },
    { label: 'Teal', value: '#99f6e4' },
    { label: 'Blue', value: '#bfdbfe' },
    { label: 'Purple', value: '#e9d5ff' },
    { label: 'Pink', value: '#fbcfe8' },
    { label: 'Gray', value: '#e5e7eb' },
];

const HIGHLIGHT_TEXT_COLOR = '#111827';

type ColorPickerMode = 'text' | 'background' | null;

function isEmptyContent(html: string): boolean {
    return html.replace(/<[^>]*>/g, '').trim() === '';
}

function preventMouseDownBlur(e: MouseEvent) {
    e.preventDefault();
}

export function RichTextEditor({
    value,
    onChange,
    placeholder,
}: {
    value: string;
    onChange: (html: string) => void;
    placeholder?: string;
}) {
    const ref = useRef<HTMLDivElement>(null);
    const lastValue = useRef(value);
    const [colorPicker, setColorPicker] = useState<ColorPickerMode>(null);

    useEffect(() => {
        if (ref.current && value !== lastValue.current) {
            ref.current.innerHTML = value;
        }
        lastValue.current = value;
    }, [value]);

    function emitChange() {
        const html = ref.current?.innerHTML ?? '';
        lastValue.current = html;
        onChange(html);
    }

    function withSelection(action: () => void) {
        ref.current?.focus();
        document.execCommand('styleWithCSS', false, 'true');
        action();
        emitChange();
    }

    function applyTextColor(color: string) {
        document.execCommand('styleWithCSS', false, 'true');
        document.execCommand('foreColor', false, color);
        emitChange();
    }

    function applyHighlight(color: string) {
        document.execCommand('styleWithCSS', false, 'true');
        const applied =
            document.execCommand('hiliteColor', false, color) ||
            document.execCommand('backColor', false, color);

        if (applied && color !== 'transparent') {
            document.execCommand('foreColor', false, HIGHLIGHT_TEXT_COLOR);
        }

        emitChange();
    }

    const palette = colorPicker === 'text' ? TEXT_COLORS : HIGHLIGHT_COLORS;

    return (
        <div className="rounded-md border">
            <div className="flex flex-wrap items-center gap-1 border-b p-1.5">
                <ToolbarButton
                    label="Bold"
                    onClick={() =>
                        withSelection(() => document.execCommand('bold'))
                    }
                >
                    <Bold className="size-4" />
                </ToolbarButton>
                <ToolbarButton
                    label="Bullet list"
                    onClick={() =>
                        withSelection(() =>
                            document.execCommand('insertUnorderedList'),
                        )
                    }
                >
                    <List className="size-4" />
                </ToolbarButton>
                <ToolbarButton
                    label="Clear formatting"
                    onClick={() =>
                        withSelection(() =>
                            document.execCommand('removeFormat'),
                        )
                    }
                >
                    <Eraser className="size-4" />
                </ToolbarButton>

                <div className="bg-border mx-1 h-5 w-px" />

                <ToolbarButton
                    label="Text color"
                    onClick={() => setColorPicker('text')}
                >
                    <Baseline className="size-4" />
                </ToolbarButton>
                <ToolbarButton
                    label="Background color"
                    onClick={() => setColorPicker('background')}
                >
                    <Highlighter className="size-4" />
                </ToolbarButton>
            </div>

            <div className="relative">
                {isEmptyContent(value) && placeholder && (
                    <span className="text-muted-foreground pointer-events-none absolute top-3 left-3 text-sm">
                        {placeholder}
                    </span>
                )}

                <div
                    ref={ref}
                    contentEditable
                    suppressContentEditableWarning
                    onInput={emitChange}
                    onBlur={emitChange}
                    className="max-h-[80vh] min-h-[65vh] overflow-y-auto p-3 text-sm focus:outline-none [&_ul]:list-disc [&_ul]:pl-5"
                />
            </div>

            <Dialog
                open={colorPicker !== null}
                onOpenChange={(next) => !next && setColorPicker(null)}
            >
                <DialogContent className="max-w-sm">
                    <DialogHeader>
                        <DialogTitle>
                            {colorPicker === 'text'
                                ? 'Text color'
                                : 'Background color'}
                        </DialogTitle>
                    </DialogHeader>

                    <div className="grid grid-cols-5 gap-3">
                        {palette.map((color) => (
                            <ColorSwatch
                                key={color.value}
                                color={color}
                                onClick={() => {
                                    if (colorPicker === 'text') {
                                        applyTextColor(color.value);
                                    } else {
                                        applyHighlight(color.value);
                                    }
                                    setColorPicker(null);
                                }}
                            />
                        ))}
                    </div>

                    <label className="flex items-center gap-3 border-t pt-4 text-sm">
                        Custom color
                        <input
                            type="color"
                            defaultValue="#111827"
                            className="size-8 cursor-pointer rounded border-0 bg-transparent p-0"
                            onChange={(e) => {
                                if (colorPicker === 'text') {
                                    applyTextColor(e.target.value);
                                } else {
                                    applyHighlight(e.target.value);
                                }
                            }}
                        />
                    </label>
                </DialogContent>
            </Dialog>
        </div>
    );
}

function ToolbarButton({
    label,
    onClick,
    children,
}: {
    label: string;
    onClick: () => void;
    children: React.ReactNode;
}) {
    return (
        <button
            type="button"
            title={label}
            onMouseDown={preventMouseDownBlur}
            onClick={onClick}
            className="hover:bg-accent flex size-7 items-center justify-center rounded"
        >
            {children}
            <span className="sr-only">{label}</span>
        </button>
    );
}

function ColorSwatch({
    color,
    onClick,
}: {
    color: Swatch;
    onClick: () => void;
}) {
    return (
        <button
            type="button"
            title={color.label}
            onClick={onClick}
            className={cn(
                'border-border flex size-9 items-center justify-center rounded-full border',
                color.value === 'transparent' &&
                    'bg-[repeating-conic-gradient(#d1d5db_0%_25%,transparent_0%_50%)] bg-size-[8px_8px]',
            )}
            style={
                color.value === 'transparent'
                    ? undefined
                    : { backgroundColor: color.value }
            }
        >
            <span className="sr-only">{color.label}</span>
        </button>
    );
}
