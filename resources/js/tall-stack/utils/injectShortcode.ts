/**
 * Injects a forum shortcode at the current cursor position, or wraps the
 * selected text with the forum shortcode. If there's no selection, the
 * shortcode is inserted at the end of the message.
 *
 * @param targetElementId The id of the target textarea.
 * @param start The opening tag of the shortcode.
 * @param end The closing tag of the shortcode. Defaults to an empty string.
 */
export function injectShortcode(targetElementId: string, start: string, end = ''): void {
  const commentTextarea = document.getElementById(targetElementId) as HTMLTextAreaElement;

  if (!commentTextarea) {
    return;
  }

  const startPosition = commentTextarea.selectionStart;
  const endPosition = commentTextarea.selectionEnd;
  const selectedText = commentTextarea.value.substring(startPosition, endPosition);

  const beforeSelectedText = commentTextarea.value.slice(0, startPosition);
  const afterSelectedText = commentTextarea.value.slice(endPosition);

  const newText = [beforeSelectedText, start, selectedText, end, afterSelectedText].join('');

  commentTextarea.value = newText;

  // Restore the cursor position.
  commentTextarea.selectionStart = startPosition + start.length;
  commentTextarea.selectionEnd = endPosition + start.length;
  commentTextarea.focus();
}
