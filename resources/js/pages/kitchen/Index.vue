<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Info, RefreshCw } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import kitchenRoutes from '@/routes/kitchen';
import type { Team } from '@/types';

defineOptions({
    layout: (p: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Kitchen',
                href: kitchenRoutes.index.url(p.currentTeam!.slug),
            },
        ],
    }),
});

function refreshOrders() {
    router.reload({
        preserveScroll: true,
        onFinish: () => {
            /* hook for future kitchen order polling */
        },
    });
}
</script>

<template>
    <Head title="Kitchen" />

    <section
        class="print:hidden flex min-h-[90vh] flex-1 flex-col gap-6 p-4 md:p-6"
    >
        <div class="flex flex-col items-center text-center">
            <h3
                class="flex flex-wrap items-center justify-center gap-2 text-2xl font-semibold tracking-tight"
            >
                All orders - Kitchen
                <TooltipProvider :delay-duration="200">
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <button
                                type="button"
                                class="text-sky-600 inline-flex rounded-full p-1 hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                aria-label="About the kitchen screen"
                            >
                                <Info class="size-5 shrink-0" aria-hidden="true" />
                            </button>
                        </TooltipTrigger>
                        <TooltipContent
                            side="bottom"
                            class="max-w-sm text-left text-sm leading-snug"
                        >
                            This is the kitchen screen. Here order details can be viewed and
                            orders can be marked as cooked.
                        </TooltipContent>
                    </Tooltip>
                </TooltipProvider>
            </h3>
        </div>

        <div
            class="rounded-xl border border-border bg-card shadow-sm ring-1 ring-border/60 transition-shadow hover:shadow-md"
        >
            <div class="p-4 sm:p-5">
                <div class="flex justify-end gap-2.5 print:hidden">
                    <Button
                        id="refresh_orders"
                        type="button"
                        class="gap-2"
                        @click="refreshOrders"
                    >
                        <RefreshCw class="size-4 shrink-0" aria-hidden="true" />
                        Refresh
                    </Button>
                </div>

                <div class="flow-root mt-5 border-b border-border pb-0">
                    <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-5">
                        <div class="inline-block min-w-full py-2 align-middle sm:px-5">
                            <input id="orders_for" type="hidden" value="kitchen" />

                            <div id="orders_div" class="flex flex-wrap">
                                <div class="w-full md:w-full">
                                    <h4
                                        class="text-muted-foreground py-12 text-center text-lg font-medium"
                                    >
                                        No orders found
                                    </h4>
                                </div>
                            </div>

                            <div class="overlay hidden" aria-hidden="true">
                                <RefreshCw
                                    class="text-muted-foreground mx-auto mt-4 block size-8 animate-spin"
                                    aria-hidden="true"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
