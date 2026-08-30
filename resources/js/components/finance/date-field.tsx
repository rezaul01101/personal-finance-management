import { useRef } from 'react';

type DateInputWithPicker = HTMLInputElement & {
    showPicker?: () => void;
};

export function DateField({
    value,
    onChange,
}: {
    value: string;
    onChange: (value: string) => void;
}) {
    const inputRef = useRef<DateInputWithPicker>(null);

    const formatted = value
        ? new Date(`${value}T00:00:00`).toLocaleDateString('en-US', {
              day: 'numeric',
              month: 'long',
              year: 'numeric',
          })
        : 'Select date';

    function openPicker() {
        try {
            inputRef.current?.showPicker?.();
        } catch {
            inputRef.current?.focus();
        }
    }

    return (
        <div className="relative" onClick={openPicker}>
            <p className="pointer-events-none py-1 text-center text-base font-semibold">
                {formatted}
            </p>
            <input
                ref={inputRef}
                type="date"
                value={value}
                onChange={(e) => onChange(e.target.value)}
                className="absolute inset-0 size-full cursor-pointer opacity-0"
                aria-label="Date"
            />
        </div>
    );
}
