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
  { id: 'reporter', meta: { t_label: 'Reporter' as TranslatedString } },
];

const defaultColumnVisibility = Object.fromEntries(
  TICKET_LIST_COLUMN_IDS.map((columnId) => [columnId, ['id', 'reporter'].includes(columnId)]),
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
    serverDefaultSortParam: '-createdAt',
    sortFields: ['createdAt', 'state', 'resolvedAt'],
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

  it('given the status sort, labels its directions as open first and closed first', async () => {
    // ARRANGE
    const { props, rerender } = renderTicketListDisplayPanel({ sortParam: 'state' });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Display' }));

    // ASSERT
    expect(screen.getByRole('button', { name: 'Open first' })).toBeVisible();
    rerender(<TicketListDisplayPanel {...props} sortParam="-state" />);
    expect(screen.getByRole('button', { name: 'Closed first' })).toBeVisible();
  });

  it('given a grouping sort, labels its directions as A to Z and most tickets first', async () => {
    // ARRANGE
    const { props, rerender } = renderTicketListDisplayPanel({
      sortFields: ['createdAt', 'state', 'resolvedAt', 'game'],
      sortParam: 'game',
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Display' }));

    // ASSERT
    expect(screen.getByRole('button', { name: 'A to Z' })).toBeVisible();
    rerender(<TicketListDisplayPanel {...props} sortParam="-game" />);
    expect(screen.getByRole('button', { name: 'Most tickets first' })).toBeVisible();
  });

  it('given the user picks a grouping field, puts the groups with the most tickets first', async () => {
    // ARRANGE
    const onSortChange = vi.fn();
    renderTicketListDisplayPanel({
      onSortChange,
      sortFields: ['createdAt', 'state', 'resolvedAt', 'game'],
      sortParam: '-createdAt',
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Display' }));
    await userEvent.click(screen.getByTestId('sort-field'));
    await userEvent.click(screen.getByRole('option', { name: 'Game' }));

    // ASSERT
    expect(onSortChange).toHaveBeenCalledWith('-game');
  });

  it('given the user leaves a grouping field, goes back to the default direction', async () => {
    // ARRANGE
    const onSortChange = vi.fn();
    renderTicketListDisplayPanel({
      onSortChange,
      serverDefaultSortParam: '-createdAt',
      sortFields: ['createdAt', 'state', 'resolvedAt', 'game'],
      sortParam: 'game',
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Display' }));
    await userEvent.click(screen.getByTestId('sort-field'));
    await userEvent.click(screen.getByRole('option', { name: 'Created' }));

    // ASSERT
    expect(onSortChange).toHaveBeenCalledWith('-createdAt');
  });

  it('given the scope offers no grouping field, does not include the option', async () => {
    // ARRANGE
    renderTicketListDisplayPanel({ sortFields: ['createdAt', 'state', 'resolvedAt'] });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Display' }));
    await userEvent.click(screen.getByTestId('sort-field'));

    // ASSERT
    expect(screen.getAllByRole('option')).toHaveLength(3);
  });

  it('given a column is hidden, marks its control as unpressed', async () => {
    // ARRANGE
    renderTicketListDisplayPanel({
      columnVisibility: { ...defaultColumnVisibility, reporter: false },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Display' }));

    // ASSERT
    expect(screen.queryByTestId('column-toggle-id')).not.toBeInTheDocument();
    expect(screen.getByTestId('column-toggle-reporter')).toHaveAttribute('aria-pressed', 'false');
    expect(screen.getByTestId('column-toggle-reporter')).toHaveAttribute('data-state', 'off');
  });
});
