<template>
  <KTModal1 />
  <KTModal2 />
  <KTModal3 />
  <KTViewUsersModal />
  <KTListaUsuarios />
</template>

<script lang="ts">
import { defineComponent } from "vue";
import KTModal1 from "@/components/modals/Modal1.vue";
import KTModal2 from "@/components/modals/ModalEjemplo2.vue";
import KTModal3 from "@/components/modals/Modal3.vue";
import KTViewUsersModal from "@/components/modals/general/ViewUsersModal.vue";
import KTListaUsuarios from "@/components/modals/ListaUsuarios.vue";

export default defineComponent({
  name: "global-modals",
  components: {
    KTModal1,
    KTModal2,
    KTModal3,
    KTViewUsersModal,
    KTListaUsuarios,
  },
});
</script>
