import { cn } from '@/lib/utils';

interface EventBadgeProps {
    color: string;
    title?: string;
    compact?: boolean;
    className?: string;
}

export function EventBadge({ color, title, compact = true, className }: EventBadgeProps) {
    if (compact) {
        return (
            <span
                className={cn('inline-block size-1.5 rounded-full shrink-0', className)}
                style={{ backgroundColor: color }}
                title={title}
            />
        );
    }

    return (
        <span
            className={cn(
                'inline-flex items-center gap-1 rounded-full px-1.5 py-0.5 text-[10px] leading-none font-medium text-white truncate max-w-full',
                className,
            )}
            style={{ backgroundColor: color }}
        >
            {title}
        </span>
    );
}
