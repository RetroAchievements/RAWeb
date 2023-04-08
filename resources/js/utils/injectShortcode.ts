/**
 * Injects a forum shortcode at the current cursor position, or wraps the
 * selected text with the forum shortcode. If there's no selection, the
 * shortcode is inserted at the end of the message.
 *
 * @param start The opening tag of the shortcode.
 * @param end The closing tag of the shortcode. Defaults to an empty string.
 */
export function injectShortcode(start: string, end = ''): void {
  const commentTextarea = document.getElementById('commentTextarea') as HTMLTextAreaElement;

  if (!commentTextarea) {
    return;
  }

  const startPosition = commentTextarea.selectionStart;
  const endPosition = commentTextarea.selectionEnd;
  const selectedText = commentTextarea.value.substring(
    startPosition,
    endPosition,
  );

  const newText = (
    selectedText === ''
      ? `${commentTextarea.value}${start}${end}`
      : `${commentTextarea.value.slice(0, startPosition)}${start}${selectedText}${end}${commentTextarea.value.slice(endPosition)}`
  );

  commentTextarea.value = newText;
  commentTextarea.focus();
}
