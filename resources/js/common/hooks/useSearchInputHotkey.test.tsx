import userEvent from '@testing-library/user-event';
import type { FC } from 'react';

import { render, screen } from '@/test';

import { useSearchInputHotkey } from './useSearchInputHotkey';

interface TestComponentProps {
  isEnabled: boolean;
  hotkey: string;
}

const TestComponent: FC<TestComponentProps> = ({ isEnabled, hotkey }) => {
  const { hotkeyInputRef } = useSearchInputHotkey({ isEnabled, key: hotkey });

  return (
    <div>
      <input ref={hotkeyInputRef} type="text" placeholder="Search input" />
      <button>Other focusable element</button>
    </div>
  );
};

describe('Hook: useSearchInputHotkey', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<TestComponent isEnabled={true} hotkey="/" />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the hotkey is pressed when enabled, focuses the search input', async () => {
    // ARRANGE
    render(<TestComponent isEnabled={true} hotkey="/" />);

    const searchInput = screen.getByRole('textbox');
    const button = screen.getByRole('button');

    // ... focus something else first ...
    button.focus();
    expect(button).toHaveFocus();

    // ACT
    await userEvent.keyboard('/');

    // ASSERT
    expect(searchInput).toHaveFocus();
  });

  it('given the hotkey is pressed when disabled, does not focus the search input', async () => {
    // ARRANGE
    render(<TestComponent isEnabled={false} hotkey="/" />);

    const searchInput = screen.getByRole('textbox');
    const button = screen.getByRole('button');

    // ... focus something else first ...
    button.focus();
    expect(button).toHaveFocus();

    // ACT
    await userEvent.keyboard('/');

    // ASSERT
    expect(searchInput).not.toHaveFocus();
    expect(button).toHaveFocus();
  });
});
