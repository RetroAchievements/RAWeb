import { screen } from '@testing-library/dom';
import {
  describe,
  expect,
  it,
  vi
} from 'vitest';

import { attachTooltipToElement } from './attachTooltipToElement';
import * as LoadDynamicTooltipModule from './utils/loadDynamicTooltip';
import * as RenderTooltipModule from './utils/renderTooltip';

function render() {
  document.body.innerHTML = /** @html */ `
    <div data-testid="anchor-element"></div>
  `;
}

describe('Util: attachTooltipToElement', () => {
  it('is defined #sanity', () => {
    expect(attachTooltipToElement).toBeDefined();
  });

  it('given an element, can allow it to have a static tooltip', () => {
    // ARRANGE
    vi.useFakeTimers();

    const renderTooltipSpy = vi.spyOn(RenderTooltipModule, 'renderTooltip');

    render();

    const anchorEl = screen.getByTestId('anchor-element');
    const addEventListenerSpy = vi.spyOn(anchorEl, 'addEventListener');

    const mockTooltipContent = '<div>Hello, world!</div>';

    // ACT
    attachTooltipToElement(anchorEl, {
      staticHtmlContent: mockTooltipContent,
    });

    // ASSERT
    expect(addEventListenerSpy).toHaveBeenCalledTimes(3);

    expect(addEventListenerSpy).toHaveBeenCalledWith('mouseleave', expect.anything());
    expect(addEventListenerSpy).toHaveBeenCalledWith('mousemove', expect.anything());

    // Manually trigger the mouseover listener to verify it is set up correctly.
    const callArgs = addEventListenerSpy.mock.calls;
    (callArgs[0][1] as EventListener)(new MouseEvent('mouseover'));

    vi.advanceTimersByTime(100);

    expect(renderTooltipSpy).toHaveBeenCalledWith(anchorEl, mockTooltipContent, 8, 6);
  });

  it('given an element, can allow it to have a tooltip with dynamically-fetched content', () => {
    // ARRANGE
    vi.useFakeTimers();

    const loadDynamicTooltipSpy = vi.spyOn(LoadDynamicTooltipModule, 'loadDynamicTooltip');

    render();

    const anchorEl = screen.getByTestId('anchor-element');
    const addEventListenerSpy = vi.spyOn(anchorEl, 'addEventListener');

    // ACT
    attachTooltipToElement(anchorEl, {
      dynamicType: 'mockType',
      dynamicId: 'mockId',
      dynamicContext: 'game',
    });

    // ASSERT
    expect(addEventListenerSpy).toHaveBeenCalledTimes(3);

    expect(addEventListenerSpy).toHaveBeenCalledWith('mouseleave', expect.anything());
    expect(addEventListenerSpy).toHaveBeenCalledWith('mousemove', expect.anything());

    // Manually trigger the mouseover listener to verify it is set up correctly.
    const callArgs = addEventListenerSpy.mock.calls;
    (callArgs[0][1] as EventListener)(new MouseEvent('mouseover'));

    vi.advanceTimersByTime(100);

    expect(loadDynamicTooltipSpy).toHaveBeenCalled();
  });
});
