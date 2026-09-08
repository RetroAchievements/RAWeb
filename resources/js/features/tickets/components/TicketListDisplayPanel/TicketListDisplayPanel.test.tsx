import type { VisibilityState } from '@tanstack/react-table';
import userEvent from '@testing-library/user-event';
import type { ComponentProps } from 'react';

import { render, screen } from '@/test';
import type { TranslatedString } from '@/types/i18next';

import type { TicketListColumnDefinition } from '../../models';
import { TICKET_LIST_COLUMN_IDS } from '../../utils/ticketListColumnIds';
import { TicketListDisplayPanel } from './TicketListDisplayPanel';

const columnDefinitions: TicketListColumnDefinition[] = [
  { id: 'id', enableHiding: false, meta: { t_label: 'ID' as TranslatedString } },
  { id: 'game', meta: { t_label: 'Game' as TranslatedString } },
];

const defaultColumnVisibility = Object.fromEntries(
  TICKET_LIST_COLUMN_IDS.map((columnId) => [columnId, ['id', 'game'].includes(columnId)]),
) as VisibilityState;

type TicketListDisplayPanelProps = ComponentProps<typeof TicketListDisplayPanel>;

function renderTicketListDisplayPanel(overrides: Partial<TicketListDisplayPanelProps> = {}) {
  const props: TicketListDisplayPanelProps = {
    columnDefinitions,
    columnVisibility: defaultColumnVisibility,
    hasColumnVisibilityOverrides: false,
    onResetDisplay: vi.fn(),
    onSortChange: vi.fn(),
    onToggleColumn: vi.fn(),
    sortParam: '-createdAt',
    ...overrides,
  };

  return { ...render(<TicketListDisplayPanel {...props} />), props };
}

describe('Component: TicketListDisplayPanel', () => {
  beforeEach(() => {
    window.HTMLElement.prototype.hasPointerCapture = vi.fn();
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
    window.HTMLElement.prototype.setPointerCapture = vi.fn();
  });

  it('given the user picks a different sort field, keeps the current direction', async () => {
    // ARRANGE
    const onSortChange = vi.fn();
    renderTicketListDisplayPanel({ onSortChange });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Display' }));
    await userEvent.click(screen.getByTestId('sort-field'));
    await userEvent.click(screen.getByRole('option', { name: 'Status' }));

    // ASSERT
    expect(onSortChange).toHaveBeenCalledWith('-state');
  });

  it('given the user toggles the direction, flips it while keeping the field', async () => {
    // ARRANGE
    const onSortChange = vi.fn();
    renderTicketListDisplayPanel({ onSortChange });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Display' }));
    await userEvent.click(screen.getByTestId('toggle-sort-direction'));

    // ASSERT
    expect(onSortChange).toHaveBeenCalledWith('createdAt');
  });

  it('given an ascending date sort, labels the direction control as oldest first', async () => {
    // ARRANGE
    renderTicketListDisplayPanel({ sortParam: 'createdAt' });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Display' }));

    // ASSERT
    expect(screen.getByRole('button', { name: 'Oldest first' })).toBeVisible();
  });

  it('given the user opens the display panel, does not focus the sort direction', async () => {
    // ARRANGE
    renderTicketListDisplayPanel();
    const displayButton = screen.getByRole('button', { name: 'Display' });

    // ACT
    await userEvent.click(displayButton);

    // ASSERT
    expect(displayButton).toHaveFocus();
    expect(screen.getByRole('button', { name: 'Newest first' })).not.toHaveFocus();
    expect(screen.queryByRole('tooltip')).not.toBeInTheDocument();
  });

  it('given the status sort, labels its directions as ascending and descending', async () => {
    // ARRANGE
    const { props, rerender } = renderTicketListDisplayPanel({ sortParam: 'state' });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Display' }));

    // ASSERT
    expect(screen.getByRole('button', { name: 'Ascending' })).toBeVisible();
    rerender(<TicketListDisplayPanel {...props} sortParam="-state" />);
    expect(screen.getByRole('button', { name: 'Descending' })).toBeVisible();
  });

  it('given a column is hidden, marks its control as unpressed', async () => {
    // ARRANGE
    renderTicketListDisplayPanel({
      columnVisibility: { ...defaultColumnVisibility, game: false },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Display' }));

    // ASSERT
    expect(screen.queryByTestId('column-toggle-id')).not.toBeInTheDocument();
    expect(screen.getByTestId('column-toggle-game')).toHaveAttribute('aria-pressed', 'false');
    expect(screen.getByTestId('column-toggle-game')).toHaveAttribute('data-state', 'off');
  });
});
