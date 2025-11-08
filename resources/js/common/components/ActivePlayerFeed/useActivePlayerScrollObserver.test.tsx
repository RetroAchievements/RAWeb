import type { FC } from 'react';

import { act, render, screen, waitFor } from '@/test';

import { useActivePlayerScrollObserver } from './useActivePlayerScrollObserver';

const TestComponent: FC<{ onHasScrolledChange?: (hasScrolled: boolean) => void }> = ({
  onHasScrolledChange,
}) => {
  const { scrollRef, hasScrolled } = useActivePlayerScrollObserver();

  if (onHasScrolledChange) {
    onHasScrolledChange(hasScrolled);
  }

  return <div data-testid="scroll-container" ref={scrollRef as any} />;
};

describe('Hook: useActivePlayerScrollObserver', () => {
  it('renders without crashing', () => {
    // ARRANGE
    render(<TestComponent />);

    // ASSERT
    expect(screen.getByTestId('scroll-container')).toBeDefined();
  });

  it('initializes with hasScrolled as false', () => {
    // ARRANGE
    let capturedHasScrolled = false;
    render(<TestComponent onHasScrolledChange={(val) => (capturedHasScrolled = val)} />);

    // ASSERT
    expect(capturedHasScrolled).toEqual(false);
  });

  it('given the ref is not attached to an element, does not crash', () => {
    // ARRANGE
    const TestComponentWithoutRef: FC = () => {
      const { hasScrolled } = useActivePlayerScrollObserver();

      return <div data-testid="no-ref">{hasScrolled ? 'scrolled' : 'not scrolled'}</div>;
    };

    render(<TestComponentWithoutRef />);

    // ASSERT
    expect(screen.getByTestId('no-ref')).toHaveTextContent('not scrolled');
  });

  it('given scrolling is detected, sets hasScrolled to true', async () => {
    // ARRANGE
    let capturedHasScrolled = false;
    render(<TestComponent onHasScrolledChange={(val) => (capturedHasScrolled = val)} />);

    const scrollContainer = screen.getByTestId('scroll-container');

    // ACT
    act(() => {
      const scrollEvent = new Event('scroll', { bubbles: true });
      scrollContainer.dispatchEvent(scrollEvent);
    });

    // ASSERT
    await waitFor(() => {
      expect(capturedHasScrolled).toEqual(true);
    });
  });

  it('maintains hasScrolled as true even after scrolling stops', async () => {
    // ARRANGE
    let capturedHasScrolled = false;
    render(<TestComponent onHasScrolledChange={(val) => (capturedHasScrolled = val)} />);

    const scrollContainer = screen.getByTestId('scroll-container');

    // ACT
    act(() => {
      const scrollEvent = new Event('scroll', { bubbles: true });
      scrollContainer.dispatchEvent(scrollEvent);
    });

    await waitFor(() => {
      expect(capturedHasScrolled).toEqual(true);
    });

    // ... dispatch another scroll event (simulating continued scrolling) ...
    act(() => {
      const anotherScrollEvent = new Event('scroll', { bubbles: true });
      scrollContainer.dispatchEvent(anotherScrollEvent);
    });

    // ASSERT
    expect(capturedHasScrolled).toEqual(true);
  });
});
