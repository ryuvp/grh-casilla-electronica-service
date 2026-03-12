<template>
  <slot name="toggle"></slot>
  <slot name="content"></slot>
</template>

<script setup>
import { nextTick, onMounted } from "vue";
import { MenuComponent } from "@/assets/ts/components";

const props = defineProps({
  menuSelector: { type: String, required: true },
});

onMounted(() => {
  nextTick(() => {
    MenuComponent.createInsance(props.menuSelector);
  });
});
</script>
