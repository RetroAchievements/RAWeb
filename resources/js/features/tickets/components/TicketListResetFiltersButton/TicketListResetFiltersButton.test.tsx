import type { ColumnFiltersState } from '@tanstack/react-table';
import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';

import { TicketListResetFiltersButton } from './TicketListResetFiltersButton';

const serverDefaultColumnFilters: ColumnFiltersState = [{ id: 'status', value: ['unresolved'] }];

describe('Component: TicketListResetFiltersButton', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <TicketListResetFiltersButton
        serverDefaultColumnFilters={serverDefaultColumnFilters}
        setColumnFilters={vi.fn()}
      />,
    );

    // ASSERT
    expect(container).toBeTruthy();
    expect(screen.getByRole('button', { name: /reset/i })).toBeVisible();
  });

  it('given the user clicks the button, restores the default filter values', async () => {
    // ARRANGE
    const setColumnFilters = vi.fn();

    render(
      <TicketListResetFiltersButton
        serverDefaultColumnFilters={serverDefaultColumnFilters}
        setColumnFilters={setColumnFilters}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /reset/i }));

    // ASSERT
    expect(setColumnFilters).toHaveBeenCalledWith(serverDefaultColumnFilters);
  });
});
