export function autoExpandTextInput(textInputEl: HTMLTextAreaElement | HTMLInputElement) {
  // When we do resize the field, add a small buffer so a scrollbar doesn't
  // appear in the user's browser.
  const SCROLL_BAR_BUFFER_SIZE = 2;

  // We need to preserve a `minHeight` attribute on the input field so we
  // don't inadvertently shrink the field.
  if (!textInputEl.dataset.minHeight) {
    textInputEl.dataset.minHeight = String(textInputEl.offsetHeight + SCROLL_BAR_BUFFER_SIZE);
  } else {
    const preservedHeight = Number(textInputEl.dataset.minHeight);

    if (Math.max(textInputEl.scrollHeight, preservedHeight) === textInputEl.scrollHeight) {
      textInputEl.style.height = `${textInputEl.scrollHeight + SCROLL_BAR_BUFFER_SIZE}px`;
    }
  }
}
