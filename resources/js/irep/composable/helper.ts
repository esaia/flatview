import { useGlobalStore } from "../store/useGlobal";

export const tr = (key: string) => {
  const globalStore = useGlobalStore();

  if (!globalStore.translations) return key;

  return (globalStore.translations as any)?.[key] || key;
};

export const useTr = () => {
  const globalStore = useGlobalStore();
  return (key: string): string => {
    if (!globalStore.translations) return key;
    return (globalStore.translations as any)?.[key] || key;
  };
};

export const currencySymbol = () => {
  const currencyData = [
    { title: "🇺🇸 USD", value: "usd", symbol: "$" }, // United States
    { title: "🇪🇺 EUR", value: "eur", symbol: "€" }, // European Union
    { title: "🇬🇧 GBP", value: "gbp", symbol: "£" }, // United Kingdom
    { title: "🇯🇵 JPY", value: "jpy", symbol: "¥" }, // Japan
    { title: "🇦🇺 AUD", value: "aud", symbol: "A$" }, // Australia
    { title: "🇨🇦 CAD", value: "cad", symbol: "C$" }, // Canada
    { title: "🇨🇭 CHF", value: "chf", symbol: "CHF" }, // Switzerland
    { title: "🇨🇳 CNY", value: "cny", symbol: "¥" }, // China
    { title: "🇮🇳 INR", value: "inr", symbol: "₹" }, // India
    { title: "🇸🇬 SGD", value: "sgd", symbol: "S$" }, // Singapore
    { title: "🇳🇿 NZD", value: "nzd", symbol: "NZ$" }, // New Zealand
    { title: "🇰🇷 KRW", value: "krw", symbol: "₩" }, // South Korea
    { title: "🇧🇷 BRL", value: "brl", symbol: "R$" }, // Brazil
    { title: "🇷🇺 RUB", value: "rub", symbol: "₽" }, // Russia
    { title: "🇿🇦 ZAR", value: "zar", symbol: "R" }, // South Africa
    { title: "🇲🇽 MXN", value: "mxn", symbol: "Mex$" }, // Mexico
    { title: "🇭🇰 HKD", value: "hkd", symbol: "HK$" }, // Hong Kong
    { title: "🇹🇷 TRY", value: "try", symbol: "₺" }, // Turkey
    { title: "🇸🇪 SEK", value: "sek", symbol: "kr" }, // Sweden
    { title: "🇳🇴 NOK", value: "nok", symbol: "kr" }, // Norway
    { title: "🇩🇰 DKK", value: "dkk", symbol: "kr" }, // Denmark
    { title: "🇵🇱 PLN", value: "pln", symbol: "zł" }, // Poland
    { title: "🇹🇭 THB", value: "thb", symbol: "฿" }, // Thailand
    { title: "🇮🇩 IDR", value: "idr", symbol: "Rp" }, // Indonesia
    { title: "🇲🇾 MYR", value: "myr", symbol: "RM" }, // Malaysia
    { title: "🇵🇭 PHP", value: "php", symbol: "₱" }, // Philippines
    { title: "🇦🇪 AED", value: "aed", symbol: "د.إ" }, // United Arab Emirates
    { title: "🇸🇦 SAR", value: "sar", symbol: "﷼" }, // Saudi Arabia
    { title: "🇶🇦 QAR", value: "qar", symbol: "﷼" }, // Qatar
    { title: "🇰🇼 KWD", value: "kwd", symbol: "د.ك" }, // Kuwait
    { title: "🇧🇭 BHD", value: "bhd", symbol: ".د.ب" }, // Bahrain
    { title: "🇴🇲 OMR", value: "omr", symbol: "﷼" }, // Oman
    { title: "🇬🇪 GEL", value: "gel", symbol: "₾" }, // Georgia
  ];

  const globalStore = useGlobalStore();

  const activeCurrency: string =
    globalStore.shortcodeData?.meta
      ?.find((item: any) => item.meta_key === "currency")
      ?.meta_value.toString() || "usd";

  return (
    currencyData?.find((item) => item.value === activeCurrency)?.symbol || "$"
  );
};

export const getPrice = (price: number) => {
  const globalStore = useGlobalStore();

  const { getMetaValue, priceRounded } = globalStore;

  const isPriceSeparatorComma = getMetaValue("price_separator") === "comma";

  const digit = priceRounded ? 0 : 2;

  const formatOptions: Intl.NumberFormatOptions = {
    minimumFractionDigits: digit,
    maximumFractionDigits: digit,
  };

  let formatPrice = isPriceSeparatorComma
    ? Number(price).toLocaleString("en-US", formatOptions) // e.g. 1.199.000,00
    : Number(price).toLocaleString("de-DE", formatOptions);

  if (globalStore.getMetaValue("currency") === "chf") {
    formatPrice = formatPrice.replaceAll(",", "'");
  }

  return formatPrice;
};

