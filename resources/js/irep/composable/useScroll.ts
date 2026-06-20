export function useScroll() {
  const scrollToWithOffset = (el: HTMLElement, offset = 0) => {
    if (el) {
      const elementPosition = el.getBoundingClientRect().top;
      const offsetPosition = elementPosition + window.pageYOffset - offset;

      window.scrollTo({
        top: offsetPosition,
        behavior: "smooth",
      });
    }
  };

  return { scrollToWithOffset };
}
