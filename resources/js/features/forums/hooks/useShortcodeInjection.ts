import type { FieldValues, Path } from 'react-hook-form';
import { useFormContext } from 'react-hook-form';

import type { Shortcode } from '../models';

export function useShortcodeInjection<TFieldValues extends FieldValues>(props: {
  fieldName: Path<TFieldValues>;
}) {
  const { fieldName } = props;

  const { getValues, setValue } = useFormContext<TFieldValues>();

  const injectShortcode = (shortcode: Shortcode) => {
    const textareaEl = document.querySelector(
      `textarea[name="${fieldName}"]`,
    ) as HTMLTextAreaElement;

    if (!textareaEl) {
      return;
    }

    const currentValue = getValues(fieldName) as string;
    const selectionStart = textareaEl.selectionStart;
    const selectionEnd = textareaEl.selectionEnd;
    const selectedText = currentValue.substring(selectionStart, selectionEnd);

    // Build the new text with the injected shortcode.
    const beforeSelection = currentValue.slice(0, selectionStart);
    const afterSelection = currentValue.slice(selectionEnd);
    const content = selectedText;

    const newText = `${beforeSelection}${shortcode.start}${content}${shortcode.end}${afterSelection}`;

    // Update the form value (render the injected shortcode to the screen).
    setValue(fieldName, newText as TFieldValues[typeof fieldName], {
      shouldDirty: true,
    });

    // Wait for React to update the DOM (this is async), then restore the user's highlighted text selection.
    setTimeout(() => {
      textareaEl.focus();

      const newPosition = selectionStart + shortcode.start.length;
      textareaEl.setSelectionRange(newPosition, newPosition + selectedText.length);
    }, 0);
  };

  return { injectShortcode };
}
