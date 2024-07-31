import { beforeEach, describe, expect, it, vi } from 'vitest';

import * as FetcherModule from '@/utils/fetcher';

import { tooltipStore as store } from '../state/tooltipStore';
import { loadDynamicTooltip } from './loadDynamicTooltip';
import * as RenderTooltipModule from './renderTooltip';

vi.mock('../state/tooltipStore', () => ({
  tooltipStore: {
    dynamicContentCache: {},
    dynamicTimeoutId: null,
    tooltipEl: document.createElement('div'),
    trackedMouseX: 0,
    trackedMouseY: 0,
  },
}));

function render(anchorEl: HTMLElement) {
  (document as any).loadDynamicTooltip = loadDynamicTooltip;
  anchorEl.dataset.tooltipDynamic = '';
  document.body.appendChild(anchorEl);
}

describe('Util: loadDynamicTooltip', () => {
  beforeEach(() => {
    vi.resetAllMocks();

    store.dynamicContentCache = {};
    store.dynamicTimeoutId = null;
    store.tooltipEl = document.createElement('div');
    store.trackedMouseX = 0;
    store.trackedMouseY = 0;
  });

  it('is defined #sanity', () => {
    expect(loadDynamicTooltip).toBeDefined();
  });

  it('renders without crashing #sanity', () => {
    // ARRANGE
    const anchorEl = document.createElement('div');
    render(anchorEl);

    // ASSERT
    expect(anchorEl).toBeInTheDocument();
  });

  it('given cached tooltip content is available, should not make a network call to refetch the content', async () => {
    // ARRANGE
    const fetcherSpy = vi.spyOn(FetcherModule, 'fetcher');

    vi.useFakeTimers();

    store.dynamicContentCache.mockType_mockId = '<div>Cached content</div>';

    const anchorEl = document.createElement('div');
    render(anchorEl);

    // ACT
    await (document as any).loadDynamicTooltip(anchorEl, 'mockType', 'mockId');

    vi.advanceTimersByTime(300);

    // ASSERT
    expect(fetcherSpy).not.toHaveBeenCalled();
  });

  it('given cached tooltip content is available, should display the cached tooltip content', async () => {
    // ARRANGE
    const renderTooltipSpy = vi.spyOn(RenderTooltipModule, 'renderTooltip');

    vi.useFakeTimers();

    store.dynamicContentCache.mockType_mockId = '<div>Cached content</div>';

    const anchorEl = document.createElement('div');
    render(anchorEl);

    // ACT
    await (document as any).loadDynamicTooltip(anchorEl, 'mockType', 'mockId');

    vi.advanceTimersByTime(300);

    // ASSERT
    expect(renderTooltipSpy).toHaveBeenCalledWith(
      anchorEl,
      store.dynamicContentCache.mockType_mockId,
      undefined,
      undefined,
    );
  });

  it('given cached tooltip content is not available, should initially render a loading spinner', async () => {
    // ARRANGE
    vi.spyOn(FetcherModule, 'fetcher').mockResolvedValueOnce({ html: '' });
    const renderTooltipSpy = vi.spyOn(RenderTooltipModule, 'renderTooltip');

    vi.useFakeTimers();

    const anchorEl = document.createElement('div');
    render(anchorEl);

    // ACT
    await (document as any).loadDynamicTooltip(anchorEl, 'mockType', 'mockId');

    vi.advanceTimersByTime(1000);

    // ASSERT
    expect(renderTooltipSpy).toHaveBeenCalledTimes(1);

    const [, renderTooltipHtmlContentArg] = renderTooltipSpy.mock.calls[0];
    expect(renderTooltipHtmlContentArg).toContain('loading.gif');
  });
});
