import { render, screen } from '@/test';

import { NewSetsList } from './NewSetsList';

describe('Component: NewSetsList', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<NewSetsList />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays an accessible heading', () => {
    // ARRANGE
    render(<NewSetsList />);

    // ASSERT
    expect(screen.getByRole('heading', { name: /just released/i })).toBeVisible();
  });

  it('displays accessible table headers', () => {
    // ARRANGE
    render(<NewSetsList />);

    // ASSERT
    expect(screen.getByRole('columnheader', { name: /game/i })).toBeVisible();
    expect(screen.getByRole('columnheader', { name: /dev/i })).toBeVisible();
    expect(screen.getByRole('columnheader', { name: /type/i })).toBeVisible();
    expect(screen.getByRole('columnheader', { name: /finished/i })).toBeVisible();
  });

  it.todo('renders multiple table rows');
  it.todo('displays game and user avatars in each row');
  it.todo('displays an empty state when there are no new sets');
  it.todo('displays the correct timestamps for each set in the list');

  it('displays a link to the "See More" page', () => {
    // ARRANGE
    render(<NewSetsList />);

    // ASSERT
    const linkEl = screen.getByRole('link', { name: /see more/i });

    expect(linkEl).toBeVisible();
    expect(linkEl).toHaveAttribute('href', 'claims.completed');
  });
});
