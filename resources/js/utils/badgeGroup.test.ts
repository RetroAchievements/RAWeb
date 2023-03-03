// @vitest-environment jsdom

import {
  describe,
  expect,
  it,
  vi
} from 'vitest';
import { screen, waitFor } from '@testing-library/dom';
import userEvent from '@testing-library/user-event';

import { badgeGroup } from './badgeGroup';
import * as CookieModule from './cookie';

global.badgeGroup = badgeGroup;

describe('Util: badgeGroup', () => {
  describe('handleExpandGroupClick', () => {
    it('should expand the awards container and remove the expand button', async () => {
      // ARRANGE
      (document as any).badgeGroup = badgeGroup;
      document.body.innerHTML = /** @html */`
        <div>
          <div 
            id="Game Awards-container" 
            class="group-fade" 
            data-testid="awards-container"
            style="max-height: 75vh;"
          ></div>

          <button 
            id="Game Awards-expand-button" 
            onclick="badgeGroup.handleExpandGroupClick(event, 'Game Awards-container')"
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

        expect(awardsContainerEl.classList.contains('group-fade')).not.toBeTruthy();

        expect(buttonEl).not.toBeInTheDocument();
      });
    });
  });

  describe('saveExpandableBadgeGroupsPreference', () => {
    it('sets a cookie and pops an alert', () => {
      const mockAlert = vi.fn();
      globalThis.alert = mockAlert;
      const setCookieSpy = vi.spyOn(CookieModule, 'setCookie');

      badgeGroup.saveExpandableBadgeGroupsPreference('mock-cookie-name', true);

      expect(mockAlert).toHaveBeenCalledWith('Saved!');
      expect(setCookieSpy).toHaveBeenCalledWith('mock-cookie-name', 'true');
    });
  });
});
