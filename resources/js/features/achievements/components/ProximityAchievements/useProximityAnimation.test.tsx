/* eslint-disable jsx-a11y/no-noninteractive-element-to-interactive-role -- doesn't matter in a test suite */

import { router } from '@inertiajs/react';
import type { FC } from 'react';
import { act } from 'react';

import { fireEvent, render, screen } from '@/test';

import { useProximityAnimation } from './useProximityAnimation';

interface TestItem {
  id: number;
  href: string;
  label: string;
}

interface TestHarnessProps {
  currentIndex: number;
  items: TestItem[];
  wireRefs?: boolean;
  wireTitleRefs?: boolean;
  shouldSkipAnimation?: boolean;
}

// Wires up the hook's refs to real DOM elements so we can exercise the animation logic in isolation.
const TestHarness: FC<TestHarnessProps> = ({
  currentIndex,
  items,
  wireRefs = true,
  wireTitleRefs = true,
  shouldSkipAnimation = false,
}) => {
  const {
    containerRef,
    listRef,
    indicatorRef,
    itemRefs,
    titleRefs,
    handleItemClick,
    handleItemKeyDown,
    handleItemMouseEnter,
    handleItemMouseLeave,
  } = useProximityAnimation({ currentIndex, itemCount: items.length, shouldSkipAnimation });

  return (
    <div ref={wireRefs ? containerRef : undefined} data-testid="container">
      <ol ref={wireRefs ? listRef : undefined} data-testid="list">
        <div ref={wireRefs ? indicatorRef : undefined} data-testid="indicator" />
        {items.map((item, index) => (
          <li
            key={item.id}
            ref={(el) => {
              itemRefs.current[index] = el;
            }}
            data-testid={`item-${index}`}
            role="button"
            tabIndex={0}
            onClick={() => handleItemClick(index, item.href)}
            onKeyDown={(e) => handleItemKeyDown(e, index, item.href)}
            onMouseEnter={() => handleItemMouseEnter(item.href)}
            onMouseLeave={handleItemMouseLeave}
          >
            <p
              ref={(el) => {
                if (wireTitleRefs) {
                  titleRefs.current[index] = el;
                }
              }}
              data-testid={`title-${index}`}
            >
              {item.label}
            </p>
          </li>
        ))}
      </ol>
    </div>
  );
};

function buildItems(count: number): TestItem[] {
  return Array.from({ length: count }, (_, i) => ({
    id: i,
    href: `/achievement/${i}`,
    label: `Achievement ${i}`,
  }));
}