export const getArea = (area: string) => {
  const globalStore = useGlobalStore();
  const { getMetaValue } = globalStore;

  const isSeparatorComma = getMetaValue("separator") === "comma";

  const numericArea = parseFloat(area);

  if (isNaN(numericArea)) return area;

  const decimals = Number.isInteger(numericArea) ? 0 : 2;
  const roundedValue = numericArea.toFixed(decimals);

  return isSeparatorComma ? roundedValue.replace(".", ",") : roundedValue;
};

export const getRoomCount = (roomCount: string) => {
  const globalStore = useGlobalStore();
  const { getMetaValue } = globalStore;

  const isSeparatorComma = getMetaValue("separator") === "comma";

  const numericCount = parseFloat(roomCount);

  if (isNaN(numericCount)) return roomCount;

  const roundedValue = (+numericCount.toFixed(1)).toString();

  return isSeparatorComma ? roundedValue.replace(".", ",") : roundedValue;
};

export const getAreaUnitLabel = () => {
  const globalStore = useGlobalStore();

  return globalStore.getMetaValue("area_unit") ?? "m";
};

export const getBlockById = (id: number | string) => {
  const globalStore = useGlobalStore();

  if (!id) return;
  return globalStore.shortcodeData?.blocks?.find(
    (block) => String(block.id) === String(id),
  );
};

export const getFloorById = (id: number | string) => {
  const globalStore = useGlobalStore();

  if (!id) return;
  return globalStore.shortcodeData?.floors?.find(
    (floor) => String(floor.id) === String(id),
  );
};

export const useGetFloorById = () => {
  const globalStore = useGlobalStore();
  return (id: number | string) => {
    if (!id) return undefined;
    return globalStore.shortcodeData?.floors?.find(
      (floor) => String(floor.id) === String(id),
    );
  };
};

export const useGetPrice = () => {
  const globalStore = useGlobalStore();
  return (price: number): string => {
    const { getMetaValue, priceRounded } = globalStore;
    const isPriceSeparatorComma = getMetaValue("price_separator") === "comma";
    const digit = priceRounded ? 0 : 2;
    const formatOptions: Intl.NumberFormatOptions = {
      minimumFractionDigits: digit,
      maximumFractionDigits: digit,
    };
    let formatPrice = isPriceSeparatorComma
      ? Number(price).toLocaleString("en-US", formatOptions)
      : Number(price).toLocaleString("de-DE", formatOptions);
    if (getMetaValue("currency") === "chf") {
      formatPrice = formatPrice.replaceAll(",", "'");
    }
    return formatPrice;
  };
};

export const useCurrencySymbol = () => {
  const globalStore = useGlobalStore();
  const currencyData = [
    { value: "usd", symbol: "$" }, { value: "eur", symbol: "€" },
    { value: "gbp", symbol: "£" }, { value: "jpy", symbol: "¥" },
    { value: "aud", symbol: "A$" }, { value: "cad", symbol: "C$" },
    { value: "chf", symbol: "CHF" }, { value: "cny", symbol: "¥" },
    { value: "inr", symbol: "₹" }, { value: "sgd", symbol: "S$" },
    { value: "nzd", symbol: "NZ$" }, { value: "krw", symbol: "₩" },
    { value: "brl", symbol: "R$" }, { value: "rub", symbol: "₽" },
    { value: "zar", symbol: "R" }, { value: "mxn", symbol: "Mex$" },
    { value: "hkd", symbol: "HK$" }, { value: "try", symbol: "₺" },
    { value: "sek", symbol: "kr" }, { value: "nok", symbol: "kr" },
    { value: "dkk", symbol: "kr" }, { value: "pln", symbol: "zł" },
    { value: "thb", symbol: "฿" }, { value: "idr", symbol: "Rp" },
    { value: "myr", symbol: "RM" }, { value: "php", symbol: "₱" },
    { value: "aed", symbol: "د.إ" }, { value: "sar", symbol: "﷼" },
    { value: "qar", symbol: "﷼" }, { value: "kwd", symbol: "د.ك" },
    { value: "bhd", symbol: ".د.ب" }, { value: "omr", symbol: "﷼" },
    { value: "gel", symbol: "₾" },
  ];
  return (): string => {
    const activeCurrency =
      globalStore.shortcodeData?.meta
        ?.find((item: any) => item.meta_key === "currency")
        ?.meta_value?.toString() || "usd";
    return currencyData.find((item) => item.value === activeCurrency)?.symbol || "$";
  };
};

export const setQuery = (key: string, value: string) => {
  // @ts-ignore
  const url = new URL(window.location);
  if (value === "") {
    url.searchParams.delete(key);
  } else {
    url.searchParams.set(key, value);
  }
  window.history.pushState({}, "", url);
};

