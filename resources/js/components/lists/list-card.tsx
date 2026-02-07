import { Link } from '@inertiajs/react';
import { CheckSquare, ShoppingCart, Users } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { show } from '@/routes/lists';
import type { FamilyList } from '@/types';

interface ListCardProps {
    list: FamilyList;
    showOwner?: boolean;
}

export function ListCard({ list, showOwner = false }: ListCardProps) {
    const items = list.items ?? [];
    const completedCount = items.filter((i) => i.is_completed).length;
    const totalCount = items.length;
    const progressPercent = totalCount > 0 ? (completedCount / totalCount) * 100 : 0;

    const Icon = list.type === 'shopping' ? ShoppingCart : CheckSquare;

    return (
        <Link href={show.url(list.id)} className="group block">
            <Card className="h-full transition-all group-hover:border-primary/50 group-hover:shadow-md">
                <CardHeader className="flex-row items-start gap-3 space-y-0 pb-2">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <Icon className="h-5 w-5" />
                    </div>
                    <div className="min-w-0 flex-1">
                        <CardTitle className="truncate text-sm font-semibold">
                            {list.title}
                        </CardTitle>
                        <div className="mt-1 flex items-center gap-2">
                            <Badge variant="secondary" className="text-[10px]">
                                {list.type === 'shopping' ? 'Einkauf' : 'Todo'}
                            </Badge>
                            {showOwner && list.owner && (
                                <span className="truncate text-xs text-muted-foreground">
                                    von {list.owner.name}
                                </span>
                            )}
                        </div>
                    </div>
                </CardHeader>
                <CardContent className="pt-0">
                    {totalCount > 0 ? (
                        <div className="space-y-1.5">
                            <div className="flex items-center justify-between text-xs text-muted-foreground">
                                <span>
                                    {completedCount}/{totalCount} erledigt
                                </span>
                                <span>{Math.round(progressPercent)}%</span>
                            </div>
                            <div className="h-1.5 w-full overflow-hidden rounded-full bg-muted">
                                <div
                                    className="h-full rounded-full bg-primary transition-all"
                                    style={{ width: `${progressPercent}%` }}
                                />
                            </div>
                        </div>
                    ) : (
                        <p className="text-xs text-muted-foreground">
                            Keine Eintraege
                        </p>
                    )}

                    {list.shared_with && list.shared_with.length > 0 && (
                        <div className="mt-2 flex items-center gap-1 text-xs text-muted-foreground">
                            <Users className="h-3 w-3" />
                            <span>
                                {list.shared_with
                                    .map((u) => u.name)
                                    .join(', ')}
                            </span>
                        </div>
                    )}
                </CardContent>
            </Card>
        </Link>
    );
}
