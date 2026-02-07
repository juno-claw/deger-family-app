import type { User } from './auth';

export type ListItem = {
    id: number;
    list_id: number;
    content: string;
    is_completed: boolean;
    position: number;
    created_by: number | null;
    creator?: User;
    created_at: string;
    updated_at: string;
};

export type FamilyList = {
    id: number;
    title: string;
    type: 'todo' | 'shopping';
    icon: string | null;
    owner_id: number;
    owner?: User;
    items?: ListItem[];
    shared_with?: (User & { pivot: { permission: 'view' | 'edit' } })[];
    created_at: string;
    updated_at: string;
};
