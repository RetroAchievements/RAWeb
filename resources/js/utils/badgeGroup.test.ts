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
  describe('handleSizeToggleButtonClick', () => {
    it('given the button is clicked to expand, it should expand the awards container, switch to a collapse button, and persist a preference', async () => {
      // ARRANGE
      const setCookieSpy = vi.spyOn(CookieModule, 'setCookie');

      (document as any).badgeGroup = badgeGroup;
      document.body.innerHTML = /** @html */`
        <div>
          <div 
            id="Game Awards-container" 
            class="group-fade max-h-[64vh]" 
            data-testid="awards-container"
          ></div>

          <button 
            id="Game Awards-expand-button" 
            onclick="badgeGroup.handleSizeToggleButtonClick(event, 'Game Awards-container', 100)"
          >
            Expand (100)
          </button>
        </div>
      `;

      const buttonEl = screen.getByRole('button', { name: /expand/i });

      // ACT
      await userEvent.click(buttonEl);

      // ASSERT
      await waitFor(() => {
        const awardsContainerEl = screen.getByTestId('awards-container');

        expect(awardsContainerEl.style.getPropertyValue('mask-image')).toEqual('');

        expect(awardsContainerEl.classList.contains('max-h-[64vh]')).not.toBeTruthy();
        expect(awardsContainerEl.classList.contains('group-fade')).not.toBeTruthy();

        expect(screen.getByRole('button')).toHaveTextContent(/collapse/i);

        expect(setCookieSpy).toHaveBeenCalledWith(expect.anything(), 'true');
      });
    });
  });

  it('given the button is clicked to collapse, it should collapse the awards container, switch to an expand button, and persist a preference', async () => {
    // ARRANGE
    const setCookieSpy = vi.spyOn(CookieModule, 'setCookie');

    (document as any).badgeGroup = badgeGroup;
    document.body.innerHTML = /** @html */`
      <div>
        <div 
          id="Game Awards-container" 
          class="group-fade max-h-[64vh]" 
          data-testid="awards-container"
        ></div>

        <button 
          id="Game Awards-expand-button" 
          onclick="badgeGroup.handleSizeToggleButtonClick(event, 'Game Awards-container', 100)"
        >
          Collapse
        </button>
      </div>
    `;

    const buttonEl = screen.getByRole('button', { name: /collapse/i });

    // ACT
    await userEvent.click(buttonEl);

    // ASSERT
    await waitFor(() => {
      const awardsContainerEl = screen.getByTestId('awards-container');

      expect(awardsContainerEl.classList.contains('max-h-[64vh]')).toBeTruthy();
      expect(awardsContainerEl.classList.contains('group-fade')).toBeTruthy();

      expect(screen.getByRole('button')).toHaveTextContent(/expand/i);

      expect(setCookieSpy).toHaveBeenCalledWith(expect.anything(), 'false');
    });
  });

  describe('saveExpandableBadgeGroupsPreference', () => {
    it('sets a cookie and pops an alert', () => {
      const setCookieSpy = vi.spyOn(CookieModule, 'setCookie');

      badgeGroup.saveExpandableBadgeGroupsPreference(true);

      expect(setCookieSpy).toHaveBeenCalledWith('prefers_always_expanded_badge_groups', 'true');
    });
  });
});
