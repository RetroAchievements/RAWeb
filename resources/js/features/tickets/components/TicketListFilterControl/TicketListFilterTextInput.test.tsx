import userEvent from '@testing-library/user-event';

import { render, screen, waitFor } from '@/test';

import { TicketListFilterTextInput } from './TicketListFilterTextInput';

describe('Component: TicketListFilterTextInput', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <TicketListFilterTextInput initialValue="" label="Core" onSubmit={vi.fn()} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('autofocuses on mount', () => {
    // ARRANGE
    render(<TicketListFilterTextInput initialValue="" label="Core" onSubmit={vi.fn()} />);

    // ASSERT
    expect(screen.getByRole('textbox', { name: 'Core contains' })).toHaveFocus();
  });

  it('given the user submits a term, propagates the submit', async () => {
    // ARRANGE
    const onSubmit = vi.fn();

    render(<TicketListFilterTextInput initialValue="" label="Core" onSubmit={onSubmit} />);

    // ACT
    await userEvent.type(screen.getByRole('textbox', { name: 'Core contains' }), ' gw {Enter}');

    // ASSERT
    expect(onSubmit).toHaveBeenCalledExactlyOnceWith('gw');
  });

  it('given its own menu trigger takes focus, takes focus back', async () => {
    // ARRANGE
    render(
      <div>
        <button id="core-trigger">Core</button>

        <div role="menu" aria-labelledby="core-trigger">
          <TicketListFilterTextInput initialValue="" label="Core" onSubmit={vi.fn()} />
        </div>
      </div>,
    );

    // ACT
    screen.getByRole('button', { name: 'Core' }).focus();

    // ASSERT
    await waitFor(() => {
      expect(screen.getByRole('textbox', { name: 'Core contains' })).toHaveFocus();
    });
  });

  it('given a different element takes focus, does not take focus back', async () => {
    // ARRANGE
    render(
      <div>
        <button id="core-trigger">Core</button>
        <button id="status-trigger">Status</button>

        <div role="menu" aria-labelledby="core-trigger">
          <TicketListFilterTextInput initialValue="" label="Core" onSubmit={vi.fn()} />
        </div>
      </div>,
    );

    // ACT
    screen.getByRole('button', { name: 'Status' }).focus();
    await Promise.resolve();

    // ASSERT
    expect(screen.getByRole('button', { name: 'Status' })).toHaveFocus();
  });
});
