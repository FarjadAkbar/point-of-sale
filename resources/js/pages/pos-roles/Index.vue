<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Pencil, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import StandardDataTable from '@/components/StandardDataTable.vue';
import { Button } from '@/components/ui/button';
import posRoleRoutes from '@/routes/pos-roles';
import type { Team } from '@/types';

type PosRoleRow = {
    id: number;
    name: string;
    is_service_staff: boolean;
    is_locked: boolean;
};

const props = defineProps<{
    posRoles: PosRoleRow[];
}>();

defineOptions({
    layout: (p: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Roles',
                href: posRoleRoutes.index.url(p.currentTeam!.slug),
            },
        ],
    }),
});

const page = usePage();
const teamSlug = computed(
    () => (page.props.currentTeam as Team | null)?.slug ?? '',
);
const posPermissions = computed<string[]>(() => {
    const value = page.props.posPermissions;
    return Array.isArray(value) ? (value as string[]) : [];
});
const hasRolePermission = (permission: string): boolean =>
    posPermissions.value.includes(permission);
const canCreateRole = computed(() => hasRolePermission('roles.create'));
const canUpdateRole = computed(() => hasRolePermission('roles.update'));
const canDeleteRole = computed(() => hasRolePermission('roles.delete'));
const showActionColumn = computed(
    () => canUpdateRole.value || canDeleteRole.value,
);
const search = ref('');
const perPage = ref('15');

function remove(row: PosRoleRow) {
    if (!canDeleteRole.value) {
        return;
    }

    if (
        !confirm(
            `Delete role "${row.name}"? This cannot be undone.`,
        )
    ) {
        return;
    }
    router.delete(
        posRoleRoutes.destroy.url({
            current_team: teamSlug.value,
            pos_role: row.id,
        }),
    );
}
</script>

<template>
    <Head title="Roles" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Roles</h1>
                <p class="text-muted-foreground text-sm">
                    Define POS permissions for staff (Ultimate POS–style).
                </p>
            </div>
            <Button v-if="canCreateRole" as-child>
                <Link :href="posRoleRoutes.create.url(teamSlug)">
                    Add role
                </Link>
            </Button>
        </div>

        <StandardDataTable
            v-model:search="search"
            v-model:per-page="perPage"
            :show-search="false"
            :show-per-page="false"
            :show-pagination="false"
        >
            <table class="w-full min-w-[640px] border-collapse text-sm">
                <thead>
                    <tr class="border-b border-border bg-muted/40">
                        <th class="px-3 py-2 text-left font-medium">Name</th>
                        <th class="px-3 py-2 text-left font-medium">
                            Service staff
                        </th>
                        <th class="px-3 py-2 text-left font-medium">Locked</th>
                        <th
                            v-if="showActionColumn"
                            class="px-3 py-2 text-right font-medium"
                        >
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in posRoles"
                        :key="row.id"
                        class="border-b border-border/80 hover:bg-muted/20"
                    >
                        <td class="px-3 py-2 font-medium">{{ row.name }}</td>
                        <td class="px-3 py-2">
                            {{ row.is_service_staff ? 'Yes' : 'No' }}
                        </td>
                        <td class="px-3 py-2">
                            {{ row.is_locked ? 'Yes' : 'No' }}
                        </td>
                        <td
                            v-if="showActionColumn"
                            class="px-3 py-2 text-right"
                        >
                            <div class="flex justify-end gap-1">
                                <Button
                                    v-if="!row.is_locked && canUpdateRole"
                                    variant="ghost"
                                    size="icon"
                                    class="size-8"
                                    as-child
                                >
                                    <Link
                                        :href="
                                            posRoleRoutes.edit.url({
                                                current_team: teamSlug,
                                                pos_role: row.id,
                                            })
                                        "
                                    >
                                        <Pencil class="size-4" />
                                    </Link>
                                </Button>
                                <Button
                                    v-if="!row.is_locked && canDeleteRole"
                                    variant="ghost"
                                    size="icon"
                                    class="text-destructive size-8"
                                    type="button"
                                    @click="remove(row)"
                                >
                                    <Trash2 class="size-4" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </StandardDataTable>
    </div>
</template>
