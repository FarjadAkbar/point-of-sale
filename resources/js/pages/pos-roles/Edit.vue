<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import PosPermissionForm, {
    type PermissionGroup,
} from '@/pages/pos-roles/PosPermissionForm.vue';
import posRoleRoutes from '@/routes/pos-roles';
import type { Team } from '@/types';

type PosRole = {
    id: number;
    name: string;
    is_service_staff: boolean;
    is_locked: boolean;
    permissions: string[] | null;
    radio_options: Record<string, string> | null;
};

const props = defineProps<{
    posRole: PosRole;
    permissionGroups: PermissionGroup[];
    defaults: {
        permissions: string[];
        radio_options: Record<string, string>;
    };
}>();

defineOptions({
    layout: (p: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Roles',
                href: posRoleRoutes.index.url(p.currentTeam!.slug),
            },
            { title: 'Edit role', href: '#' },
        ],
    }),
});

const page = usePage();
const teamSlug = computed(
    () => (page.props.currentTeam as Team | null)?.slug ?? '',
);

const form = useForm({
    name: props.posRole.name,
    is_service_staff: props.posRole.is_service_staff,
    permissions: [...(props.defaults.permissions ?? [])],
    radio_options: { ...props.defaults.radio_options },
});

function submit() {
    form
        .transform((data) => ({
            ...data,
            permissions: [...(data.permissions ?? [])],
            radio_options: { ...(data.radio_options ?? {}) },
        }))
        .put(
            posRoleRoutes.update.url({
                current_team: teamSlug.value,
                pos_role: props.posRole.id,
            }),
        );
}
</script>

<template>
    <Head :title="`Edit ${posRole.name}`" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Edit role</h1>
                <p class="text-muted-foreground text-sm">
                    Update permissions for this role.
                </p>
            </div>
            <Button variant="outline" as-child>
                <Link :href="posRoleRoutes.index.url(teamSlug)">
                    Back to list
                </Link>
            </Button>
        </div>

        <div
            v-if="posRole.is_locked"
            class="bg-muted rounded-md border border-border px-3 py-2 text-sm"
        >
            This role is locked and cannot be edited.
        </div>

        <form class="space-y-6" @submit.prevent="submit">
            <div class="grid max-w-md gap-2">
                <Label for="role-name">Role name *</Label>
                <Input
                    id="role-name"
                    v-model="form.name"
                    required
                    placeholder="Role name"
                    autocomplete="off"
                    :disabled="posRole.is_locked"
                />
                <p
                    v-if="form.errors.name"
                    class="text-destructive text-sm"
                >
                    {{ form.errors.name }}
                </p>
            </div>

            <div>
                <h2 class="mb-3 text-base font-semibold">Permissions</h2>
                <PosPermissionForm
                    :form="form"
                    :permission-groups="permissionGroups"
                />
            </div>

            <div class="flex flex-wrap gap-2">
                <Button
                    type="submit"
                    :disabled="form.processing || posRole.is_locked"
                >
                    Save
                </Button>
                <Button type="button" variant="outline" as-child>
                    <Link :href="posRoleRoutes.index.url(teamSlug)">
                        Cancel
                    </Link>
                </Button>
            </div>
        </form>
    </div>
</template>
