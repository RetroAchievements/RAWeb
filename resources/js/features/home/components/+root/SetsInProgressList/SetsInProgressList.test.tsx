import { render, screen } from '@/test';

import { SetsInProgressList } from './SetsInProgressList';

describe('Component: SetsInProgressList', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<SetsInProgressList />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays an accessible heading', () => {
    // ARRANGE
    render(<SetsInProgressList />);

    // ASSERT
    expect(screen.getByRole('heading', { name: /latest sets in progress/i })).toBeVisible();
  });

  it('displays accessible table headers', () => {
    // ARRANGE
    render(<SetsInProgressList />);

    // ASSERT
    expect(screen.getByRole('columnheader', { name: /game/i })).toBeVisible();
    expect(screen.getByRole('columnheader', { name: /dev/i })).toBeVisible();
    expect(screen.getByRole('columnheader', { name: /type/i })).toBeVisible();
    expect(screen.getByRole('columnheader', { name: /started/i })).toBeVisible();
  });

  it.todo('renders multiple table rows');
  it.todo('displays game and user avatars in each row');
  it.todo('displays an empty state when there are no new sets');
  it.todo('displays the correct timestamps for each set in the list');

  it('displays a link to the "See More" page', () => {
    // ARRANGE
    render(<SetsInProgressList />);

    // ASSERT
    const linkEl = screen.getByRole('link', { name: /see more/i });

    expect(linkEl).toBeVisible();
    expect(linkEl).toHaveAttribute('href', 'claims.active');
  });
});
