<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Bell,
    Calculator,
    Download,
    LayoutGrid,
    Plus,
} from 'lucide-vue-next';
import { computed } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { getInitials } from '@/composables/useInitials';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const props = withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage();
const auth = computed(() => page.props.auth);

const dashboardUrl = computed(() =>
    page.props.currentTeam
        ? dashboard(page.props.currentTeam.slug).url
        : '/',
);

const todayLabel = computed(() =>
    new Intl.DateTimeFormat(undefined, {
        month: '2-digit',
        day: '2-digit',
        year: 'numeric',
    }).format(new Date()),
);
</script>

<template>
    <header class="shrink-0 border-b border-emerald-950/30 bg-emerald-950 text-white">
        <div class="flex h-14 items-center gap-2 px-4 md:h-12 md:gap-3">
            <SidebarTrigger
                class="-ml-1 text-white hover:bg-white/10 hover:text-white [&_svg]:text-white"
            />
            <Link
                :href="dashboardUrl"
                class="flex min-w-0 items-center gap-2 font-semibold tracking-tight"
            >
                <span
                    class="size-2 shrink-0 rounded-full bg-emerald-400 shadow-[0_0_8px_hsl(142_71%_55%)]"
                    aria-hidden="true"
                />
                <span class="truncate">{{ page.props.name }}</span>
            </Link>

            <div class="ml-auto flex items-center gap-1 sm:gap-2">
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="hidden h-9 w-9 text-white hover:bg-white/10 hover:text-white sm:flex"
                >
                    <Download class="size-4" />
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="hidden h-9 w-9 text-white hover:bg-white/10 hover:text-white md:flex"
                >
                    <Plus class="size-4" />
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="hidden h-9 w-9 text-white hover:bg-white/10 hover:text-white lg:flex"
                >
                    <Calculator class="size-4" />
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="hidden h-9 w-9 text-white hover:bg-white/10 hover:text-white lg:flex"
                >
                    <LayoutGrid class="size-4" />
                </Button>
                <Button
                    type="button"
                    size="sm"
                    class="hidden h-8 bg-primary px-3 text-primary-foreground shadow-sm hover:bg-primary/90 sm:inline-flex"
                    as-child
                >
                    <Link :href="dashboardUrl">POS</Link>
                </Button>
                <span
                    class="hidden text-xs text-emerald-100/90 tabular-nums sm:inline"
                >
                    {{ todayLabel }}
                </span>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="h-9 w-9 text-white hover:bg-white/10 hover:text-white"
                >
                    <Bell class="size-4" />
                </Button>
                <DropdownMenu v-if="auth.user">
                    <DropdownMenuTrigger as-child>
                        <Button
                            variant="ghost"
                            size="sm"
                            class="h-9 gap-2 rounded-md px-2 text-white hover:bg-white/10 hover:text-white"
                        >
                            <Avatar class="size-7 rounded-full">
                                <AvatarImage
                                    v-if="auth.user.avatar"
                                    :src="auth.user.avatar"
                                    :alt="auth.user.name"
                                />
                                <AvatarFallback
                                    class="rounded-full bg-emerald-700 text-xs text-white"
                                >
                                    {{ getInitials(auth.user?.name) }}
                                </AvatarFallback>
                            </Avatar>
                            <span
                                class="hidden max-w-[7rem] truncate text-sm font-medium lg:inline"
                            >
                                {{ auth.user.name }}
                            </span>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-56">
                        <UserMenuContent :user="auth.user" />
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </div>
        <div
            v-if="props.breadcrumbs.length > 1"
            class="border-t border-emerald-900/60 bg-background px-4 py-2 text-muted-foreground"
        >
            <Breadcrumbs :breadcrumbs="props.breadcrumbs" />
        </div>
    </header>
</template>
