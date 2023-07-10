// TODO: Refactor to use Alpine.js instead.
export function initializeTextareaCounter() {
  const textareaCounterEls = document.getElementsByClassName('textarea-counter') as HTMLCollectionOf<HTMLInputElement>;

  Array.from(textareaCounterEls).forEach((textareaCounterEl) => {
    const textareaId = textareaCounterEl.dataset.textareaId;
    const textareaEl = document.getElementById(textareaId ?? 'no-id-found') as HTMLInputElement;

    if (textareaEl) {
      const max = Number(textareaEl.getAttribute('maxlength')) ?? 0;

      if (max) {
        const updateCount = () => {
          const currentCharacterCount = getStringByteCount(textareaEl.value);
          textareaCounterEl.textContent = `${currentCharacterCount} / ${max}`;
          textareaCounterEl.classList.toggle('text-danger', currentCharacterCount >= max);
        };

        ['input', 'change', 'blur'].forEach(function (eventName) {
          textareaEl.addEventListener(eventName, updateCount);
        });

        updateCount();
      }
    }
  });
}

// Some characters, such as "á, ã, â, ü, ç, ñ, à, Á, Ã, Â, Ü, Ç, Ñ, À" or
// emojis, are actually more than 1 character. We need to count those properly
// when computing if the user is getting close to the field's max length.
function getStringByteCount(value: string) {
  return encodeURI(value).split(/%..|./).length - 1;
}

window.addEventListener('load', initializeTextareaCounter);
