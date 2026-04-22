<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import teamUserRoutes from '@/routes/team-users';
import type { Team } from '@/types';

const NONE = '__none__';

type Profile = {
    dob?: string;
    gender?: string;
    marital_status?: string;
    contact_number?: string;
    permanent_address?: string;
    current_address?: string;
    bank_details?: {
        account_holder_name?: string;
        account_number?: string;
        bank_name?: string;
    };
};

const props = defineProps<{
    user: {
        id: number;
        first_name: string;
        last_name: string;
        email: string;
        username: string | null;
        is_active: boolean;
        team_role: string;
    };
    settings: Record<string, unknown>;
    pos_role_id: number | null;
    posRoles: { id: number; name: string; is_locked: boolean }[];
    locations: { id: number; name: string }[];
}>();

defineOptions({
    layout: (p: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Users',
                href: teamUserRoutes.index.url(p.currentTeam!.slug),
            },
            { title: 'Edit user', href: '#' },
        ],
    }),
});

const page = usePage();
const teamSlug = computed(
    () => (page.props.currentTeam as Team | null)?.slug ?? '',
);

const s = props.settings as {
    prefix?: string;
    is_enable_service_staff_pin?: boolean;
    allow_login?: boolean;
    access_all_locations?: boolean;
    location_ids?: number[];
    cmmsn_percent?: string | number | null;
    max_sales_discount_percent?: string | number | null;
    selected_contacts?: boolean;
    profile?: Profile;
};

const prof = s.profile ?? {};
const bd = prof.bank_details ?? {};

const form = useForm({
    prefix: (s.prefix as string) ?? '',
    first_name: props.user.first_name,
    last_name: props.user.last_name,
    email: props.user.email,
    password: '',
    password_confirmation: '',
    username: props.user.username ?? '',
    is_active: props.user.is_active,
    is_enable_service_staff_pin: s.is_enable_service_staff_pin ?? false,
    service_staff_pin: '',
    allow_login: s.allow_login ?? true,
    pos_role_id: props.pos_role_id ?? props.posRoles[0]?.id ?? 0,
    access_all_locations: s.access_all_locations ?? true,
    location_ids: [...(s.location_ids ?? [])],
    cmmsn_percent:
        s.cmmsn_percent === null || s.cmmsn_percent === undefined
            ? ''
            : String(s.cmmsn_percent),
    max_sales_discount_percent:
        s.max_sales_discount_percent === null ||
        s.max_sales_discount_percent === undefined
            ? ''
            : String(s.max_sales_discount_percent),
    selected_contacts: s.selected_contacts ?? false,
    profile: {
        dob: prof.dob ?? '',
        gender: prof.gender && prof.gender !== '' ? prof.gender : NONE,
        marital_status:
            prof.marital_status && prof.marital_status !== ''
                ? prof.marital_status
                : NONE,
        contact_number: prof.contact_number ?? '',
        permanent_address: prof.permanent_address ?? '',
        current_address: prof.current_address ?? '',
        bank_details: {
            account_holder_name: bd.account_holder_name ?? '',
            account_number: bd.account_number ?? '',
            bank_name: bd.bank_name ?? '',
        },
    },
});

watch(
    () => form.access_all_locations,
    (v) => {
        if (v) {
            form.location_ids = [];
        }
    },
);

watch(
    () => form.is_enable_service_staff_pin,
    (v) => {
        if (!v) {
            form.service_staff_pin = '';
        }
    },
);

function toggleLocation(id: number, checked: boolean) {
    const set = new Set(form.location_ids);
    if (checked) {
        set.add(id);
    } else {
        set.delete(id);
    }
    form.location_ids = Array.from(set);
}

function locationChecked(id: number): boolean {
    return form.location_ids.includes(id);
}

function submit() {
    form
        .transform((data) => {
            const payload: Record<string, unknown> = {
                ...data,
                pos_role_id: Number(data.pos_role_id),
                cmmsn_percent:
                    data.cmmsn_percent === '' ? null : data.cmmsn_percent,
                max_sales_discount_percent:
                    data.max_sales_discount_percent === ''
                        ? null
                        : data.max_sales_discount_percent,
                profile: {
                    ...data.profile,
                    gender:
                        data.profile.gender === NONE
                            ? ''
                            : data.profile.gender,
                    marital_status:
                        data.profile.marital_status === NONE
                            ? ''
                            : data.profile.marital_status,
                },
            };
            if (!data.password) {
                delete payload.password;
                delete payload.password_confirmation;
            }
            return payload;
        })
        .put(
            teamUserRoutes.update.url({
                current_team: teamSlug.value,
                user: props.user.id,
            }),
        );
}
</script>

