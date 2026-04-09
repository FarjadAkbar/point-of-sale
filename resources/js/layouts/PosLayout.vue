<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import TeamSwitcher from '@/components/TeamSwitcher.vue';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { getInitials } from '@/composables/useInitials';
import { dashboard } from '@/routes';
import { computed } from 'vue';

const page = usePage();
const auth = computed(() => page.props.auth);

const dashboardUrl = computed(() =>
    page.props.currentTeam
        ? dashboard(page.props.currentTeam.slug).url
        : '/',
);
</script>

<template>
    <div
        class="bg-background text-foreground flex h-svh min-h-0 flex-col overflow-hidden"
    >
        <header
            class="border-border bg-card flex h-12 shrink-0 items-center gap-2 border-b px-2 sm:gap-3 sm:px-3"
        >
            <TeamSwitcher in-header />
            <span
                class="text-muted-foreground hidden text-xs font-medium sm:inline"
            >
                Point of sale
            </span>
            <div class="ml-auto flex items-center gap-1 sm:gap-2">
                <Button variant="outline" size="sm" class="h-8" as-child>
                    <Link :href="dashboardUrl"> Exit to app </Link>
                </Button>
                <DropdownMenu v-if="auth.user">
                    <DropdownMenuTrigger as-child>
                        <Button
                            variant="ghost"
                            size="sm"
                            class="h-9 gap-2 rounded-md px-2"
                        >
                            <Avatar class="size-7 rounded-full">
                                <AvatarImage
                                    v-if="auth.user.avatar"
                                    :src="auth.user.avatar"
                                    :alt="auth.user.name"
                                />
                                <AvatarFallback
                                    class="rounded-full bg-muted text-xs"
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
        </header>
        <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
            <slot />
        </div>
    </div>
</template>
