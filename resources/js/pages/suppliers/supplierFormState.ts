import type { ContactPersonForm } from '@/pages/suppliers/SupplierForm.vue';

export function emptySupplierPerson(): ContactPersonForm {
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

export function mapSupplierPerson(p: Record<string, unknown>): ContactPersonForm {
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

/** Default shape for supplier create / edit `useForm` initial state. */
export function supplierFormFields() {
    return {
        contact_type: 'individual',
        supplier_code: '',
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
        contact_persons: [emptySupplierPerson()],
    };
}

export function applyEditingSupplier(
    form: object,
    s: Record<string, unknown> & {
        id: number;
        contact_persons?: unknown;
        assigned_to_users?: unknown;
    },
) {
    const f = form as Record<string, unknown>;
    f.contact_type = String(s.contact_type ?? 'individual');
    f.supplier_code = String(s.supplier_code ?? '');
    f.business_name = String(s.business_name ?? '');
    f.prefix = String(s.prefix ?? '');
    f.first_name = String(s.first_name ?? '');
    f.middle_name = String(s.middle_name ?? '');
    f.last_name = String(s.last_name ?? '');
    f.mobile = String(s.mobile ?? '');
    f.alternate_number = String(s.alternate_number ?? '');
    f.landline = String(s.landline ?? '');
    f.email = String(s.email ?? '');
    f.dob = String(s.dob ?? '');
    f.tax_number = String(s.tax_number ?? '');
    f.opening_balance = String(s.opening_balance ?? '0');
    f.pay_term_number =
        s.pay_term_number !== null && s.pay_term_number !== undefined
            ? String(s.pay_term_number)
            : '';
    f.pay_term_type = String(s.pay_term_type ?? '');
    f.address_line_1 = String(s.address_line_1 ?? '');
    f.address_line_2 = String(s.address_line_2 ?? '');
    f.city = String(s.city ?? '');
    f.state = String(s.state ?? '');
    f.country = String(s.country ?? '');
    f.zip_code = String(s.zip_code ?? '');
    f.land_mark = String(s.land_mark ?? '');
    f.street_name = String(s.street_name ?? '');
    f.building_number = String(s.building_number ?? '');
    f.additional_number = String(s.additional_number ?? '');
    f.shipping_address = String(s.shipping_address ?? '');
    f.custom_field1 = String(s.custom_field1 ?? '');
    f.custom_field2 = String(s.custom_field2 ?? '');
    f.custom_field3 = String(s.custom_field3 ?? '');
    f.custom_field4 = String(s.custom_field4 ?? '');
    f.custom_field5 = String(s.custom_field5 ?? '');
    f.custom_field6 = String(s.custom_field6 ?? '');
    f.custom_field7 = String(s.custom_field7 ?? '');
    f.custom_field8 = String(s.custom_field8 ?? '');
    f.custom_field9 = String(s.custom_field9 ?? '');
    f.custom_field10 = String(s.custom_field10 ?? '');
    f.assigned_to_users = [...(s.assigned_to_users as number[] | undefined ?? [])];
    const persons = s.contact_persons as Record<string, unknown>[] | undefined;
    f.contact_persons = persons?.length
        ? persons.map(mapSupplierPerson)
        : [emptySupplierPerson()];
}

export function transformSupplierSubmit(data: ReturnType<typeof supplierFormFields>) {
    return {
        ...data,
        supplier_code: data.supplier_code?.trim() || null,
        pay_term_number:
            data.pay_term_number === '' || data.pay_term_number === null
                ? null
                : Number(data.pay_term_number),
        contact_persons: data.contact_persons.filter((p) =>
            (p.first_name ?? '').trim(),
        ),
    };
}
