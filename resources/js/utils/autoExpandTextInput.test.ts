import { screen } from '@testing-library/dom';
import userEvent from '@testing-library/user-event';
import { describe, expect, it } from 'vitest';

import { autoExpandTextInput } from './autoExpandTextInput';

function render() {
  (document as any).autoExpandTextInput = autoExpandTextInput;

  document.body.innerHTML = /** @html */ `
    <textarea
      class="comment-textarea"
      name="body"
      maxlength="2000"
      placeholder="Enter a comment here..."
      oninput="autoExpandTextInput(this)"
    ></textarea>
  `;
}

describe('Util: autoExpandTextInput', () => {
  it('is defined #sanity', () => {
    expect(autoExpandTextInput).toBeDefined();
  });

  it('renders without crashing #sanity', () => {
    render();

    expect(screen.getByRole('textbox')).toBeInTheDocument();
  });

  it('on any user input, sets a minHeight data attribute', async () => {
    // ARRANGE
    render();

    // ACT
    const inputEl = screen.getByRole('textbox');
    await userEvent.type(inputEl, 'A');

    // ASSERT
    expect(inputEl.dataset.minHeight).toEqual('2');
  });

  it('given multiple user inputs, preserves the minHeight data attribute', async () => {
    // ARRANGE
    render();

    // ACT
    const inputEl = screen.getByRole('textbox');
    await userEvent.type(inputEl, 'Hello world');

    // ASSERT
    expect(inputEl.dataset.minHeight).toEqual('2');
  });
});
