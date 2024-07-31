import { screen } from '@testing-library/dom';
import { describe, expect, it } from 'vitest';

import { renderTooltip } from './renderTooltip';

describe('Util: renderTooltip', () => {
  it('is defined #sanity', () => {
    expect(renderTooltip).toBeDefined();
  });

  it('given an anchor element and an html template, renders a tooltip', () => {
    // ARRANGE
    const anchorEl = document.createElement('div');
    const tooltipHtml = '<p>I worked!</p>';

    // ACT
    renderTooltip(anchorEl, tooltipHtml);

    // ASSERT
    expect(screen.getByText(/i worked/i)).toBeVisible();
  });

  it('given there is already an active tooltip element, removes it from the DOM', () => {
    // ARRANGE
    const anchorEl = document.createElement('div');
    const firstTooltipHtml = '<p>one</p>';
    const secondTooltipHtml = '<p>two</p>';

    // ACT
    renderTooltip(anchorEl, firstTooltipHtml);
    renderTooltip(anchorEl, secondTooltipHtml);

    // ASSERT
    expect(screen.queryByText(/one/i)).not.toBeInTheDocument();
    expect(screen.getByText(/two/i)).toBeVisible();
  });
});
