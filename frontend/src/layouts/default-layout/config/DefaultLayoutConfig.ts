import type LayoutConfigTypes from "@/layouts/default-layout/config/types";

const config: LayoutConfigTypes = {
  general: {
    mode: "light",
    primaryColor: "#50CD89",
    pageWidth: "default",
    layout: "dark-sidebar",
    iconsType: "outline",
  },
  header: {
    display: true,
    default: {
      container: "fluid",
      fixed: {
        desktop: false,
        mobile: false,
      },
      menu: {
        display: true,
        iconType: "keenthemes",
      },
    },
  },
  sidebar: {
    display: true,
    default: {
      minimize: {
        desktop: {
          enabled: true,
          default: true,
          hoverable: true,
        },
      },
      menu: {
        iconType: "bootstrap",
      },
    },
  },
  toolbar: {
    display: true,
    container: "fluid",
    fixed: {
      desktop: false,
      mobile: false,
    },
  },
  pageTitle: {
    display: true,
    breadcrumb: true,
    direction: "row",
  },
  content: {
    container: "fluid",
  },
  footer: {
    display: true,
    container: "fluid",
    fixed: {
      desktop: false,
      mobile: false,
    },
  },
  illustrations: {
    set: "sketchy-1",
  },
  scrolltop: {
    display: true,
  },
};
export default config;
