import { getStringByteCount } from './getStringByteCount';

// TODO: Refactor to use Alpine.js instead.
export function initializeTextareaCounter() {
  const textareaCounterEls = document.getElementsByClassName(
    'textarea-counter',
  ) as HTMLCollectionOf<HTMLInputElement>;

  for (const textareaCounterEl of Array.from(textareaCounterEls)) {
    const textareaId = textareaCounterEl.dataset.textareaId;
    const textareaEl = document.getElementById(textareaId ?? 'no-id-found') as HTMLInputElement;

    if (textareaEl) {
      const max = Number(textareaEl.getAttribute('maxlength')) ?? 0;

      if (max) {
        const updateCount = () => {
          const currentCharacterCount = getStringByteCount(textareaEl.value);
          textareaCounterEl.textContent = `${currentCharacterCount} / ${max}`;
          textareaCounterEl.classList.toggle('text-danger', currentCharacterCount > max);
        };

        for (const eventName of ['input', 'change', 'blur']) {
          textareaEl.addEventListener(eventName, updateCount);
        }

        updateCount();
      }
    }
  }
}

window.addEventListener('load', initializeTextareaCounter);
