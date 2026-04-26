<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';

export type PermissionGroup = {
    id: string;
    title: string;
    select_all?: boolean;
    help?: string;
    checkboxes?: { value: string; label: string }[];
    radios?: {
        name: string;
        options: { value: string; label: string }[];
    }[];
};

type RoleForm = {
    name: string;
    is_service_staff: boolean;
    permissions: string[];
    radio_options: Record<string, string>;
};

const props = defineProps<{
    form: InertiaForm<RoleForm>;
    permissionGroups: PermissionGroup[];
}>();

const catalogCheckboxPermissions = computed(() =>
    props.permissionGroups.flatMap((group) =>
        (group.checkboxes ?? []).map((checkbox) => checkbox.value),
    ),
);

const extraAssignedPermissions = computed(() => {
    const known = new Set(catalogCheckboxPermissions.value);
    return props.form.permissions.filter((permission) => !known.has(permission));
});

function hasPerm(value: string): boolean {
    return props.form.permissions.includes(value);
}

function togglePerm(value: string, checked: boolean) {
    const next = new Set(props.form.permissions);
    if (checked) {
        next.add(value);
    } else {
        next.delete(value);
    }
    props.form.permissions = Array.from(next);
}

function groupCheckboxValues(group: PermissionGroup): string[] {
    return group.checkboxes?.map((c) => c.value) ?? [];
}

function groupAllChecked(group: PermissionGroup): boolean {
    const keys = groupCheckboxValues(group);
    if (keys.length === 0) {
        return false;
    }
    return keys.every((k) => hasPerm(k));
}

function toggleGroupAll(group: PermissionGroup, checked: boolean) {
    const keys = groupCheckboxValues(group);
    const next = new Set(props.form.permissions);
    for (const k of keys) {
        if (checked) {
            next.add(k);
        } else {
            next.delete(k);
        }
    }
    props.form.permissions = Array.from(next);
}

function setRadio(name: string, value: string) {
    props.form.radio_options = {
        ...props.form.radio_options,
        [name]: value,
    };
}
</script>

<template>
    <div class="space-y-8">
        <section
            v-for="group in permissionGroups"
            :key="group.id"
            class="space-y-3 border-b border-border pb-6 last:border-0"
        >
            <div
                class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between"
            >
                <div>
                    <h3 class="text-base font-semibold">{{ group.title }}</h3>
                    <p
                        v-if="group.help"
                        class="text-muted-foreground max-w-prose text-sm"
                    >
                        {{ group.help }}
                    </p>
                </div>
                <div
                    v-if="group.select_all && (group.checkboxes?.length ?? 0) > 0"
                    class="flex shrink-0 items-center gap-2"
                >
                    <Checkbox
                        :checked="groupAllChecked(group)"
                        @update:checked="
                            (v: boolean | 'indeterminate') =>
                                toggleGroupAll(group, v === true)
                        "
                    />
                    <span class="text-sm">Select all</span>
                </div>
            </div>

            <div
                v-if="group.id === 'others'"
                class="flex items-start gap-2 py-1"
            >
                <Checkbox
                    :id="`is_service_staff_${group.id}`"
                    :checked="form.is_service_staff"
                    @update:checked="
                        (v: boolean | 'indeterminate') =>
                            (form.is_service_staff = v === true)
                    "
                />
                <Label
                    :for="`is_service_staff_${group.id}`"
                    class="cursor-pointer font-normal leading-snug"
                >
                    Service staff
                </Label>
            </div>

            <div
                v-if="group.radios?.length"
                class="space-y-4"
            >
                <div
                    v-for="cluster in group.radios"
                    :key="cluster.name"
                    class="grid gap-2"
                >
                    <label
                        v-for="opt in cluster.options"
                        :key="opt.value"
                        class="flex cursor-pointer items-center gap-2 text-sm"
                    >
                        <input
                            type="radio"
                            class="size-4"
                            :name="`radio-${group.id}-${cluster.name}`"
                            :value="opt.value"
                            :checked="
                                form.radio_options[cluster.name] === opt.value
                            "
                            @change="setRadio(cluster.name, opt.value)"
                        />
                        <span>{{ opt.label }}</span>
                    </label>
                </div>
            </div>

            <div
                v-if="group.checkboxes?.length"
                class="grid gap-2"
            >
                <div
                    v-for="cb in group.checkboxes"
                    :key="cb.value"
                    class="flex items-start gap-2"
                >
                    <Checkbox
                        :id="`perm-${cb.value}`"
                        :checked="hasPerm(cb.value)"
                        @update:checked="
                            (v: boolean | 'indeterminate') =>
                                togglePerm(cb.value, v === true)
                        "
                    />
                    <Label
                        :for="`perm-${cb.value}`"
                        class="cursor-pointer font-normal leading-snug"
                    >
                        {{ cb.label }}
                    </Label>
                </div>
            </div>
        </section>

        <section
            v-if="extraAssignedPermissions.length"
            class="space-y-3 border-b border-border pb-6 last:border-0"
        >
            <div>
                <h3 class="text-base font-semibold">Other permissions</h3>
                <p class="text-muted-foreground max-w-prose text-sm">
                    Existing assigned permissions that are not in the current
                    catalog.
                </p>
            </div>
            <div class="grid gap-2">
                <div
                    v-for="permission in extraAssignedPermissions"
                    :key="permission"
                    class="flex items-start gap-2"
                >
                    <Checkbox
                        :id="`perm-extra-${permission}`"
                        :checked="hasPerm(permission)"
                        @update:checked="
                            (v: boolean | 'indeterminate') =>
                                togglePerm(permission, v === true)
                        "
                    />
                    <Label
                        :for="`perm-extra-${permission}`"
                        class="cursor-pointer font-normal leading-snug"
                    >
                        {{ permission }}
                    </Label>
                </div>
            </div>
        </section>
    </div>
</template>
