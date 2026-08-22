import userEvent from '@testing-library/user-event';

import { screen } from '@/test';

/**
 * Opens one property's value list in the ticket filter menu.
 *
 * Radix uses pointer events to trigger when submenus should open.
 * JSDOM does not produce pointer events. This helper util drives
 * the keyboard a11y path instead.
 */
export async function openPropertySubmenu(propertyIndex: number): Promise<void> {
  await userEvent.click(screen.getByTestId('add-filter'));

  for (let pressCount = 0; pressCount <= propertyIndex; pressCount += 1) {
    await userEvent.keyboard('{ArrowDown}');
  }

  await userEvent.keyboard('{ArrowRight}');
}
