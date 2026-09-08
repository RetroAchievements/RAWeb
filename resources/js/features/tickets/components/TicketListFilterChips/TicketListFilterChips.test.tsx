import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';

import type { TicketListFilterProperty } from '../../models';
import { TicketListFilterChips } from './TicketListFilterChips';

const statusProperty: TicketListFilterProperty = {
  id: 'status',
  label: 'Status',
  noFilterValue: 'all',
  options: [
    { value: 'unresolved', label: 'Open' },
    { value: 'resolved', label: 'Resolved' },
    { value: 'all', label: 'All' },
  ],
};

const typeProperty: TicketListFilterProperty = {
  id: 'type',
  label: 'Type',
  noFilterValue: '0',
  options: [
    { value: '0', label: 'All' },
    { value: '2', label: 'Did not trigger' },
  ],
};

const properties = [statusProperty, typeProperty];

describe('Component: TicketListFilterChips', () => {
  beforeEach(() => {
    window.HTMLElement.prototype.hasPointerCapture = vi.fn();
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
    window.HTMLElement.prototype.setPointerCapture = vi.fn();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <TicketListFilterChips
        columnFilters={[{ id: 'status', value: ['resolved'] }]}
        properties={properties}
        setColumnFilters={vi.fn()}
      />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the filters do not actually narrow the full list, renders nothing', () => {
    // ARRANGE
    render(
      <TicketListFilterChips
        columnFilters={[
          { id: 'status', value: ['all'] },
          { id: 'type', value: ['0'] },
        ]}
        properties={properties}
        setColumnFilters={vi.fn()}
      />,
    );

    // ASSERT
    expect(screen.queryByTestId('chip-status')).not.toBeInTheDocument();
    expect(screen.queryByTestId('chip-type')).not.toBeInTheDocument();
  });

  it('given a filter that does narrow the list, renders a chip that reads as an accessible phrase', () => {
    // ARRANGE
    render(
      <TicketListFilterChips
        columnFilters={[{ id: 'status', value: ['resolved'] }]}
        properties={properties}
        setColumnFilters={vi.fn()}
      />,
    );

    // ASSERT
    expect(screen.getByTestId('chip-status')).toHaveTextContent('Status is Resolved');
  });

  it('given an unknown value, falls back to showing the raw unknown value', () => {
    // ARRANGE
    render(
      <TicketListFilterChips
        columnFilters={[{ id: 'status', value: ['nonsense'] }]}
        properties={properties}
        setColumnFilters={vi.fn()}
      />,
    );

    // ASSERT
    expect(screen.getByTestId('chip-status')).toHaveTextContent('nonsense');
  });

  it('given the user clears a chip, returns its filter value to its nonfiltered state', async () => {
    // ARRANGE
    const setColumnFilters = vi.fn();

    render(
      <TicketListFilterChips
        columnFilters={[{ id: 'status', value: ['resolved'] }]}
        properties={properties}
        setColumnFilters={setColumnFilters}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Remove Status filter' }));

    // ASSERT
    const [updater] = setColumnFilters.mock.calls[0];
    expect(updater([{ id: 'status', value: ['resolved'] }])).toEqual([
      { id: 'status', value: ['all'] },
    ]);
  });

  it('given the user opens a chip, shows the possible filter values and lets the user select one', async () => {
    // ARRANGE
    const setColumnFilters = vi.fn();

    render(
      <TicketListFilterChips
        columnFilters={[{ id: 'status', value: ['resolved'] }]}
        properties={properties}
        setColumnFilters={setColumnFilters}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Change Status filter' }));
    await userEvent.keyboard('{Escape}');
    await userEvent.click(screen.getByRole('button', { name: 'Change Status filter' }));
    await userEvent.click(screen.getByRole('menuitem', { name: /open/i }));

    // ASSERT
    const [updater] = setColumnFilters.mock.calls[0];
    expect(updater([{ id: 'status', value: ['resolved'] }])).toEqual([
      { id: 'status', value: ['unresolved'] },
    ]);
  });

  it('given several filters can narrow the list of results, displays one chip for each filter', () => {
    // ARRANGE
    render(
      <TicketListFilterChips
        columnFilters={[
          { id: 'status', value: ['resolved'] },
          { id: 'type', value: ['2'] },
        ]}
        properties={properties}
        setColumnFilters={vi.fn()}
      />,
    );

    // ASSERT
    expect(screen.getByTestId('chip-status')).toBeVisible();
    expect(screen.getByTestId('chip-type')).toHaveTextContent('Did not trigger');
  });
});
