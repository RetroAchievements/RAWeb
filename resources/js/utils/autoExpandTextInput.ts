export function autoExpandTextInput(textInputEl: HTMLTextAreaElement | HTMLInputElement) {
  if (!textInputEl.dataset.minHeight) {
    textInputEl.dataset.minHeight = String(textInputEl.offsetHeight + 2);
  } else {
    const preservedHeight = Number(textInputEl.dataset.minHeight);

    if (Math.max(textInputEl.scrollHeight, preservedHeight) === textInputEl.scrollHeight) {
      textInputEl.style.height = `${textInputEl.scrollHeight + 2}px`;
    }
  }
}
