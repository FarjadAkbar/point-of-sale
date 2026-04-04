import type { ContactPersonForm } from '@/pages/customers/CustomerForm.vue';

export function emptyCustomerPerson(): ContactPersonForm {
    return {
        user_id: null,
        surname: '',
        first_name: '',
        last_name: '',
        email: '',
        contact_no: '',
        alt_number: '',
        family_number: '',
        crm_department: '',
        crm_designation: '',
        cmmsn_percent: '',
        allow_login: false,
        username: '',
        password: '',
        is_active: true,
    };
}

export function mapCustomerPerson(p: Record<string, unknown>): ContactPersonForm {
    return {
        user_id:
            p.user_id !== null && p.user_id !== undefined && p.user_id !== ''
                ? Number(p.user_id)
                : null,
        surname: String(p.surname ?? ''),
        first_name: String(p.first_name ?? ''),
        last_name: String(p.last_name ?? ''),
        email: String(p.email ?? ''),
        contact_no: String(p.contact_no ?? ''),
        alt_number: String(p.alt_number ?? ''),
        family_number: String(p.family_number ?? ''),
        crm_department: String(p.crm_department ?? ''),
        crm_designation: String(p.crm_designation ?? ''),
        cmmsn_percent:
            p.cmmsn_percent !== null && p.cmmsn_percent !== undefined
                ? String(p.cmmsn_percent)
                : '',
        allow_login: Boolean(p.allow_login),
        username: String(p.username ?? ''),
        password: '',
        is_active: p.is_active !== false,
    };
}

export function customerFormFields() {
    return {
        party_role: 'customer',
        entity_type: 'individual',
        customer_code: '',
        customer_group_id: '__none__',
        business_name: '',
        prefix: '',
        first_name: '',
        middle_name: '',
        last_name: '',
        mobile: '',
        alternate_number: '',
        landline: '',
        email: '',
        dob: '',
        tax_number: '',
        opening_balance: '0',
        credit_limit: '',
        pay_term_number: '',
        pay_term_type: '',
        address_line_1: '',
        address_line_2: '',
        city: '',
        state: '',
        country: '',
        zip_code: '',
        land_mark: '',
        street_name: '',
        building_number: '',
        additional_number: '',
        shipping_address: '',
        custom_field1: '',
        custom_field2: '',
        custom_field3: '',
        custom_field4: '',
        custom_field5: '',
        custom_field6: '',
        custom_field7: '',
        custom_field8: '',
        custom_field9: '',
        custom_field10: '',
        assigned_to_users: [] as number[],
        contact_persons: [emptyCustomerPerson()],
    };
}

export function applyEditingCustomer(
    form: object,
    c: Record<string, unknown> & {
        id: number;
        contact_persons?: unknown;
        assigned_to_users?: unknown;
        customer_group_id?: number | null;
        credit_limit?: string | number | null;
    },
) {
    const f = form as Record<string, unknown>;
    f.party_role = String(c.party_role ?? 'customer');
    f.entity_type = String(c.entity_type ?? 'individual');
    f.customer_code = String(c.customer_code ?? '');
    f.customer_group_id =
        c.customer_group_id != null ? String(c.customer_group_id) : '__none__';
    f.business_name = String(c.business_name ?? '');
    f.prefix = String(c.prefix ?? '');
    f.first_name = String(c.first_name ?? '');
    f.middle_name = String(c.middle_name ?? '');
    f.last_name = String(c.last_name ?? '');
    f.mobile = String(c.mobile ?? '');
    f.alternate_number = String(c.alternate_number ?? '');
    f.landline = String(c.landline ?? '');
    f.email = String(c.email ?? '');
    f.dob = String(c.dob ?? '');
    f.tax_number = String(c.tax_number ?? '');
    f.opening_balance = String(c.opening_balance ?? '0');
    f.credit_limit =
        c.credit_limit !== null && c.credit_limit !== undefined
            ? String(c.credit_limit)
            : '';
    f.pay_term_number =
        c.pay_term_number !== null && c.pay_term_number !== undefined
            ? String(c.pay_term_number)
            : '';
    f.pay_term_type = String(c.pay_term_type ?? '');
    f.address_line_1 = String(c.address_line_1 ?? '');
    f.address_line_2 = String(c.address_line_2 ?? '');
    f.city = String(c.city ?? '');
    f.state = String(c.state ?? '');
    f.country = String(c.country ?? '');
    f.zip_code = String(c.zip_code ?? '');
    f.land_mark = String(c.land_mark ?? '');
    f.street_name = String(c.street_name ?? '');
    f.building_number = String(c.building_number ?? '');
    f.additional_number = String(c.additional_number ?? '');
    f.shipping_address = String(c.shipping_address ?? '');
    f.custom_field1 = String(c.custom_field1 ?? '');
    f.custom_field2 = String(c.custom_field2 ?? '');
    f.custom_field3 = String(c.custom_field3 ?? '');
    f.custom_field4 = String(c.custom_field4 ?? '');
    f.custom_field5 = String(c.custom_field5 ?? '');
    f.custom_field6 = String(c.custom_field6 ?? '');
    f.custom_field7 = String(c.custom_field7 ?? '');
    f.custom_field8 = String(c.custom_field8 ?? '');
    f.custom_field9 = String(c.custom_field9 ?? '');
    f.custom_field10 = String(c.custom_field10 ?? '');
    f.assigned_to_users = [...(c.assigned_to_users as number[] | undefined ?? [])];
    const persons = c.contact_persons as Record<string, unknown>[] | undefined;
    f.contact_persons = persons?.length
        ? persons.map(mapCustomerPerson)
        : [emptyCustomerPerson()];
}

export function transformCustomerSubmit(data: ReturnType<typeof customerFormFields>) {
    return {
        ...data,
        customer_code: data.customer_code?.trim() || null,
        customer_group_id:
            data.customer_group_id === '__none__' ||
            data.customer_group_id === '' ||
            data.customer_group_id == null
                ? null
                : Number(data.customer_group_id),
        credit_limit:
            data.credit_limit === '' || data.credit_limit == null
                ? null
                : data.credit_limit,
        pay_term_number:
            data.pay_term_number === '' || data.pay_term_number === null
                ? null
                : Number(data.pay_term_number),
        contact_persons: data.contact_persons.filter((p) =>
            (p.first_name ?? '').trim(),
        ),
    };
}
