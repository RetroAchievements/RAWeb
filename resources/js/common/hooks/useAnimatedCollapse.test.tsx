import userEvent from '@testing-library/user-event';
import type { FC } from 'react';
import { useState } from 'react';

import { render, screen } from '@/test';

import { useAnimatedCollapse } from './useAnimatedCollapse';

const TestComponent: FC = () => {
  const { contentHeight, contentRef, isOpen, setIsOpen } = useAnimatedCollapse();
  const [extraContent, setExtraContent] = useState(false);

  return (
    <div>
      <button onClick={() => setIsOpen(!isOpen)} data-testid="toggle">
        Toggle
      </button>
      <p data-testid="height">{contentHeight}</p>

      {isOpen ? (
        <div ref={contentRef} data-testid="content">
          <p>Content</p>
          {extraContent ? <p>Extra content that makes the container taller</p> : null}
        </div>
      ) : null}

      <button onClick={() => setExtraContent(true)} data-testid="add-content">
        Add Content
      </button>
    </div>
  );
};

describe('Hook: useAnimatedCollapse', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<TestComponent />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('starts in the closed state', () => {
    // ARRANGE
    render(<TestComponent />);

    // ASSERT
    expect(screen.queryByTestId('content')).not.toBeInTheDocument();
  });

  it('opens when toggled', async () => {
    // ARRANGE
    render(<TestComponent />);

    // ACT
    await userEvent.click(screen.getByTestId('toggle'));

    // ASSERT
    expect(screen.getByTestId('content')).toBeInTheDocument();
  });
});
