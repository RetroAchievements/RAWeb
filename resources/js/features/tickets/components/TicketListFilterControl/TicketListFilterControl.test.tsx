import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';

import type { TicketListFilterProperty } from '../../models';
import { openPropertySubmenu } from '../../test/openPropertySubmenu';
import { TicketListFilterControl } from './TicketListFilterControl';

const statusProperty: TicketListFilterProperty = {
  id: 'status',
  label: 'Status',
  noFilterValue: 'all',
  options: [
    { value: 'unresolved', label: 'Open', count: 487, glyphState: 'open' },
    { value: 'resolved', label: 'Resolved', count: 0, glyphState: 'resolved' },
    { value: 'closed', label: 'Closed', count: 12, glyphState: 'closed' },
    { value: 'all', label: 'All', count: 607 },
  ],
};

const emulatorProperty: TicketListFilterProperty = {
  id: 'emulator',
  label: 'Emulator',
  noFilterValue: 'all',
  options: [
    { value: 'all', label: 'All' },
    ...['Bizhawk', 'Dolphin', 'FCEUX', 'Gens', 'Mesen', 'PCSX2', 'RALibRetro', 'RetroArch'].map(
      (name) => ({ value: name, label: name }),
    ),
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

describe('Component: TicketListFilterControl', () => {
  beforeEach(() => {
    window.HTMLElement.prototype.hasPointerCapture = vi.fn();
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
    window.HTMLElement.prototype.setPointerCapture = vi.fn();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <TicketListFilterControl
        columnFilters={[]}
        properties={[statusProperty, typeProperty]}
        setColumnFilters={vi.fn()}
      />,
    );

    // ASSERT
    expect(container).toBeTruthy();
    expect(screen.getByTestId('add-filter')).toBeVisible();
  });

  it('keeps an accessible name when its text is hidden', () => {
    // ARRANGE
    const { rerender } = render(
      <TicketListFilterControl
        columnFilters={[]}
        isLabelHidden={true}
        properties={[statusProperty]}
        setColumnFilters={vi.fn()}
      />,
    );

    // ASSERT
    expect(screen.getByTestId('add-filter')).toHaveAccessibleName('Filter');
    expect(screen.getByText('Filter', { selector: 'span' })).toHaveClass('sm:hidden');

    rerender(
      <TicketListFilterControl
        columnFilters={[]}
        isLabelHidden={false}
        properties={[statusProperty]}
        setColumnFilters={vi.fn()}
      />,
    );

    expect(screen.getByText('Filter', { selector: 'span' })).not.toHaveClass('sm:hidden');
  });

  it('given the user opens the menu, lists each possible property', async () => {
    // ARRANGE
    render(
      <TicketListFilterControl
        columnFilters={[]}
        properties={[statusProperty, typeProperty]}
        setColumnFilters={vi.fn()}
      />,
    );

    // ACT
    await userEvent.click(screen.getByTestId('add-filter'));

    // ASSERT
    expect(screen.getByTestId('filter-property-status')).toHaveTextContent('Status');
    expect(screen.getByTestId('filter-property-type')).toHaveTextContent('Type');
  });

  it('given a property has an active filter, shows a dot', async () => {
    // ARRANGE
    render(
      <TicketListFilterControl
        columnFilters={[{ id: 'status', value: ['closed'] }]}
        properties={[statusProperty, typeProperty]}
        setColumnFilters={vi.fn()}
      />,
    );

    // ACT
    await userEvent.click(screen.getByTestId('add-filter'));

    // ASSERT
    expect(screen.getByTestId('filter-property-status-active')).toHaveAccessibleName('Active');
    expect(screen.queryByTestId('filter-property-type-active')).not.toBeInTheDocument();
  });

  it('given a property does not have an active filter, does not show a dot', async () => {
    // ARRANGE
    render(
      <TicketListFilterControl
        columnFilters={[{ id: 'status', value: ['all'] }]}
        properties={[statusProperty]}
        setColumnFilters={vi.fn()}
      />,
    );

    // ACT
    await userEvent.click(screen.getByTestId('add-filter'));

    // ASSERT
    expect(screen.queryByTestId('filter-property-status-active')).not.toBeInTheDocument();
  });

  it('given the user opens a property submenu, shows its values alongside associated counts', async () => {
    // ARRANGE
    render(
      <TicketListFilterControl
        columnFilters={[]}
        properties={[statusProperty, typeProperty]}
        setColumnFilters={vi.fn()}
      />,
    );

    // ACT
    await openPropertySubmenu(0);

    // ASSERT
    expect(screen.getByRole('menuitem', { name: /open/i })).toHaveTextContent('487');
    expect(screen.getByRole('menuitem', { name: /resolved/i })).toHaveTextContent('0');
  });

  it('given the user picks a value, sets the filter', async () => {
    // ARRANGE
    const setColumnFilters = vi.fn();

    render(
      <TicketListFilterControl
        columnFilters={[{ id: 'status', value: ['unresolved'] }]}
        properties={[statusProperty, typeProperty]}
        setColumnFilters={setColumnFilters}
      />,
    );

    // ACT
    await openPropertySubmenu(1);
    await userEvent.click(screen.getByRole('menuitem', { name: 'Did not trigger' }));

    // ASSERT
    const [updater] = setColumnFilters.mock.calls[0];
    expect(updater([{ id: 'status', value: ['unresolved'] }])).toEqual([
      { id: 'status', value: ['unresolved'] },
      { id: 'type', value: ['2'] },
    ]);
  });

  it('given a status option, shows the same glyph the ticket rows use', async () => {
    // ARRANGE
    render(
      <TicketListFilterControl
        columnFilters={[]}
        properties={[statusProperty]}
        setColumnFilters={vi.fn()}
      />,
    );

    // ACT
    await openPropertySubmenu(0);

    // ASSERT
    expect(screen.getByTestId('glyph-unresolved')).toBeVisible();
    expect(screen.getByTestId('glyph-resolved')).toBeVisible();
    expect(screen.queryByTestId('glyph-all')).not.toBeInTheDocument();
  });

  it('given a property has a value, marks it, and marks the no-filter option when it has none', async () => {
    // ARRANGE
    const { rerender } = render(
      <TicketListFilterControl
        columnFilters={[{ id: 'status', value: ['resolved'] }]}
        properties={[statusProperty]}
        setColumnFilters={vi.fn()}
      />,
    );

    // ACT
    await openPropertySubmenu(0);
    expect(screen.getByTestId('checked-resolved')).toBeVisible();

    await userEvent.keyboard('{Escape}');
    rerender(
      <TicketListFilterControl
        columnFilters={[]}
        properties={[statusProperty]}
        setColumnFilters={vi.fn()}
      />,
    );
    await openPropertySubmenu(0);

    // ASSERT
    expect(screen.getByTestId('checked-all')).toBeVisible();
  });

  it('given a property with relatively few options, omits the search box due to the entire list already being visible', async () => {
    // ARRANGE
    render(
      <TicketListFilterControl
        columnFilters={[]}
        properties={[statusProperty]}
        setColumnFilters={vi.fn()}
      />,
    );

    // ACT
    await openPropertySubmenu(0);

    // ASSERT
    expect(screen.queryByPlaceholderText('Search options...')).not.toBeInTheDocument();
    expect(screen.getByRole('menuitem', { name: /open/i })).toBeVisible();
  });

  it('given a property with lots of possible values, displays a functional search box', async () => {
    // ARRANGE
    const setColumnFilters = vi.fn();

    render(
      <TicketListFilterControl
        columnFilters={[]}
        properties={[emulatorProperty]}
        setColumnFilters={setColumnFilters}
      />,
    );

    // ACT
    await openPropertySubmenu(0);
    await userEvent.type(screen.getByPlaceholderText('Search options...'), 'retro');

    // ASSERT
    expect(screen.getByRole('option', { name: 'RetroArch' })).toBeVisible();
    expect(screen.queryByRole('option', { name: 'Dolphin' })).not.toBeInTheDocument();

    await userEvent.click(screen.getByRole('option', { name: 'RetroArch' }));

    const [updater] = setColumnFilters.mock.calls[0];
    expect(updater([])).toEqual([{ id: 'emulator', value: ['RetroArch'] }]);
  });

  it('given the search matches nothing, shows an empty state', async () => {
    // ARRANGE
    render(
      <TicketListFilterControl
        columnFilters={[]}
        properties={[emulatorProperty]}
        setColumnFilters={vi.fn()}
      />,
    );

    // ACT
    await openPropertySubmenu(0);
    await userEvent.type(screen.getByPlaceholderText('Search options...'), 'zzz');

    // ASSERT
    expect(screen.getByText('No results found.')).toBeVisible();
  });

  describe('mobile', () => {
    beforeEach(() => {
      Object.defineProperty(window, 'matchMedia', {
        writable: true,
        value: vi.fn().mockImplementation((query: string) => ({
          matches: query === '(max-width: 639px)',
          media: query,
          onchange: null,
          addListener: vi.fn(),
          removeListener: vi.fn(),
          addEventListener: vi.fn(),
          removeEventListener: vi.fn(),
          dispatchEvent: vi.fn(),
        })),
      });
    });

    it('maintains one panel rather than branching submenus', async () => {
      // ARRANGE
      render(
        <TicketListFilterControl
          columnFilters={[]}
          properties={[statusProperty, typeProperty]}
          setColumnFilters={vi.fn()}
        />,
      );

      // ACT
      await userEvent.click(screen.getByTestId('add-filter'));
      await userEvent.click(screen.getByTestId('filter-property-status'));

      // ASSERT
      expect(screen.getByRole('menuitem', { name: /open/i })).toBeVisible();
      expect(screen.queryByTestId('filter-property-type')).not.toBeInTheDocument();
    });

    it('given the user drills into a filter, the back button returns to the user to the list of filters', async () => {
      // ARRANGE
      render(
        <TicketListFilterControl
          columnFilters={[]}
          properties={[statusProperty, typeProperty]}
          setColumnFilters={vi.fn()}
        />,
      );

      // ACT
      await userEvent.click(screen.getByTestId('add-filter'));
      await userEvent.click(screen.getByTestId('filter-property-type'));
      await userEvent.click(screen.getByTestId('filter-back'));

      // ASSERT
      expect(screen.getByTestId('filter-property-status')).toBeVisible();
      expect(screen.getByTestId('filter-property-type')).toBeVisible();
    });

    it('given the user picks a filter value, sets the filter and closes the panel', async () => {
      // ARRANGE
      const setColumnFilters = vi.fn();

      render(
        <TicketListFilterControl
          columnFilters={[]}
          properties={[statusProperty, typeProperty]}
          setColumnFilters={setColumnFilters}
        />,
      );

      // ACT
      await userEvent.click(screen.getByTestId('add-filter'));
      await userEvent.click(screen.getByTestId('filter-property-type'));
      await userEvent.click(screen.getByRole('menuitem', { name: 'Did not trigger' }));

      // ASSERT
      const [updater] = setColumnFilters.mock.calls[0];
      expect(updater([])).toEqual([{ id: 'type', value: ['2'] }]);

      expect(screen.queryByTestId('filter-property-type')).not.toBeInTheDocument();
    });
  });

  it('supports keyboard selection without a search field', async () => {
    // ARRANGE
    const setColumnFilters = vi.fn();

    render(
      <TicketListFilterControl
        columnFilters={[]}
        properties={[statusProperty]}
        setColumnFilters={setColumnFilters}
      />,
    );

    // ACT
    await openPropertySubmenu(0);
    await userEvent.keyboard('{ArrowDown}{Enter}');

    // ASSERT
    const [updater] = setColumnFilters.mock.calls[0];
    expect(updater([])).toEqual([{ id: 'status', value: ['resolved'] }]);
  });
});
