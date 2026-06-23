import type { FlatItem, ShortcodeData } from "../types/DemoTypes";
import { defineStore } from "pinia";
import { computed, inject, reactive, ref, watch } from "vue";

export const GLOBAL_STORE_KEY = "ireGlobalStore";

export const createGlobalStore = (id: string) =>
  defineStore(id, () => {
  const hoverdSvg = ref();
  const tooltip = ref("1");

  const shortcodeData = ref<ShortcodeData>();
  const irePlaginWp = ref<IrePlagin>();

  const getMetaValue = (metaKey: string): any => {
    return (
      shortcodeData.value?.meta?.find((meta: any) => meta?.meta_key === metaKey)
        ?.meta_value || null
    );
  };

  const constants = ref({
    AVAILABLE_FLAT_COLOR:
      getMetaValue("available_flat_color") || "rgba(255, 255, 255, 0.3)",
    PREVIEW_PATH_COLOR:
      getMetaValue("path_color") || "rgba(255, 255, 255, 0.3)",
    PREVIEW_PATH_HOVER_COLOR:
      getMetaValue("path_hover_color") || "rgba(250, 250, 250, 0.54)",
    PREVIEW_RESERVED_COLOR:
      getMetaValue("reserved_color") || "rgba(255, 247, 89, 0.53)",
    PREVIEW_SOLD_COLOR: getMetaValue("sold_color") || "rgba(219, 64, 64, 0.45)",
    PREVIEW_STROKE_COLOR: getMetaValue("stroke_color") || "rgba(0, 0, 0,  1)",
    PREVIEW_PRIMARY_COLOR:
      getMetaValue("primary_color") || "rgba(45, 45, 46,  1)",
    PREVIEW_STROKE_WIDTH: +getMetaValue("stroke_width") || 1,
    PREVIEW_BORDER_RADIUS: +getMetaValue("border_radius") || 0,
  });

  const colors = reactive({
    available_flat_color: constants.value?.AVAILABLE_FLAT_COLOR,
    path: constants.value?.PREVIEW_PATH_COLOR,
    path_hover: constants.value?.PREVIEW_PATH_HOVER_COLOR,
    reserved: constants.value?.PREVIEW_RESERVED_COLOR,
    sold: constants.value?.PREVIEW_SOLD_COLOR,
    stroke_color: constants.value?.PREVIEW_STROKE_COLOR,
    primary_color: constants.value?.PREVIEW_PRIMARY_COLOR,
    stroke_width: constants.value?.PREVIEW_STROKE_WIDTH,
    border_radius: constants.value?.PREVIEW_BORDER_RADIUS,
  });

  const flats = computed(() => {
    return shortcodeData.value?.flats?.map((flat) => {
      const flatType =
        flat?.use_type || !flat?.type
          ? (shortcodeData.value?.types?.find((t) => t.id === flat?.type_id) ??
            flat?.type)
          : flat?.type;

      return { ...flat, type: flatType } as FlatItem;
    }) as FlatItem[];
  });

  const openReservedFlat = computed(() => {
    return getMetaValue("open_reserved_flat") === "true";
  });

  const openSoldFlat = computed(() => {
    return getMetaValue("open_sold_flat") === "true";
  });

  const priceRounded = computed(() => {
    return getMetaValue("price_rounded") === "true";
  });

  const translations = computed(() => {
    return irePlaginWp.value?.translations;
  });

  const hasPriceHistoryAddon = computed(() => {
    return Boolean(irePlaginWp.value?.price_history_addon);
  });

  const cssVariables = computed(() => {
    return {
      "--available-flat-color": colors.available_flat_color,
      "--reserved-color": colors.reserved,
      "--sold-color": colors.sold,
      "--path-hover-color": colors.path_hover,
      "--path-color": colors.path,
      "--stroke-color": colors.stroke_color,
      "--primary-color": colors.primary_color,
      "--stroke-width": colors.stroke_width + "px",
      "--border-radius": colors.border_radius + "px",
    };
  });

  const customTypesPathCss = computed(() => {
    const customTypes = getMetaValue("custom_types");
    const list = Array.isArray(customTypes)
      ? customTypes
      : typeof customTypes === "string"
        ? (() => {
            try {
              return JSON.parse(customTypes) ?? [];
            } catch {
              return [];
            }
          })()
        : [];

    return list
      .map((t: any) => {
        const escape = (s: string) =>
          s.replace(/\\/g, "\\\\").replace(/"/g, '\\"');
        const slugKey = escape(String(t.value ?? ""));
        const titleKey = escape(String(t.title ?? ""));
        const fill = t?.type_color || "var(--path-color)";
        const rules: string[] = [
          `.path-color g[conf="${slugKey}"] path { fill: ${fill}; }`,
          `.path-color.path-hover-fill-only svg g[conf="${slugKey}"]:hover path { fill: ${fill} !important; }`,
        ];
        if (titleKey !== slugKey) {
          rules.push(`.path-color g[conf="${titleKey}"] path { fill: ${fill}; }`);
          rules.push(`.path-color.path-hover-fill-only svg g[conf="${titleKey}"]:hover path { fill: ${fill} !important; }`);
        }
        return rules.join("\n");
      })
      .join("\n");
  });

  const setData = (data: any) => {
    shortcodeData.value = data;
  };

  const setIrePlaginWp = (val: IrePlagin) => {
    irePlaginWp.value = val;
  };

  watch(
    () => shortcodeData.value?.meta,
    () => {
      const available_flat_color = getMetaValue("available_flat_color");
      const path_color = getMetaValue("path_color");
      const path_hover_color = getMetaValue("path_hover_color");
      const reserved_color = getMetaValue("reserved_color");
      const sold_color = getMetaValue("sold_color");
      const stroke_color = getMetaValue("stroke_color");
      const primary_color = getMetaValue("primary_color");
      const stroke_width = getMetaValue("stroke_width");
      const border_radius = getMetaValue("border_radius");

      if (available_flat_color) {
        colors.available_flat_color = available_flat_color.toString();
      }

      if (path_color) {
        colors.path = path_color.toString();
      }

      if (path_hover_color) {
        colors.path_hover = path_hover_color.toString();
      }

      if (reserved_color) {
        colors.reserved = reserved_color.toString();
      }

      if (sold_color) {
        colors.sold = sold_color.toString();
      }

      if (stroke_color) {
        colors.stroke_color = stroke_color.toString();
      }

      if (stroke_color) {
        colors.stroke_color = stroke_color.toString();
      }

      if (primary_color) {
        colors.primary_color = primary_color.toString();
      }

      colors.stroke_width = Number(stroke_width) || 1;

      colors.border_radius = Number(border_radius) || 0;
    },
    { deep: true },
  );

  return {
    // State
    hoverdSvg,
    tooltip,
    shortcodeData,
    irePlaginWp,
    // Geters
    flats,
    getMetaValue,
    openReservedFlat,
    openSoldFlat,
    priceRounded,
    translations,
    cssVariables,
    customTypesPathCss,
    hasPriceHistoryAddon,
    // Mutation
    setData,
    setIrePlaginWp,
  };
});

export type GlobalStore = ReturnType<ReturnType<typeof createGlobalStore>>;

export const useGlobalStore = (): GlobalStore => {
  const store = inject<GlobalStore>(GLOBAL_STORE_KEY);
  if (!store) throw new Error("useGlobalStore: no store provided — wrap with a Project or Project360 component");
  return store;
};
