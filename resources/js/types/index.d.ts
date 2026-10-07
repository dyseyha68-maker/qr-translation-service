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

export interface ReferralCode {
    id: number;
    code: string;
    referral_partner_id: number;
    driver_id: number;
    vehicle_id: number;
    status: 'ACTIVE' | 'INACTIVE';
    label?: string | null;
    notes?: string | null;
    created_by: number;
    deactivated_at?: string | null;
    created_at?: string;
    updated_at?: string;
    referral_partner?: ReferralPartner | null;
    driver?: Driver | null;
    vehicle?: Vehicle | null;
    creator?: User | null;
    can?: {
        view: boolean;
        edit: boolean;
        delete: boolean;
        activate: boolean;
        deactivate: boolean;
        viewQr: boolean;
        downloadQr: boolean;
    };
}

export interface TelegramUser {
    id: number;
    telegram_id: number;
    username?: string | null;
    first_name?: string | null;
    last_name?: string | null;
    full_name?: string;
    language_code?: string | null;
    is_bot: boolean;
    phone?: string | null;
    last_interaction_at?: string | null;
    created_at?: string;
}

export interface Customer {
    id: number;
    telegram_user_id?: number | null;
    name: string;
    phone?: string | null;
    email?: string | null;
    notes?: string | null;
    status: 'ACTIVE' | 'INACTIVE';
    created_at?: string;
    updated_at?: string;
}

export interface ReferralAttribution {
    id: number;
    referral_event_id: number;
    referral_code_id: number;
    telegram_user_id?: number | null;
    customer_id: number;
    lead_id?: number | null;
    referral_partner_id: number;
    driver_id: number;
    vehicle_id: number;
    attribution_status: 'ACTIVE' | 'CHANGED' | 'CANCELLED';
    attributed_at: string;
    changed_at?: string | null;
    change_reason?: string | null;
    created_by?: number | null;
    referral_code?: ReferralCode | null;
    driver?: Driver | null;
    vehicle?: Vehicle | null;
    referral_partner?: ReferralPartner | null;
    creator?: User | null;
}

export interface LeadNote {
    id: number;
    lead_id: number;
    user_id: number;
    note: string;
    created_at: string;
    user?: User | null;
}

export interface Lead {
    id: number;
    customer_id: number;
    telegram_user_id?: number | null;
    referral_attribution_id?: number | null;
    title: string;
    description?: string | null;
    service_type?: string | null;
    status: 'NEW' | 'CONTACTED' | 'QUOTATION' | 'CONFIRMED' | 'LOST' | 'CANCELLED';
    assigned_to?: number | null;
    source: 'TELEGRAM_REFERRAL';
    created_at: string;
    updated_at: string;
    customer?: Customer | null;
    telegram_user?: TelegramUser | null;
    assigned_user?: User | null;
    referral_attribution?: ReferralAttribution | null;
    attributions?: ReferralAttribution[];
    notes?: LeadNote[];
    order?: Order | null;
}

export type OrderStatus = 'pending' | 'confirmed' | 'cancelled' | 'completed';
export type PaymentMethod = 'cash' | 'bank_transfer' | 'khqr_manual' | 'other';
export type PaymentStatus = 'unpaid' | 'partial' | 'paid' | 'refunded';
export type PaymentRecordStatus = 'paid' | 'refunded';

export interface OrderItem {
    id: number;
    order_id: number;
    description: string;
    quantity: number;
    unit_price: string | number;
    subtotal: string | number;
    notes?: string | null;
    created_at?: string;
    updated_at?: string;
}

export interface Payment {
    id: number;
    order_id: number;
    payment_reference: string;
    amount: string | number;
    currency: string;
    payment_method: PaymentMethod;
    status: PaymentRecordStatus;
    paid_at: string;
    received_by: number;
    receiver?: User | null;
    notes?: string | null;
    refunded_at?: string | null;
    refund_reason?: string | null;
    refunded_by?: number | null;
    refunder?: User | null;
    created_at: string;
    updated_at: string;
}

export interface Order {
    id: number;
    order_number: string;
    customer_id: number;
    lead_id?: number | null;
    referral_attribution_id?: number | null;
    status: OrderStatus;
    currency: string;
    subtotal: string | number;
    discount: string | number;
    total_amount: string | number;
    notes?: string | null;
    confirmed_at?: string | null;
    completed_at?: string | null;
    cancelled_at?: string | null;
    created_by: number;
    updated_by?: number | null;
    created_at: string;
    updated_at: string;
    customer?: Customer | null;
    lead?: Lead | null;
    referral_attribution?: ReferralAttribution | null;
    items?: OrderItem[];
    payments?: Payment[];
    creator?: User | null;
    updater?: User | null;
    paid_amount: number;
    outstanding_amount: number;
    payment_status: PaymentStatus;
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
            viewReferralCodes: boolean;
            createReferralCodes: boolean;
            viewLeads: boolean;
            changeAttribution: boolean;
            viewOrders: boolean;
            createOrders: boolean;
            viewPayments: boolean;
            refundPayments: boolean;
        };
    };
    flash: {
        success?: string | null;
        error?: string | null;
    };
};