<template>
    <Head title="Edit user" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Edit user</h1>
                <p class="text-muted-foreground text-sm">
                    Update profile, login, POS role, and locations.
                </p>
            </div>
            <Button variant="outline" as-child>
                <Link :href="teamUserRoutes.index.url(teamSlug)">
                    Back to list
                </Link>
            </Button>
        </div>

        <form class="mx-auto flex w-full max-w-4xl flex-col gap-6" @submit.prevent="submit">
            <section class="bg-card rounded-xl border border-border p-4 shadow-sm">
                <h2 class="mb-4 text-base font-semibold">Basic</h2>
                <div class="grid gap-4 sm:grid-cols-12">
                    <div class="sm:col-span-2">
                        <Label for="prefix">Prefix</Label>
                        <Input
                            id="prefix"
                            v-model="form.prefix"
                            class="mt-1"
                            placeholder="Mr / Mrs"
                        />
                    </div>
                    <div class="sm:col-span-5">
                        <Label for="first_name">First name *</Label>
                        <Input
                            id="first_name"
                            v-model="form.first_name"
                            class="mt-1"
                            required
                        />
                    </div>
                    <div class="sm:col-span-5">
                        <Label for="last_name">Last name</Label>
                        <Input
                            id="last_name"
                            v-model="form.last_name"
                            class="mt-1"
                        />
                    </div>
                    <div class="sm:col-span-6">
                        <Label for="email">Email *</Label>
                        <Input
                            id="email"
                            v-model="form.email"
                            class="mt-1"
                            type="email"
                            required
                        />
                        <p
                            v-if="form.errors.email"
                            class="text-destructive mt-1 text-sm"
                        >
                            {{ form.errors.email }}
                        </p>
                    </div>
                    <div class="flex items-end gap-2 sm:col-span-6">
                        <div class="flex items-center gap-2 pb-2">
                            <Checkbox
                                id="is_active"
                                :checked="form.is_active"
                                @update:checked="
                                    (v) => (form.is_active = v === true)
                                "
                            />
                            <Label for="is_active" class="cursor-pointer">
                                Is active
                            </Label>
                        </div>
                    </div>
                    <div class="sm:col-span-12 flex flex-wrap items-center gap-4">
                        <div class="flex items-center gap-2">
                            <Checkbox
                                id="pin_enable"
                                :checked="form.is_enable_service_staff_pin"
                                @update:checked="
                                    (v) =>
                                        (form.is_enable_service_staff_pin =
                                            v === true)
                                "
                            />
                            <Label for="pin_enable" class="cursor-pointer">
                                Enable service staff PIN
                            </Label>
                        </div>
                        <div v-if="form.is_enable_service_staff_pin" class="max-w-xs flex-1">
                            <Label for="staff_pin">New staff PIN</Label>
                            <Input
                                id="staff_pin"
                                v-model="form.service_staff_pin"
                                class="mt-1"
                                type="password"
                                autocomplete="new-password"
                                placeholder="Leave blank to keep current"
                            />
                        </div>
                    </div>
                </div>
            </section>

            <section class="bg-card rounded-xl border border-border p-4 shadow-sm">
                <h2 class="mb-4 text-base font-semibold">Roles and permissions</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="allow_login"
                            :checked="form.allow_login"
                            @update:checked="
                                (v) => (form.allow_login = v === true)
                            "
                        />
                        <Label for="allow_login" class="cursor-pointer">
                            Allow login
                        </Label>
                    </div>
                </div>
                <div
                    v-if="form.allow_login"
                    class="mt-4 grid gap-4 sm:grid-cols-3"
                >
                    <div>
                        <Label for="username">Username</Label>
                        <Input
                            id="username"
                            v-model="form.username"
                            class="mt-1"
                            autocomplete="username"
                        />
                    </div>
                    <div>
                        <Label for="password">New password</Label>
                        <Input
                            id="password"
                            v-model="form.password"
                            class="mt-1"
                            type="password"
                            autocomplete="new-password"
                            placeholder="Leave blank to keep"
                        />
                    </div>
                    <div>
                        <Label for="password_confirmation">
                            Confirm new password
                        </Label>
                        <Input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            class="mt-1"
                            type="password"
                            autocomplete="new-password"
                        />
                    </div>
                </div>

                <div class="mt-6 grid gap-2 sm:max-w-md">
                    <Label>POS role *</Label>
                    <Select
                        :model-value="String(form.pos_role_id)"
                        @update:model-value="(v) => (form.pos_role_id = Number(v))"
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Select role" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="r in posRoles"
                                :key="r.id"
                                :value="String(r.id)"
                            >
                                {{ r.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="mt-6">
                    <h3 class="mb-2 text-sm font-medium">Access locations</h3>
                    <div class="mb-3 flex items-center gap-2">
                        <Checkbox
                            id="all_locs"
                            :checked="form.access_all_locations"
                            @update:checked="
                                (v) => (form.access_all_locations = v === true)
                            "
                        />
                        <Label for="all_locs" class="cursor-pointer">
                            All locations
                        </Label>
                    </div>
                    <div
                        v-if="!form.access_all_locations"
                        class="grid gap-2"
                    >
                        <label
                            v-for="loc in locations"
                            :key="loc.id"
                            class="flex items-center gap-2 text-sm"
                        >
                            <Checkbox
                                :checked="locationChecked(loc.id)"
                                @update:checked="
                                    (v) =>
                                        toggleLocation(loc.id, v === true)
                                "
                            />
                            {{ loc.name }}
                        </label>
                    </div>
                </div>
            </section>

            <section class="bg-card rounded-xl border border-border p-4 shadow-sm">
                <h2 class="mb-4 text-base font-semibold">Sales</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <Label for="cmmsn">Sales commission %</Label>
                        <Input
                            id="cmmsn"
                            v-model="form.cmmsn_percent"
                            class="mt-1"
                            inputmode="decimal"
                        />
                    </div>
                    <div>
                        <Label for="max_disc">Max sales discount %</Label>
                        <Input
                            id="max_disc"
                            v-model="form.max_sales_discount_percent"
                            class="mt-1"
                            inputmode="decimal"
                        />
                    </div>
                    <div class="flex items-center gap-2 sm:col-span-2">
                        <Checkbox
                            id="sel_contacts"
                            :checked="form.selected_contacts"
                            @update:checked="
                                (v) =>
                                    (form.selected_contacts = v === true)
                            "
                        />
                        <Label for="sel_contacts" class="cursor-pointer">
                            Allow selected contacts only
                        </Label>
                    </div>
                </div>
            </section>

            <section class="bg-card rounded-xl border border-border p-4 shadow-sm">
                <h2 class="mb-4 text-base font-semibold">More information</h2>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <Label for="dob">Date of birth</Label>
                        <Input
                            id="dob"
                            v-model="form.profile.dob"
                            class="mt-1"
                            type="date"
                        />
                    </div>
                    <div>
                        <Label for="gender">Gender</Label>
                        <Select v-model="form.profile.gender">
                            <SelectTrigger id="gender" class="mt-1">
                                <SelectValue placeholder="Select" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="NONE">—</SelectItem>
                                <SelectItem value="male">Male</SelectItem>
                                <SelectItem value="female">Female</SelectItem>
                                <SelectItem value="others">Others</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <Label for="marital">Marital status</Label>
                        <Select v-model="form.profile.marital_status">
                            <SelectTrigger id="marital" class="mt-1">
                                <SelectValue placeholder="Select" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="NONE">—</SelectItem>
                                <SelectItem value="married">Married</SelectItem>
                                <SelectItem value="unmarried">
                                    Unmarried
                                </SelectItem>
                                <SelectItem value="divorced">Divorced</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <Label for="mobile">Mobile</Label>
                        <Input
                            id="mobile"
                            v-model="form.profile.contact_number"
                            class="mt-1"
                        />
                    </div>
                    <div class="sm:col-span-3">
                        <Label for="perm_addr">Permanent address</Label>
                        <textarea
                            id="perm_addr"
                            v-model="form.profile.permanent_address"
                            class="border-input bg-background mt-1 min-h-[72px] w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            rows="2"
                        />
                    </div>
                    <div class="sm:col-span-3">
                        <Label for="curr_addr">Current address</Label>
                        <textarea
                            id="curr_addr"
                            v-model="form.profile.current_address"
                            class="border-input bg-background mt-1 min-h-[72px] w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            rows="2"
                        />
                    </div>
                    <div>
                        <Label for="bank_holder">Account holder</Label>
                        <Input
                            id="bank_holder"
                            v-model="form.profile.bank_details.account_holder_name"
                            class="mt-1"
                        />
                    </div>
                    <div>
                        <Label for="bank_acct">Account number</Label>
                        <Input
                            id="bank_acct"
                            v-model="form.profile.bank_details.account_number"
                            class="mt-1"
                        />
                    </div>
                    <div>
                        <Label for="bank_nm">Bank name</Label>
                        <Input
                            id="bank_nm"
                            v-model="form.profile.bank_details.bank_name"
                            class="mt-1"
                        />
                    </div>
                </div>
            </section>

            <div class="flex flex-wrap gap-2">
                <Button type="submit" :disabled="form.processing">
                    Save
                </Button>
                <Button type="button" variant="outline" as-child>
                    <Link :href="teamUserRoutes.index.url(teamSlug)">
                        Cancel
                    </Link>
                </Button>
            </div>
        </form>
    </div>
</template>
