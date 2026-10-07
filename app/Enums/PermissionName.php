<?php

declare(strict_types=1);

namespace App\Enums;

enum PermissionName: string
{
    // Phase 1: Users & Roles
    case USERS_VIEW = 'users.view';
    case USERS_CREATE = 'users.create';
    case USERS_UPDATE = 'users.update';
    case USERS_DELETE = 'users.delete';

    case ROLES_VIEW = 'roles.view';
    case ROLES_ASSIGN = 'roles.assign';

    case DASHBOARD_VIEW = 'dashboard.view';

    // Phase 2: Referral Partners
    case PARTNERS_VIEW = 'partners.view';
    case PARTNERS_CREATE = 'partners.create';
    case PARTNERS_UPDATE = 'partners.update';
    case PARTNERS_DELETE = 'partners.delete';

    // Phase 2: Drivers
    case DRIVERS_VIEW = 'drivers.view';
    case DRIVERS_CREATE = 'drivers.create';
    case DRIVERS_UPDATE = 'drivers.update';
    case DRIVERS_DELETE = 'drivers.delete';

    // Phase 2: Vehicles
    case VEHICLES_VIEW = 'vehicles.view';
    case VEHICLES_CREATE = 'vehicles.create';
    case VEHICLES_UPDATE = 'vehicles.update';
    case VEHICLES_DELETE = 'vehicles.delete';

    // Phase 3: Referral Codes & QR Engine
    case REFERRAL_CODES_VIEW = 'referral_codes.view';
    case REFERRAL_CODES_CREATE = 'referral_codes.create';
    case REFERRAL_CODES_UPDATE = 'referral_codes.update';
    case REFERRAL_CODES_DELETE = 'referral_codes.delete';
    case REFERRAL_CODES_ACTIVATE = 'referral_codes.activate';
    case REFERRAL_CODES_DEACTIVATE = 'referral_codes.deactivate';
    case REFERRAL_CODES_QR_VIEW = 'referral_codes.qr.view';
    case REFERRAL_CODES_QR_DOWNLOAD = 'referral_codes.qr.download';

    // Phase 4: Leads, Customers & Referral Attribution
    case LEADS_VIEW = 'leads.view';
    case LEADS_CREATE = 'leads.create';
    case LEADS_UPDATE = 'leads.update';
    case LEADS_ASSIGN = 'leads.assign';
    case LEADS_CHANGE_ATTRIBUTION = 'leads.change_attribution';
    case LEADS_ADD_NOTE = 'leads.add_note';
    case CUSTOMERS_VIEW = 'customers.view';

    // Phase 5: Orders & Customer Payments
    case ORDERS_VIEW = 'orders.view';
    case ORDERS_CREATE = 'orders.create';
    case ORDERS_UPDATE = 'orders.update';
    case ORDERS_CONFIRM = 'orders.confirm';
    case ORDERS_CANCEL = 'orders.cancel';
    case ORDERS_COMPLETE = 'orders.complete';
    case ORDERS_DELETE = 'orders.delete';

    case PAYMENTS_VIEW = 'payments.view';
    case PAYMENTS_CREATE = 'payments.create';
    case PAYMENTS_UPDATE = 'payments.update';
    case PAYMENTS_REFUND = 'payments.refund';

    // Phase 6: Commission Management
    case COMMISSIONS_VIEW = 'commissions.view';
    case COMMISSIONS_CREATE = 'commissions.create';
    case COMMISSIONS_UPDATE = 'commissions.update';
    case COMMISSIONS_APPROVE = 'commissions.approve';
    case COMMISSIONS_PAY = 'commissions.pay';
    case COMMISSIONS_REVERSE = 'commissions.reverse';
    case COMMISSIONS_DELETE = 'commissions.delete';
    case COMMISSIONS_REPORTS = 'commissions.reports';
    case COMMISSION_PAYMENTS_VIEW = 'commission_payments.view';
    case COMMISSION_PAYMENTS_CREATE = 'commission_payments.create';
    case COMMISSION_PAYMENTS_UPDATE = 'commission_payments.update';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
