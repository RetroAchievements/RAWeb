import { BaseTable, BaseTableBody } from '@/common/components/+vendor/BaseTable';
import { render, screen } from '@/test';

import { SystemHeaderRow } from './SystemHeaderRow';

// Suppress validateDOMNesting() errors.
console.error = vi.fn();

describe('Component: SystemHeaderRow', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <BaseTable>
        <BaseTableBody>
          <SystemHeaderRow columnCount={3} gameCount={1} systemName="PlayStation 2" />,
        </BaseTableBody>
      </BaseTable>,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a system name, displays it as the main text', () => {
    // ARRANGE
    render(
      <BaseTable>
        <BaseTableBody>
          <SystemHeaderRow columnCount={3} gameCount={1} systemName="PlayStation 2" />
        </BaseTableBody>
      </BaseTable>,
    );

    // ASSERT
    expect(screen.getByText(/playstation 2/i)).toBeVisible();
  });

  it('given a column count, applies it as a colspan and aria-colspan to the cell', () => {
    // ARRANGE
    render(
      <BaseTable>
        <BaseTableBody>
          <SystemHeaderRow columnCount={5} gameCount={1} systemName="PlayStation 2" />
        </BaseTableBody>
      </BaseTable>,
    );

    // ASSERT
    const cellEl = screen.getByRole('columnheader');

    expect(cellEl.getAttribute('colspan')).toEqual('5');
    expect(cellEl.getAttribute('aria-colspan')).toEqual('5');
  });

  it('given multiple games are in the group, displays the game count', () => {
    // ARRANGE
    render(
      <BaseTable>
        <BaseTableBody>
          <SystemHeaderRow columnCount={3} gameCount={5} systemName="PlayStation 2" />
        </BaseTableBody>
      </BaseTable>,
    );

    // ASSERT
    expect(screen.getByText(/5 games/i)).toBeVisible();
  });

  it('given only one game, does not display the game count', () => {
    // ARRANGE
    render(
      <BaseTable>
        <BaseTableBody>
          <SystemHeaderRow columnCount={3} gameCount={1} systemName="PlayStation 2" />
        </BaseTableBody>
      </BaseTable>,
    );

    // ASSERT
    expect(screen.queryByText(/games/i)).not.toBeInTheDocument();
  });

  it('assigns proper aria roles to the table elements', () => {
    // ARRANGE
    render(
      <BaseTable>
        <BaseTableBody>
          <SystemHeaderRow columnCount={3} gameCount={1} systemName="PlayStation 2" />
        </BaseTableBody>
      </BaseTable>,
    );

    // ASSERT
    expect(screen.getByRole('rowheader')).toBeVisible();
    expect(screen.getByRole('columnheader')).toBeVisible();
    expect(screen.getByRole('group')).toBeVisible();
  });
});
