export function autoExpandTextInput(textInputEl: HTMLTextAreaElement | HTMLInputElement) {
  // When we do resize the field, add a small buffer so a scrollbar doesn't
  // appear in the user's browser.
  const SCROLL_BAR_BUFFER_SIZE = 2;

  // Check if the textarea has been manually resized, and bail if it has.
  // We don't want to override the user's preferred size.
  const lastHeight = textInputEl.dataset.lastHeight;
  if (lastHeight && textInputEl.offsetHeight !== Number(lastHeight)) {
    return;
  }

  // We need to preserve a `minHeight` attribute on the input field so we
  // don't inadvertently shrink the field.
  if (!textInputEl.dataset.minHeight) {
    textInputEl.dataset.minHeight = String(textInputEl.offsetHeight + SCROLL_BAR_BUFFER_SIZE);
  } else {
    const preservedHeight = Number(textInputEl.dataset.minHeight);

    // Temporarily reset the height to allow it to shrink to the content size.
    textInputEl.style.height = `${preservedHeight - SCROLL_BAR_BUFFER_SIZE}px`;

    // prettier-ignore
    if (
      textInputEl.scrollHeight >= preservedHeight
      && Math.max(textInputEl.scrollHeight, preservedHeight) === textInputEl.scrollHeight
    ) {
      textInputEl.style.height = `${textInputEl.scrollHeight + SCROLL_BAR_BUFFER_SIZE}px`;
    }
  }

  // Store the new height
  textInputEl.dataset.lastHeight = String(textInputEl.offsetHeight);
}
