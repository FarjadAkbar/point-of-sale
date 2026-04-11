<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Clock, LogOut } from 'lucide-vue-next';
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
import { computed, onMounted, onUnmounted, ref } from 'vue';

const page = usePage();
const auth = computed(() => page.props.auth);

const dashboardUrl = computed(() =>
    page.props.currentTeam
        ? dashboard.url(page.props.currentTeam.slug)
        : '/',
);

const clock = ref('');
let clockInterval: ReturnType<typeof setInterval> | undefined;

onMounted(() => {
    const tick = () => {
        clock.value = new Date().toLocaleString();
    };
    tick();
    clockInterval = setInterval(tick, 1000);
});

onUnmounted(() => {
    if (clockInterval) {
        clearInterval(clockInterval);
    }
});
</script>

<template>
    <div
        class="bg-background text-foreground flex h-svh min-h-0 flex-col overflow-hidden"
    >
        <header
            class="border-border bg-card flex h-11 shrink-0 items-center gap-2 border-b px-2 sm:h-12 sm:gap-3 sm:px-3"
        >
            <TeamSwitcher in-header />
            <div class="ml-auto flex items-center gap-2 sm:gap-3">
                <div
                    class="bg-muted text-muted-foreground inline-flex max-w-[min(100%,11rem)] items-center gap-1.5 truncate rounded-md border px-2 py-1 text-[11px] tabular-nums sm:max-w-none sm:px-2.5 sm:text-xs"
                >
                    <Clock class="size-3.5 shrink-0" />
                    {{ clock }}
                </div>
                <Button variant="outline" size="sm" class="h-8 shrink-0" as-child>
                    <Link :href="dashboardUrl" class="inline-flex items-center gap-1.5">
                        <LogOut class="size-3.5" />
                        <span>Exit</span>
                    </Link>
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
