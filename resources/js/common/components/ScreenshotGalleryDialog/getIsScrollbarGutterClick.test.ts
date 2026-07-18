import type { MouseEvent } from 'react';

import { getIsScrollbarGutterClick } from './getIsScrollbarGutterClick';

interface FakeEventInput {
  clientWidth: number;
  offsetWidth: number;
  offsetX: number;
}

const buildEvent = ({ clientWidth, offsetWidth, offsetX }: FakeEventInput) =>
  ({
    currentTarget: { clientWidth, offsetWidth } as HTMLElement,
    nativeEvent: { offsetX } as MouseEvent['nativeEvent'],
  }) as MouseEvent<HTMLElement>;

describe('Util: getIsScrollbarGutterClick', () => {
  it('given the element has no scrollbar, returns false', () => {
    // ARRANGE
    const event = buildEvent({ clientWidth: 1000, offsetWidth: 1000, offsetX: 999 });

    // ACT
    const result = getIsScrollbarGutterClick(event);

    // ASSERT
    expect(result).toEqual(false);
  });

  it('given the click lands inside the content area of a scrollable element, returns false', () => {
    // ARRANGE
    const event = buildEvent({ clientWidth: 1000, offsetWidth: 1015, offsetX: 500 });

    // ACT
    const result = getIsScrollbarGutterClick(event);

    // ASSERT
    expect(result).toEqual(false);
  });

  it('given the click lands on the scrollbar gutter of a scrollable element, returns true', () => {
    // ARRANGE
    const event = buildEvent({ clientWidth: 1000, offsetWidth: 1015, offsetX: 1010 });

    // ACT
    const result = getIsScrollbarGutterClick(event);

    // ASSERT
    expect(result).toEqual(true);
  });

  it('given the click lands exactly at the content edge of a scrollable element, returns true', () => {
    // ARRANGE
    const event = buildEvent({ clientWidth: 1000, offsetWidth: 1015, offsetX: 1000 });

    // ACT
    const result = getIsScrollbarGutterClick(event);

    // ASSERT
    expect(result).toEqual(true);
  });
});
