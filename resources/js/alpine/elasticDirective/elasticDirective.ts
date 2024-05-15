/**
 * Cause a textarea to autoresize. Usage:
 * ```html
 * <textarea x-elastic></textarea>
 * ```
 */
export function elasticDirective(el: HTMLElement) {
  const minRows = 4;
  let isResizing = false;

  const resize = () => {
    if (isResizing) return;

    isResizing = true;
    window.requestAnimationFrame(() => {
      const lineHeight = parseInt(window.getComputedStyle(el).lineHeight);
      const minHeight = lineHeight * minRows;
      el.style.height = '5px'; // Temporarily set to a small height to calculate scrollHeight correctly
      el.style.height = Math.max(el.scrollHeight, minHeight) + 'px';
      isResizing = false;
    });
  };

  const handleVisibilityChange = (mutationsList: MutationRecord[]) => {
    for (const mutation of mutationsList) {
      if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
        const targetElement = mutation.target as HTMLTextAreaElement;
        if (targetElement.style.display !== 'none') {
          resize();
        }
      }
    }
  };

  const observer = new MutationObserver(handleVisibilityChange);
  observer.observe(el, { attributes: true, attributeFilter: ['style'] });

  el.addEventListener('input', resize);

  // Resize on initialization
  window.requestAnimationFrame(resize);
}
