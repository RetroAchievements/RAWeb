import { screen } from '@testing-library/dom';
import userEvent from '@testing-library/user-event';
import { describe, it, expect } from 'vitest';

import { injectShortcode } from './injectShortcode';

function render(start: string, end = '') {
  (document as any).injectShortcode = injectShortcode;

  document.body.innerHTML = /** @html */ `
    <textarea id="commentTextarea"></textarea>
    <button onclick="injectShortcode('commentTextarea', '${start}', '${end}')">Insert shortcode</button>
  `;
}

describe('Util: injectShortcode', () => {
  it('is defined #sanity', () => {
    expect(injectShortcode).toBeDefined();
  });

  it('renders without crashing #sanity', () => {
    render('[shortcode]', '[/shortcode]');
    expect(screen.getByRole('button', { name: /insert shortcode/i })).toBeVisible();
  });

  it('given the user has no cursor position and no text selection, can insert a shortcode', async () => {
    // ARRANGE
    render('[shortcode]', '[/shortcode]');

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /insert shortcode/i }));

    // ASSERT
    const textareaEl = screen.getByRole<HTMLTextAreaElement>('textbox');
    expect(textareaEl.value).toEqual('[shortcode][/shortcode]');
  });

  it('given the user has a cursor position and no text selected, should insert a shortcode at the cursor position', async () => {
    // ARRANGE
    render('[shortcode]', '[/shortcode]');

    // ACT
    const textareaEl = screen.getByRole<HTMLTextAreaElement>('textbox');

    await userEvent.type(textareaEl, 'This is a test');

    textareaEl.setSelectionRange(5, 5); // Set the cursor, but don't highlight any text.
    await userEvent.click(screen.getByRole('button', { name: /insert shortcode/i }));

    // ASSERT
    expect(textareaEl.value).toEqual('This [shortcode][/shortcode]is a test');
  });

  it('given the cursor has a cursor position and text selected, wraps the text with the shortcode', async () => {
    // ARRANGE
    render('[shortcode]', '[/shortcode]');

    // ACT
    const textareaEl = screen.getByRole<HTMLTextAreaElement>('textbox');

    await userEvent.type(textareaEl, 'This is a test');

    textareaEl.setSelectionRange(5, 7); // Highlight the word "is".
    await userEvent.click(screen.getByRole('button', { name: /insert shortcode/i }));

    // ASSERT
    expect(textareaEl.value).toEqual('This [shortcode]is[/shortcode] a test');
  });

  it('given a shortcode is injected, restores the cursor position after shortcode injection', async () => {
    // ARRANGE
    render('[shortcode]', '[/shortcode]');

    // ACT
    const textareaEl = screen.getByRole<HTMLTextAreaElement>('textbox');

    await userEvent.type(textareaEl, 'This is a test');

    textareaEl.setSelectionRange(5, 7); // Highlight the word "is".
    await userEvent.click(screen.getByRole('button', { name: /insert shortcode/i }));

    // ASSERT
    expect(textareaEl.selectionStart).toEqual(16);
    expect(textareaEl.selectionEnd).toEqual(18);
  });

  it('properly handles shortcodes that have single-character closing tags', async () => {
    // ARRANGE
    render('[ach=', ']');

    // ACT
    const textareaEl = screen.getByRole<HTMLTextAreaElement>('textbox');

    await userEvent.type(textareaEl, 'I unlocked achievement 12345');

    textareaEl.setSelectionRange(23, 28); // Highlight "12345".
    await userEvent.click(screen.getByRole('button', { name: /insert shortcode/i }));

    // ASSERT
    expect(textareaEl.value).toEqual('I unlocked achievement [ach=12345]');
  });
});
