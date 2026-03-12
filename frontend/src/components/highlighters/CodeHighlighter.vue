<template>
  <div class="py-5">
    <div class="highlight">
      <button
        class="highlight-copy btn"
        data-bs-toggle="tooltip"
        title=""
        data-bs-original-title="Copy code"
      >
        copy
      </button>
      <div class="highlight-code">
        <pre
          :class="`language-${lang}`"
          :style="{ height: getHeightInPixesls }"
        ><code :class="`language-${lang}`">
          <slot></slot>
        </code></pre>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import { useCopyClipboard } from "@/core/helpers/system";
import Prism from "prismjs";

const props = defineProps({
  lang: String,
  fieldHeight: Number,
});

const height = ref(props.fieldHeight);

const { init } = useCopyClipboard();

const getHeightInPixesls = computed(() => {
  return height.value + "px";
});

onMounted(() => {
  Prism.highlightAll();
  init();
});
</script>
