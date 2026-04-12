<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import { Plus, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

export type ContactPersonForm = {
    user_id: number | null;
    surname: string;
    first_name: string;
    last_name: string;
    email: string;
    contact_no: string;
    alt_number: string;
    family_number: string;
    crm_department: string;
    crm_designation: string;
    cmmsn_percent: string;
    allow_login: boolean;
    username: string;
    password: string;
    is_active: boolean;
};

export type SupplierFormData = {
    contact_type: string;
    supplier_code: string;
    business_name: string;
    prefix: string;
    first_name: string;
    middle_name: string;
    last_name: string;
    mobile: string;
    alternate_number: string;
    landline: string;
    email: string;
    dob: string;
    tax_number: string;
    opening_balance: string;
    pay_term_number: string;
    pay_term_type: string;
    address_line_1: string;
    address_line_2: string;
    city: string;
    state: string;
    country: string;
    zip_code: string;
    land_mark: string;
    street_name: string;
    building_number: string;
    additional_number: string;
    shipping_address: string;
    custom_field1: string;
    custom_field2: string;
    custom_field3: string;
    custom_field4: string;
    custom_field5: string;
    custom_field6: string;
    custom_field7: string;
    custom_field8: string;
    custom_field9: string;
    custom_field10: string;
    assigned_to_users: number[];
    contact_persons: ContactPersonForm[];
};

const props = withDefaults(
    defineProps<{
        form: InertiaForm<SupplierFormData>;
        teamMembers?: { id: number; name: string; email: string }[];
    }>(),
    {
        teamMembers: () => [],
    },
);

const isBusiness = computed(() => props.form.contact_type === 'business');
const isIndividual = computed(() => props.form.contact_type === 'individual');

function emptyPerson(): ContactPersonForm {
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

function addContactPerson() {
    if (props.form.contact_persons.length >= 5) {
        return;
    }

    props.form.contact_persons.push(emptyPerson());
}

function removeContactPerson(i: number) {
    props.form.contact_persons.splice(i, 1);

    if (!props.form.contact_persons.length) {
        props.form.contact_persons.push(emptyPerson());
    }
}

function toggleAssigned(id: number, checked: boolean) {
    const set = new Set(props.form.assigned_to_users);

    if (checked) {
        set.add(id);
    } else {
        set.delete(id);
    }

    props.form.assigned_to_users = [...set];
}

function isAssigned(id: number): boolean {
    return props.form.assigned_to_users.includes(id);
}
</script>

<template>
    <div class="grid gap-6">
        <div class="grid gap-4 md:grid-cols-2">
            <div class="grid gap-2">
                <Label>Contact type</Label>
                <Select v-model="form.contact_type">
                    <SelectTrigger>
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="individual">Individual</SelectItem>
                        <SelectItem value="business">Business</SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <div class="grid gap-2">
                <Label for="supplier_code">Contact ID</Label>
                <Input
                    id="supplier_code"
                    v-model="form.supplier_code"
                    placeholder="Leave empty to autogenerate"
                />
            </div>
        </div>

        <div v-if="isBusiness" class="grid gap-2 md:max-w-md">
            <Label for="business_name">Business name</Label>
            <Input
                id="business_name"
                v-model="form.business_name"
                placeholder="Business name"
            />
        </div>

        <div
            v-if="isIndividual"
            class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
        >
            <div class="grid gap-2">
                <Label for="prefix">Prefix</Label>
                <Input
                    id="prefix"
                    v-model="form.prefix"
                    placeholder="Mr / Mrs / Miss"
                />
            </div>
            <div class="grid gap-2">
                <Label for="first_name">First name *</Label>
                <Input
                    id="first_name"
                    v-model="form.first_name"
                    required
                    placeholder="First name"
                />
            </div>
            <div class="grid gap-2">
                <Label for="middle_name">Middle name</Label>
                <Input
                    id="middle_name"
                    v-model="form.middle_name"
                    placeholder="Middle name"
                />
            </div>
            <div class="grid gap-2">
                <Label for="last_name">Last name</Label>
                <Input
                    id="last_name"
                    v-model="form.last_name"
                    placeholder="Last name"
                />
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="grid gap-2">
                <Label for="mobile">Mobile *</Label>
                <Input
                    id="mobile"
                    v-model="form.mobile"
                    required
                    placeholder="Mobile"
                />
            </div>
            <div class="grid gap-2">
                <Label for="alternate_number">Alternate number</Label>
                <Input
                    id="alternate_number"
                    v-model="form.alternate_number"
                    placeholder="Alternate contact number"
                />
            </div>
            <div class="grid gap-2">
                <Label for="landline">Landline</Label>
                <Input
                    id="landline"
                    v-model="form.landline"
                    placeholder="Landline"
                />
            </div>
            <div class="grid gap-2">
                <Label for="email">Email</Label>
                <Input
                    id="email"
                    v-model="form.email"
                    type="email"
                    placeholder="Email"
                />
            </div>
        </div>

        <div v-if="isIndividual" class="grid gap-2 md:max-w-xs">
            <Label for="dob">Date of birth</Label>
            <Input id="dob" v-model="form.dob" type="date" />
        </div>

        <div class="grid gap-2">
            <Label>Assigned to</Label>
            <div
                class="max-h-40 space-y-2 overflow-y-auto rounded-md border border-input p-3"
            >
                <div
                    v-for="m in teamMembers"
                    :key="m.id"
                    class="flex items-center gap-2"
                >
                    <Checkbox
                        :id="`u-${m.id}`"
                        :model-value="isAssigned(m.id)"
                        @update:model-value="
                            (v) => toggleAssigned(m.id, v === true)
                        "
                    />
                    <Label :for="`u-${m.id}`" class="font-normal">
                        {{ m.name }}
                        <span class="text-muted-foreground">({{ m.email }})</span>
                    </Label>
                </div>
            </div>
        </div>

        <Collapsible class="space-y-2">
            <CollapsibleTrigger as-child>
                <Button type="button" variant="outline" size="sm">
                    More information ▼
                </Button>
            </CollapsibleTrigger>
            <CollapsibleContent class="space-y-4 pt-2">
                <div class="grid gap-4 md:grid-cols-3">
                    <div class="grid gap-2">
                        <Label for="tax_number">Tax number</Label>
                        <Input
                            id="tax_number"
                            v-model="form.tax_number"
                            placeholder="Tax number"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="opening_balance">Opening balance</Label>
                        <Input
                            id="opening_balance"
                            v-model="form.opening_balance"
                            type="text"
                            inputmode="decimal"
                        />
                    </div>
                    <div class="grid gap-2 md:col-span-1">
                        <Label>Pay term</Label>
                        <div class="flex gap-2">
                            <Input
                                v-model="form.pay_term_number"
                                type="number"
                                min="0"
                                placeholder="Number"
                                class="w-24"
                            />
                            <Select v-model="form.pay_term_type">
                                <SelectTrigger class="flex-1">
                                    <SelectValue placeholder="Period" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="months">Months</SelectItem>
                                    <SelectItem value="days">Days</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="address_line_1">Address line 1</Label>
                        <Input
                            id="address_line_1"
                            v-model="form.address_line_1"
                            placeholder="Address line 1"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="address_line_2">Address line 2</Label>
                        <Input
                            id="address_line_2"
                            v-model="form.address_line_2"
                            placeholder="Address line 2"
                        />
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="grid gap-2">
                        <Label for="city">City</Label>
                        <Input id="city" v-model="form.city" placeholder="City" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="state">State</Label>
                        <Input
                            id="state"
                            v-model="form.state"
                            placeholder="State"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="country">Country</Label>
                        <Input
                            id="country"
                            v-model="form.country"
                            placeholder="Country"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="zip_code">Zip code</Label>
                        <Input
                            id="zip_code"
                            v-model="form.zip_code"
                            placeholder="Zip / Postal code"
                        />
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="grid gap-2">
                        <Label for="land_mark">Landmark</Label>
                        <Input
                            id="land_mark"
                            v-model="form.land_mark"
                            placeholder="Landmark"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="street_name">Street name</Label>
                        <Input
                            id="street_name"
                            v-model="form.street_name"
                            placeholder="Street name"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="building_number">Building number</Label>
                        <Input
                            id="building_number"
                            v-model="form.building_number"
                            placeholder="Building number"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="additional_number">Additional number</Label>
                        <Input
                            id="additional_number"
                            v-model="form.additional_number"
                            placeholder="Additional number"
                        />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="shipping_address">Shipping address</Label>
                    <textarea
                        id="shipping_address"
                        v-model="form.shipping_address"
                        rows="2"
                        class="border-input bg-background min-h-[60px] w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none"
                        placeholder="Shipping address notes"
                    />
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <div class="grid gap-2">
                        <Label for="cf1">Custom field 1</Label>
                        <Input id="cf1" v-model="form.custom_field1" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="cf2">Custom field 2</Label>
                        <Input id="cf2" v-model="form.custom_field2" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="cf3">Custom field 3</Label>
                        <Input id="cf3" v-model="form.custom_field3" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="cf4">Custom field 4</Label>
                        <Input id="cf4" v-model="form.custom_field4" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="cf5">Custom field 5</Label>
                        <Input id="cf5" v-model="form.custom_field5" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="cf6">Custom field 6</Label>
                        <Input id="cf6" v-model="form.custom_field6" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="cf7">Custom field 7</Label>
                        <Input id="cf7" v-model="form.custom_field7" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="cf8">Custom field 8</Label>
                        <Input id="cf8" v-model="form.custom_field8" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="cf9">Custom field 9</Label>
                        <Input id="cf9" v-model="form.custom_field9" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="cf10">Custom field 10</Label>
                        <Input id="cf10" v-model="form.custom_field10" />
                    </div>
                </div>
            </CollapsibleContent>
        </Collapsible>

        <Collapsible class="space-y-2">
            <CollapsibleTrigger as-child>
                <Button type="button" variant="outline" size="sm">
                    Contact persons ▼
                </Button>
            </CollapsibleTrigger>
            <CollapsibleContent class="space-y-6 pt-2">
                <div
                    v-for="(person, i) in form.contact_persons"
                    :key="i"
                    class="space-y-4 rounded-lg border border-border p-4"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium">Person {{ i + 1 }}</span>
                        <Button
                            v-if="form.contact_persons.length > 1"
                            type="button"
                            variant="ghost"
                            size="icon"
                            @click="removeContactPerson(i)"
                        >
                            <Trash2 class="size-4" />
                        </Button>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="grid gap-2">
                            <Label :for="`p${i}-s`">Prefix</Label>
                            <Input
                                :id="`p${i}-s`"
                                v-model="person.surname"
                                placeholder="Mr / Mrs / Miss"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label :for="`p${i}-fn`">First name *</Label>
                            <Input
                                :id="`p${i}-fn`"
                                v-model="person.first_name"
                                placeholder="First name"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label :for="`p${i}-ln`">Last name</Label>
                            <Input
                                :id="`p${i}-ln`"
                                v-model="person.last_name"
                                placeholder="Last name"
                            />
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div v-if="!person.allow_login" class="grid gap-2">
                            <Label :for="`p${i}-em`">Email</Label>
                            <Input
                                :id="`p${i}-em`"
                                v-model="person.email"
                                type="email"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label :for="`p${i}-mo`">Mobile</Label>
                            <Input
                                :id="`p${i}-mo`"
                                v-model="person.contact_no"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label :for="`p${i}-alt`">Alternate number</Label>
                            <Input :id="`p${i}-alt`" v-model="person.alt_number" />
                        </div>
                        <div class="grid gap-2">
                            <Label :for="`p${i}-fam`">Family number</Label>
                            <Input
                                :id="`p${i}-fam`"
                                v-model="person.family_number"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label :for="`p${i}-dep`">Department</Label>
                            <Input
                                :id="`p${i}-dep`"
                                v-model="person.crm_department"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label :for="`p${i}-des`">Designation</Label>
                            <Input
                                :id="`p${i}-des`"
                                v-model="person.crm_designation"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label :for="`p${i}-cm`">Commission %</Label>
                            <Input
                                :id="`p${i}-cm`"
                                v-model="person.cmmsn_percent"
                                inputmode="decimal"
                            />
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-4">
                        <div class="flex items-center gap-2">
                            <Checkbox
                                :id="`p${i}-login`"
                                :checked="person.allow_login"
                                @update:checked="
                                    (v: boolean | 'indeterminate') =>
                                        (person.allow_login = v === true)
                                "
                            />
                            <Label :for="`p${i}-login`" class="font-normal">
                                Allow login
                            </Label>
                        </div>
                    </div>
                    <div
                        v-if="person.allow_login"
                        class="space-y-4 rounded-lg border border-primary/20 bg-muted/20 p-4"
                    >
                        <p class="text-sm font-medium text-foreground">
                            Portal login (team role: supplier)
                        </p>
                        <p class="text-xs text-muted-foreground">
                            Sign in uses email and password. Inactive users cannot
                            log in.
                        </p>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label :for="`p${i}-em-login`">Email *</Label>
                                <Input
                                    :id="`p${i}-em-login`"
                                    v-model="person.email"
                                    type="email"
                                    autocomplete="email"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label :for="`p${i}-un`">Username *</Label>
                                <Input
                                    :id="`p${i}-un`"
                                    v-model="person.username"
                                    autocomplete="username"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label :for="`p${i}-pw`">Password</Label>
                                <Input
                                    :id="`p${i}-pw`"
                                    v-model="person.password"
                                    type="password"
                                    autocomplete="new-password"
                                    :placeholder="
                                        person.user_id
                                            ? 'Leave blank to keep current'
                                            : 'Required for new login'
                                    "
                                />
                            </div>
                            <div class="flex items-center gap-2 pt-6">
                                <Checkbox
                                    :id="`p${i}-act`"
                                    :checked="person.is_active"
                                    @update:checked="
                                        (v: boolean | 'indeterminate') =>
                                            (person.is_active = v === true)
                                    "
                                />
                                <Label :for="`p${i}-act`" class="font-normal">
                                    User is active
                                </Label>
                            </div>
                        </div>
                    </div>
                </div>
                <Button
                    v-if="form.contact_persons.length < 5"
                    type="button"
                    variant="secondary"
                    size="sm"
                    @click="addContactPerson"
                >
                    <Plus class="mr-1 size-4" />
                    Add contact person
                </Button>
            </CollapsibleContent>
        </Collapsible>
    </div>
</template>
