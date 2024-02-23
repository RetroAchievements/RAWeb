import { screen } from '@testing-library/dom';
import userEvent from '@testing-library/user-event';
import {
  // @prettier-ignore
  describe,
  expect,
  it,
  vi,
} from 'vitest';

import * as fetcherModule from './fetcher';
import { loadPostPreview } from './loadPostPreview';

function render(textContent = '') {
  (document as any).loadPostPreview = loadPostPreview;

  document.body.innerHTML = /** @html */ `
    <div>
      <textarea id="commentTextarea">${textContent}</textarea>
      <button onclick="loadPostPreview()">Preview</button>
      <div id="post-preview" data-testid="post-preview"></div>
      <img id="preview-loading-icon" style="opacity: 0;">
    </div>
  `;
}

describe('Util: loadPostPreview', () => {
  it('is defined #sanity', () => {
    expect(loadPostPreview).toBeDefined();
  });

  it('renders without crashing #sanity', () => {
    render();

    expect(screen.getByRole('textbox')).toBeVisible();
  });

  it('given post content, fetches and displays a preview', async () => {
    // ARRANGE
    const fetcherSpy = vi.spyOn(fetcherModule, 'fetcher').mockResolvedValueOnce({
      postPreviewHtml: '<p>Hello from preview!</p>',
    });

    // ACT
    await userEvent.type(screen.getByRole('textbox'), 'Hello from preview!');
    await userEvent.click(screen.getByRole('button', { name: /preview/i }));

    // ASSERT
    expect(fetcherSpy).toHaveBeenCalledTimes(1);
    expect(screen.getByTestId('post-preview')).toHaveTextContent(/hello from preview/i);
  });

  it('given duplicate content, does not fetch', async () => {
    // ARRANGE
    const fetcherSpy = vi.spyOn(fetcherModule, 'fetcher').mockResolvedValueOnce({
      postPreviewHtml: '<p>Hello from preview!</p>',
    });

    // ACT
    await userEvent.type(screen.getByRole('textbox'), 'Hello from preview!');
    await userEvent.click(screen.getByRole('button', { name: /preview/i }));
    await userEvent.click(screen.getByRole('button', { name: /preview/i }));
    await userEvent.click(screen.getByRole('button', { name: /preview/i }));

    // ASSERT
    expect(fetcherSpy).toHaveBeenCalledTimes(1);
  });
});
