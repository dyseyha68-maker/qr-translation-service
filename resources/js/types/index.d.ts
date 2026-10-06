export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
    status: 'ACTIVE' | 'INACTIVE';
    role: string;
    created_at?: string;
    can?: {
        edit: boolean;
        delete: boolean;
    };
}

export interface ReferralPartner {
    id: number;
    name: string;
    type: 'DRIVER' | 'COMPANY' | 'OTHER';
    phone?: string | null;
    email?: string | null;
    company_name?: string | null;
    address?: string | null;
    notes?: string | null;
    status: 'ACTIVE' | 'INACTIVE';
    drivers_count?: number;
    drivers?: Driver[];
    created_at?: string;
    updated_at?: string;
    can?: {
        view: boolean;
        edit: boolean;
        delete: boolean;
    };
}

export interface Driver {
    id: number;
    referral_partner_id: number;
    name: string;
    phone: string;
    email?: string | null;
    national_id?: string | null;
    address?: string | null;
    notes?: string | null;
    status: 'ACTIVE' | 'INACTIVE';
    vehicles_count?: number;
    vehicles?: Vehicle[];
    referral_partner?: {
        id: number;
        name: string;
        type?: string;
        status?: string;
    } | null;
    created_at?: string;
    updated_at?: string;
    can?: {
        view: boolean;
        edit: boolean;
        delete: boolean;
    };
}

export interface Vehicle {
    id: number;
    driver_id: number;
    vehicle_type: 'TAXI' | 'TUK_TUK' | 'OTHER';
    plate_number: string;
    make?: string | null;
    model?: string | null;
    color?: string | null;
    notes?: string | null;
    status: 'ACTIVE' | 'INACTIVE';
    driver?: {
        id: number;
        name: string;
        phone?: string;
        status?: string;
        referral_partner?: {
            id: number;
            name: string;
            type?: string;
        } | null;
    } | null;
    created_at?: string;
    updated_at?: string;
    can?: {
        view: boolean;
        edit: boolean;
        delete: boolean;
    };
}

export interface PaginatedData<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    total: number;
    from: number | null;
    to: number | null;
    current_page: number;
    last_page: number;
    per_page: number;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
        can: {
            viewUsers: boolean;
            createUsers: boolean;
            viewPartners: boolean;
            createPartners: boolean;
            viewDrivers: boolean;
            createDrivers: boolean;
            viewVehicles: boolean;
            createVehicles: boolean;
        };
    };
    flash: {
        success?: string | null;
        error?: string | null;
    };
};
