import { render, screen } from '@/test';

import { DataTablePaginationScrollTarget } from './DataTablePaginationScrollTarget';

describe('Component: DataTablePaginationScrollTarget', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <DataTablePaginationScrollTarget>Hello, world</DataTablePaginationScrollTarget>,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('renders children', () => {
    // ARRANGE
    render(<DataTablePaginationScrollTarget>Hello, world</DataTablePaginationScrollTarget>);

    // ASSERT
    expect(screen.getByText(/hello, world/i)).toBeVisible();
  });
});
