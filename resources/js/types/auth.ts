export type User = {
    id: number;
    name?: string;
    username: string;
    email: string;
    account_type: 'user' | 'merchant';
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};
