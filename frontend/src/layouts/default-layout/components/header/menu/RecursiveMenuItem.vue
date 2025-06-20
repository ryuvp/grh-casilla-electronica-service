<template>
  <div
    v-if="hasChildren"
    data-kt-menu-trigger="{default: 'click', lg: 'hover'}"
    data-kt-menu-placement="bottom-start"
    class="menu-item menu-lg-down-accordion menu-sub-lg-down-indention me-0 me-lg-2"
  >
    <span class="menu-link">
      <span class="menu-title">{{ translate(item.heading || item.sectionTitle) }}</span>
      <span class="menu-arrow d-lg-none"></span>
    </span>
    <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown px-lg-2 py-lg-4 w-lg-200px">
      <template v-for="(child, index) in item.pages || item.sub" :key="index">
        <div class="menu-item">
          <router-link
            v-if="child.route"
            class="menu-link"
            :to="child.route"
            active-class="active"
            :title="translate(child.tooltip || child.heading || child.sectionTitle)"
            data-bs-toggle="tooltip"
            data-bs-trigger="hover"
            data-bs-dismiss="click"
            data-bs-placement="right"
          >
            <span class="menu-icon">
              <KTIcon
                v-if="headerMenuIcons === 'keenthemes' && child.keenthemesIcon"
                :icon-name="child.keenthemesIcon"
                icon-class="fs-3"
              />
              <i
                v-else-if="headerMenuIcons === 'bootstrap' && child.bootstrapIcon"
                :class="['fs-3', child.bootstrapIcon]"
              />
            </span>
            <span class="menu-title">{{ translate(child.heading || child.sectionTitle) }}</span>
          </router-link>

          <!-- External link fallback -->
          <a
            v-else-if="child.href"
            class="menu-link"
            :href="child.href"
            target="_blank"
            rel="noopener"
            :title="translate(child.tooltip || child.heading || child.sectionTitle)"
            data-bs-toggle="tooltip"
            data-bs-trigger="hover"
            data-bs-dismiss="click"
            data-bs-placement="right"
          >
            <span class="menu-icon">
              <KTIcon
                v-if="headerMenuIcons === 'keenthemes' && child.keenthemesIcon"
                :icon-name="child.keenthemesIcon"
                icon-class="fs-3"
              />
              <i
                v-else-if="headerMenuIcons === 'bootstrap' && child.bootstrapIcon"
                :class="['fs-3', child.bootstrapIcon]"
              />
            </span>
            <span class="menu-title">{{ translate(child.heading || child.sectionTitle) }}</span>
          </a>

          <!-- Render hijos recursivos -->
          <RecursiveMenuItem
            v-if="(child.pages || child.sub) && (child.pages?.length || child.sub?.length)"
            :item="child"
          />
        </div>
      </template>
    </div>
  </div>

  <!-- Enlace directo (sin hijos) -->
  <div v-else class="menu-item">
    <router-link
      v-if="item.route"
      class="menu-link"
      :to="item.route"
      active-class="active"
      :title="translate(item.tooltip || item.heading)"
      data-bs-toggle="tooltip"
      data-bs-trigger="hover"
      data-bs-dismiss="click"
      data-bs-placement="right"
    >
      <span class="menu-icon">
        <KTIcon
          v-if="headerMenuIcons === 'keenthemes' && item.keenthemesIcon"
          :icon-name="item.keenthemesIcon"
          icon-class="fs-3"
        />
        <i
          v-else-if="headerMenuIcons === 'bootstrap' && item.bootstrapIcon"
          :class="['fs-3', item.bootstrapIcon]"
        />
      </span>
      <span class="menu-title">{{ translate(item.heading || item.sectionTitle) }}</span>
    </router-link>
  </div>
</template>

  
  <script lang="ts">
  import { defineComponent } from "vue";
  import { useRoute } from "vue-router";
  import { useI18n } from "vue-i18n";
  import { headerMenuIcons } from "@/layouts/default-layout/config/helper";
  
  export default defineComponent({
    name: "RecursiveMenuItem",
    props: {
      item: { type: Object, required: true },
      isRoot: { type: Boolean, default: false },
    },
    setup(props) {
      const route = useRoute();
      const { t, te } = useI18n();
  
      const hasActiveChildren = (match?: string) => {
        return !!match && route.path.includes(match);
      };
  
      const translate = (text: string) => (te(text) ? t(text) : text);
  
      const hasChildren =
        (props.item.pages && props.item.pages.length > 0) ||
        (props.item.sub && props.item.sub.length > 0);
  
      return {
        hasChildren,
        hasActiveChildren,
        translate,
        headerMenuIcons,
      };
    },
  });
  </script>
  
  