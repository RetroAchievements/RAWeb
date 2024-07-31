import { describe, expect, it, vi } from 'vitest';

import { pinTooltipToCursorPosition } from './pinTooltipToCursorPosition';
import * as UpdateTooltipPositionModule from './updateTooltipPosition';

describe('Util: pinTooltipToCursorPosition', () => {
  it('is defined #sanity', () => {
    expect(pinTooltipToCursorPosition).toBeDefined();
  });

  it('given there is a tracked mouse position and active tooltip element, makes a call to update the tooltip current position', () => {
    // ARRANGE
    const updateTooltipPositionSpy = vi.spyOn(UpdateTooltipPositionModule, 'updateTooltipPosition');

    const anchorEl = document.createElement('div');
    const tooltipEl = document.createElement('div');

    // ACT
    pinTooltipToCursorPosition(anchorEl, tooltipEl, 20, 20);

    // ASSERT
    expect(updateTooltipPositionSpy).toHaveBeenCalledWith(anchorEl, tooltipEl, 20 + 12, 20 + 16);
  });

  it('given there is no active tooltip element, does not try to change a tooltip position', () => {
    // ARRANGE
    const updateTooltipPositionSpy = vi.spyOn(UpdateTooltipPositionModule, 'updateTooltipPosition');

    const anchorEl = document.createElement('div');
    const tooltipEl = null;

    // ACT
    pinTooltipToCursorPosition(anchorEl, tooltipEl, 20, 20);

    // ASSERT
    expect(updateTooltipPositionSpy).not.toHaveBeenCalled();
  });
});
