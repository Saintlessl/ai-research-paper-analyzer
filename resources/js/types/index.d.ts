export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
    role?: 'researcher' | 'reviewer' | 'admin' | string;
    roles?: Array<{ name: string } | string>;
}
export interface Evidence { page?: number | null; section?: string | null; excerpt?: string | null; confidence?: number | null; }
export interface Author { id?: number; name: string; affiliation?: string | null; }
export interface Paper { id: number; title: string; abstract?: string | null; status?: string | null; publication_year?: number | null; journal?: string | null; doi?: string | null; keywords?: string[] | null; authors?: Author[]; created_at?: string; updated_at?: string; progress?: number | null; error_message?: string | null; overall_score?: number | null; }
export interface Score { id?: number; criterion: string; score: number; reason?: string | null; evidence?: Evidence[]; }
export interface Finding { id?: number; severity?: string; category?: string; finding?: string; title?: string; explanation?: string | null; confidence?: number | null; evidence?: Evidence[]; }
export interface Reference { id?: number; citation?: string; title?: string; authors?: string; year?: number | null; cited_in_text?: boolean | null; }
export interface Review { id?: number; reviewer?: User; status?: string; recommendation?: string | null; summary?: string | null; strengths?: string[]; major_concerns?: string[]; minor_concerns?: string[]; scores?: Record<string, number>; comments?: Array<{ id?: number; body: string; author?: User; created_at?: string }>; submitted_at?: string | null; }
export interface AiJob { id: number | string; paper?: Paper; type?: string; status?: string; attempts?: number; max_attempts?: number; started_at?: string | null; completed_at?: string | null; duration?: number | string | null; error?: string | null; request_id?: string | null; }
export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & { auth: { user: User }; flash?: { success?: string; error?: string; info?: string }; };
