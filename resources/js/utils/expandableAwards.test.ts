// @vitest-environment jsdom

import {
  describe,
  expect,
  it,
  vi
} from 'vitest';
import { screen, waitFor } from '@testing-library/dom';
import userEvent from '@testing-library/user-event';

import { expandableAwards } from './expandableAwards';
import * as CookieModule from './cookie';

global.expandableAwards = expandableAwards;

describe('Util: expandableAwards', () => {
  describe('handleExpandAwardsClick', () => {
    it('should expand the awards container and remove the expand button', async () => {
      // ARRANGE
      (document as any).expandableAwards = expandableAwards;
      document.body.innerHTML = /** @html */`
        <div>
          <div 
            id="Game Awards-container" 
            class="awards-fade" 
            data-testid="awards-container"
            style="max-height: 75vh;"
          ></div>

          <button 
            id="Game Awards-expand-button" 
            onclick="expandableAwards.handleExpandAwardsClick(event, 'Game Awards-container')"
          >
            Expand
          </button>
        </div>
      `;

      const buttonEl = screen.getByRole('button', { name: /expand/i });

      // ACT
      await userEvent.click(buttonEl);

      // ASSERT
      await waitFor(() => {
        const awardsContainerEl = screen.getByTestId('awards-container');

        expect(awardsContainerEl.style.getPropertyValue('max-height')).toEqual('100000px');
        expect(awardsContainerEl.style.getPropertyValue('mask-image')).toEqual('');

        expect(awardsContainerEl.classList.contains('awards-fade')).not.toBeTruthy();

        expect(buttonEl).not.toBeInTheDocument();
      });
    });
  });

  describe('saveExpandableAwardsPreference', () => {
    it('sets a cookie and pops an alert', () => {
      const mockAlert = vi.fn();
      globalThis.alert = mockAlert;
      const setCookieSpy = vi.spyOn(CookieModule, 'setCookie');

      expandableAwards.saveExpandableAwardsPreference('mock-cookie-name', true);

      expect(mockAlert).toHaveBeenCalledWith('Saved!');
      expect(setCookieSpy).toHaveBeenCalledWith('mock-cookie-name', 'true');
    });
  });
});