describe('Hook: useProximityAnimation', () => {
  beforeEach(() => {
    // Our simulated DOM doesn't implement layout, so offsetHeight is always 0.
    // Mock it so the hook can compute real positions.
    Object.defineProperty(HTMLElement.prototype, 'offsetHeight', {
      configurable: true,
      get: () => 48,
    });
  });

  afterEach(() => {
    Object.defineProperty(HTMLElement.prototype, 'offsetHeight', {
      configurable: true,
      get: () => 0,
    });
  });

  it('given more than 5 items, sets a maxHeight on the container', () => {
    // ARRANGE
    const items = buildItems(7);

    render(<TestHarness currentIndex={3} items={items} />);

    // ASSERT
    const container = screen.getByTestId('container');

    // ... 5 visible items * 48px each + 8px for py-1 padding ...
    expect(container.style.maxHeight).toEqual(`${48 * 5 + 8}px`);
  });

  it('given 5 or fewer items, does not constrain the container height', () => {
    // ARRANGE
    const items = buildItems(4);

    render(<TestHarness currentIndex={1} items={items} />);

    // ASSERT
    const container = screen.getByTestId('container');
    expect(container.style.maxHeight).toEqual('');
  });

  it('positions the indicator at the current item on mount', () => {
    // ARRANGE
    const items = buildItems(3);

    render(<TestHarness currentIndex={1} items={items} />);

    // ASSERT
    const indicator = screen.getByTestId('indicator');

    // ... itemTop = 1 * 48 = 48, plus an 8px inset = 56 ...
    expect(indicator.style.top).toEqual('56px');

    // ... 48 - (8 * 2) = 32 ...
    expect(indicator.style.height).toEqual('32px');
    expect(indicator.style.visibility).toEqual('visible');
  });

  it('locks each item to a consistent height to prevent subpixel drift', () => {
    // ARRANGE
    const items = buildItems(3);

    render(<TestHarness currentIndex={0} items={items} />);

    // ASSERT
    for (let i = 0; i < items.length; i++) {
      expect(screen.getByTestId(`item-${i}`).style.height).toEqual('48px');
    }
  });

  it('given itemCount is zero, does not attempt DOM measurements', () => {
    // ASSERT
    render(<TestHarness currentIndex={-1} items={[]} />); // should not throw
  });

  it('given mouseLeave fires after mouseEnter, cancels the prefetch timeout', () => {
    // ARRANGE
    vi.useFakeTimers();
    const items = buildItems(3);

    render(<TestHarness currentIndex={0} items={items} />);

    // ACT
    fireEvent.mouseEnter(screen.getByTestId('item-1'));
    fireEvent.mouseLeave(screen.getByTestId('item-1'));

    act(() => {
      vi.advanceTimersByTime(600);
    });

    // ASSERT
    expect(router.prefetch).not.toHaveBeenCalled();
  });

  it('given Space key is pressed on an item, triggers navigation', () => {
    // ARRANGE
    vi.useFakeTimers();
    const items = buildItems(3);

    render(<TestHarness currentIndex={0} items={items} />);

    // ACT
    fireEvent.keyDown(screen.getByTestId('item-1'), { key: ' ' });

    act(() => {
      vi.advanceTimersByTime(300);
    });

    // ASSERT
    expect(router.visit).toHaveBeenCalledWith('/achievement/1');
  });

  it('given getComputedStyle returns colors, cross-fades label text on click', () => {
    // ARRANGE
    vi.useFakeTimers();
    vi.spyOn(window, 'getComputedStyle').mockReturnValue({
      color: 'rgb(100, 100, 100)',
    } as CSSStyleDeclaration);

    const items = buildItems(3);

    render(<TestHarness currentIndex={0} items={items} />);

    // ACT
    fireEvent.click(screen.getByTestId('item-1'));

    // ASSERT
    const prevTitle = screen.getByTestId('title-0');
    const nextTitle = screen.getByTestId('title-1');

    // ... both titles should have their color set by the cross-fade logic ...
    expect(prevTitle.style.color).toEqual('rgb(100, 100, 100)');
    expect(nextTitle.style.color).toEqual('rgb(100, 100, 100)');
  });

  it('given a click is already in progress, ignores subsequent clicks', () => {
    // ARRANGE
    vi.useFakeTimers();
    const items = buildItems(3);

    render(<TestHarness currentIndex={0} items={items} />);

    // ACT
    fireEvent.click(screen.getByTestId('item-1'));
    fireEvent.click(screen.getByTestId('item-2'));

    act(() => {
      vi.advanceTimersByTime(300);
    });

    // ASSERT
    expect(router.visit).toHaveBeenCalledTimes(1);
    expect(router.visit).toHaveBeenCalledWith('/achievement/1');
  });

  it('given the current index is clicked, does not trigger navigation', () => {
    // ARRANGE
    vi.useFakeTimers();
    const items = buildItems(3);

    render(<TestHarness currentIndex={1} items={items} />);

    // ACT
    fireEvent.click(screen.getByTestId('item-1'));

    act(() => {
      vi.advanceTimersByTime(300);
    });

    // ASSERT
    expect(router.visit).not.toHaveBeenCalled();
  });

  it('always animates on click without crashing', () => {
    // ARRANGE
    vi.useFakeTimers();
    const items = buildItems(3);

    render(<TestHarness currentIndex={-1} items={items} />);

    // ACT
    fireEvent.click(screen.getByTestId('item-1'));

    act(() => {
      vi.advanceTimersByTime(300);
    });

    // ASSERT
    expect(router.visit).toHaveBeenCalledWith('/achievement/1');
  });

  it('given list or indicator refs are not attached, bails out of the click handler gracefully', () => {
    // ARRANGE
    vi.useFakeTimers();
    const items = buildItems(3);

    render(<TestHarness currentIndex={0} items={items} wireRefs={false} />);

    // ACT
    fireEvent.click(screen.getByTestId('item-1'));

    act(() => {
      vi.advanceTimersByTime(300);
    });

    // ASSERT
    expect(router.visit).not.toHaveBeenCalled();
  });

  it('given title refs are not attached, skips the color cross-fade without crashing', () => {
    // ARRANGE
    vi.useFakeTimers();
    const items = buildItems(3);

    render(<TestHarness currentIndex={0} items={items} wireTitleRefs={false} />);

    // ACT
    fireEvent.click(screen.getByTestId('item-1'));

    act(() => {
      vi.advanceTimersByTime(300);
    });

    // ASSERT
    expect(router.visit).toHaveBeenCalledWith('/achievement/1');
  });

  it('given shouldSkipAnimation is true, navigates immediately without waiting for animation', () => {
    // ARRANGE
    vi.useFakeTimers();
    const items = buildItems(3);

    render(<TestHarness currentIndex={0} items={items} shouldSkipAnimation={true} />);

    // ACT
    fireEvent.click(screen.getByTestId('item-1'));

    // ASSERT
    // ... navigation should happen synchronously, no need to advance timers ...
    expect(router.visit).toHaveBeenCalledWith('/achievement/1');
  });
});
