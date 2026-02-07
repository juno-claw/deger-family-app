import { Check } from 'lucide-react';
import { cn } from '@/lib/utils';

const NOTE_COLORS: { value: string | null; label: string; bg: string }[] = [
    { value: null, label: 'Standard', bg: 'bg-card' },
    { value: '#fef3c7', label: 'Gelb', bg: 'bg-[#fef3c7]' },
    { value: '#dbeafe', label: 'Blau', bg: 'bg-[#dbeafe]' },
    { value: '#dcfce7', label: 'Grün', bg: 'bg-[#dcfce7]' },
    { value: '#fce7f3', label: 'Pink', bg: 'bg-[#fce7f3]' },
    { value: '#f3e8ff', label: 'Lila', bg: 'bg-[#f3e8ff]' },
];

interface ColorPickerProps {
    value: string | null;
    onChange: (color: string | null) => void;
}

export default function ColorPicker({ value, onChange }: ColorPickerProps) {
    return (
        <div className="flex items-center gap-1.5">
            {NOTE_COLORS.map((color) => (
                <button
                    key={color.value ?? 'default'}
                    type="button"
                    className={cn(
                        'flex size-7 items-center justify-center rounded-full border-2 transition-transform hover:scale-110',
                        value === color.value ? 'border-foreground' : 'border-transparent',
                    )}
                    style={color.value ? { backgroundColor: color.value } : undefined}
                    onClick={() => onChange(color.value)}
                    title={color.label}
                >
                    {!color.value && (
                        <div className="size-5 rounded-full border bg-card" />
                    )}
                    {value === color.value && (
                        <Check className="size-3.5" />
                    )}
                </button>
            ))}
        </div>
    );
}

export { NOTE_COLORS };
