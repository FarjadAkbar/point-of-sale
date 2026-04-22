<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Pencil, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import StandardDataTable from '@/components/StandardDataTable.vue';
import { Button } from '@/components/ui/button';
import teamUserRoutes from '@/routes/team-users';
import type { Team } from '@/types';

type Row = {
    membership_id: number;
    user_id: number;
    first_name: string;
    last_name: string | null;
    email: string;
    is_active: boolean;
    team_role: string;
    pos_role: { id: number; name: string } | null;
};

type Paginated = {
    data: Row[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

const props = defineProps<{
    memberships: Paginated;
}>();

defineOptions({
    layout: (p: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Users',
                href: teamUserRoutes.index.url(p.currentTeam!.slug),
            },
        ],
    }),
});

const page = usePage();
const teamSlug = computed(
    () => (page.props.currentTeam as Team | null)?.slug ?? '',
);
const search = ref('');
const perPage = ref(String(props.memberships?.per_page ?? 15));

function goToPage(url: string | null) {
    if (url) {
        router.visit(url, { preserveState: true, replace: true });
    }
}

function remove(row: Row) {
    if (row.team_role === 'owner') {
        return;
    }
    if (
        !confirm(
            `Remove ${row.first_name} ${row.last_name ?? ''} from this team?`,
        )
    ) {
        return;
    }
    router.delete(
        teamUserRoutes.destroy.url({
            current_team: teamSlug.value,
            user: row.user_id,
        }),
    );
}
</script>

<template>
    <Head title="Users" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Users</h1>
                <p class="text-muted-foreground text-sm">
                    Team members, login, and POS roles.
                </p>
            </div>
            <Button as-child>
                <Link :href="teamUserRoutes.create.url(teamSlug)">
                    Add user
                </Link>
            </Button>
        </div>

        <StandardDataTable
            v-model:search="search"
            v-model:per-page="perPage"
            :show-search="false"
            :show-per-page="false"
            :paginator="memberships"
            @page="goToPage"
        >
            <table class="w-full min-w-[720px] border-collapse text-sm">
                <thead>
                    <tr class="border-b border-border bg-muted/40">
                        <th class="px-3 py-2 text-left font-medium">Name</th>
                        <th class="px-3 py-2 text-left font-medium">Email</th>
                        <th class="px-3 py-2 text-left font-medium">Active</th>
                        <th class="px-3 py-2 text-left font-medium">POS role</th>
                        <th class="px-3 py-2 text-left font-medium">Team role</th>
                        <th class="px-3 py-2 text-right font-medium">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in memberships.data ?? []"
                        :key="row.membership_id"
                        class="border-b border-border/80 hover:bg-muted/20"
                    >
                        <td class="px-3 py-2">
                            {{ row.first_name }} {{ row.last_name ?? '' }}
                        </td>
                        <td class="px-3 py-2">{{ row.email }}</td>
                        <td class="px-3 py-2">
                            {{ row.is_active ? 'Yes' : 'No' }}
                        </td>
                        <td class="px-3 py-2">
                            {{ row.pos_role?.name ?? '—' }}
                        </td>
                        <td class="px-3 py-2 capitalize">
                            {{ row.team_role }}
                        </td>
                        <td class="px-3 py-2 text-right">
                            <div class="flex justify-end gap-1">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="size-8"
                                    as-child
                                >
                                    <Link
                                        :href="
                                            teamUserRoutes.edit.url({
                                                current_team: teamSlug,
                                                user: row.user_id,
                                            })
                                        "
                                    >
                                        <Pencil class="size-4" />
                                    </Link>
                                </Button>
                                <Button
                                    v-if="row.team_role !== 'owner'"
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
