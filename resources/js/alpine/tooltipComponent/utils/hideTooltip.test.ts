import { describe, expect, it, vi } from 'vitest';

import { tooltipStore as store } from '../state/tooltipStore';
import { hideTooltip } from './hideTooltip';

describe('Util: hideTooltip', () => {
  it('is defined #sanity', () => {
    expect(hideTooltip).toBeDefined();
  });

  it('given a timeout id is held in the store, should clear the timeout when invoked', () => {
    // ARRANGE
    vi.useFakeTimers();
    store.dynamicTimeoutId = setTimeout(() => {
      console.log('hi!');
    }, 500);

    // ACT
    hideTooltip();

    // ASSERT
    expect(store.dynamicTimeoutId).toEqual(null);
  });

  it('should immediately add some styles to the tooltip element', () => {
    // ARRANGE
    vi.useFakeTimers();

    const tooltipEl = document.createElement('div');
    store.tooltipEl = tooltipEl;
    store.currentTooltipId = 1;

    // ACT
    hideTooltip();

    // ASSERT
    expect(tooltipEl.style.transition).toBeTruthy();
    expect(tooltipEl.style.opacity).toBeTruthy();
  });

  it('given a lengthy period of time passes, should strip added styles', () => {
    // ARRANGE
    vi.useFakeTimers();

    const tooltipEl = document.createElement('div');
    store.tooltipEl = tooltipEl;
    store.currentTooltipId = 1;

    // ACT
    hideTooltip();
    vi.advanceTimersByTime(300);

    // ASSERT
    expect(tooltipEl.style.display).not.toBeTruthy();
    expect(tooltipEl.style.transition).not.toBeTruthy();
    expect(tooltipEl.style.opacity).not.toBeTruthy();
  });

  it('given currentTooltipId is different after 150ms, styles should not be stripped by the execution context', () => {
    // ARRANGE
    vi.useFakeTimers();

    const tooltipEl = document.createElement('div');
    store.tooltipEl = tooltipEl;
    store.currentTooltipId = 1;

    // ACT
    hideTooltip();

    vi.advanceTimersByTime(50);
    store.currentTooltipId = 2;
    vi.advanceTimersByTime(180);

    // ASSERT
    expect(tooltipEl.style.transition).toBeTruthy();
    expect(tooltipEl.style.opacity).toBeTruthy();
  });
});