export const getQuery = (key: string) => {
  const urlParams = new URLSearchParams(window.location.search);
  const pageFromQuery = urlParams.get(key);

  return pageFromQuery;
};

export const copyToClipboard = async (text: string): Promise<boolean> => {
  try {
    // Try modern clipboard API first
    if (navigator.clipboard) {
      await navigator.clipboard.writeText(text);
      return true;
    }

    // Fallback for older browsers
    const textArea = document.createElement("textarea");
    textArea.value = text;

    // Avoid scrolling to bottom
    textArea.style.top = "0";
    textArea.style.left = "0";
    textArea.style.position = "fixed";

    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    const success = document.execCommand("copy");
    document.body.removeChild(textArea);

    return success;
  } catch (err) {
    console.error("Failed to copy text:", err);
    return false;
  }
};

export const getNested = (obj: any, path: string) =>
  path?.split(".")?.reduce((o, key) => (o ? o[key] : undefined), obj);

export const getConfValue = (conf: string) => {
  const globalStore = useGlobalStore();
  const customTypes = globalStore.getMetaValue("custom_types");

  const customType = customTypes?.find((t: any) => t.title === conf);
  return customType ? customType.value : conf;
};

// Use this composable version when getConfValue will be called outside setup
// (e.g. from watchers, onMounted, or event handlers)
export const useGetConfValue = () => {
  const globalStore = useGlobalStore();
  return (conf: string): string => {
    const customTypes = globalStore.getMetaValue("custom_types");
    const customType = customTypes?.find((t: any) => t.title === conf);
    return customType ? customType.value : conf;
  };
};

export const getCustomTypeColor = (conf: string) => {
  const globalStore = useGlobalStore();
  const customTypes = globalStore.getMetaValue("custom_types");
  let customType = customTypes?.find((t: any) => t.title === conf);

  if (!customType) {
    customType = customTypes?.find((t: any) => t.value === conf);
  }

  return customType?.type_color || null;
};

export const transformOtherToKeyValue = (
  other: { key?: string; value?: unknown }[],
): Record<string, unknown> => {
  if (!Array.isArray(other)) return {};
  const result: Record<string, unknown> = {};
  for (const item of other) {
    result[item?.key ?? ""] = item?.value ?? "";
  }
  return result;
};

export const normalizeFilterOptionsMeta = (
  raw: unknown,
): Record<string, unknown> => {
  if (raw == null) return {};
  if (typeof raw === "object" && !Array.isArray(raw)) {
    return raw as Record<string, unknown>;
  }
  if (typeof raw === "string") {
    try {
      const parsed = JSON.parse(raw) as unknown;
      if (parsed && typeof parsed === "object" && !Array.isArray(parsed)) {
        return parsed as Record<string, unknown>;
      }
    } catch {
      /* ignore invalid json */
    }
  }
  return {};
};

export const normalizeRangeOption = (
  raw: unknown,
  fallback: { min: number; max: number; step: number },
) => {
  if (raw && typeof raw === "object" && !Array.isArray(raw)) {
    const min = Number((raw as { min?: unknown }).min);
    const max = Number((raw as { max?: unknown }).max);
    const step = Number((raw as { step?: unknown }).step);
    return {
      min: Number.isFinite(min) ? min : fallback.min,
      max: Number.isFinite(max) ? max : fallback.max,
      step: Number.isFinite(step) && step > 0 ? step : fallback.step,
    };
  }
  return fallback;
};

// ── Media type detection ──────────────────────────────────────────────────────
// Media items can come from the editor (YouTube links set `type: "youtube"`) or
// the media library (mp4 uploads expose neither `mime` nor `type`, only a `.mp4`
// url). Detection therefore has to fall back to the url itself.
export const extractYoutubeId = (url?: string): string => {
  const m = url?.match(
    /(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/|youtube\.com\/v\/|youtube\.com\/shorts\/)([a-zA-Z0-9_-]{11})/,
  );
  return m ? m[1] : "";
};

export const isYoutubeMedia = (img: any): boolean =>
  img?.mime === "youtube" ||
  img?.type === "youtube" ||
  /(?:youtube\.com|youtu\.be)/i.test(img?.url || "");

export const isVideoMedia = (img: any): boolean =>
  (typeof img?.mime === "string" && img.mime.startsWith("video/")) ||
  img?.type === "video" ||
  /\.mp4(?:\?|#|$)/i.test(img?.url || "");

// Returns a usable image url for thumbnails (e.g. flat cards). YouTube items
// resolve to their poster frame; plain videos have no poster so return "".
export const mediaThumbUrl = (img: any): string => {
  if (!img) return "";
  if (isYoutubeMedia(img)) {
    const id = extractYoutubeId(img.url);
    return id ? `https://img.youtube.com/vi/${id}/hqdefault.jpg` : "";
  }
  if (isVideoMedia(img)) return "";
  return img.url || "";
};
