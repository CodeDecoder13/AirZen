<script setup lang="ts">
import { Leaf } from 'lucide-vue-next';
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <Leaf :class="className" v-bind="$attrs" :stroke-width="2" />
</template>
