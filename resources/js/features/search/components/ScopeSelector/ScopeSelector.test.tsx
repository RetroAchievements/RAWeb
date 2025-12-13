import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';

import { ScopeSelector } from './ScopeSelector';

describe('Component: ScopeSelector', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<ScopeSelector scope="all" onScopeChange={vi.fn()} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('renders all scope buttons', () => {
    // ARRANGE
    render(<ScopeSelector scope="all" onScopeChange={vi.fn()} />);

    // ASSERT
    expect(screen.getAllByRole('button').length).toEqual(8);
  });

  it('renders the expected scope labels', () => {
    // ARRANGE
    render(<ScopeSelector scope="all" onScopeChange={vi.fn()} />);

    // ASSERT
    expect(screen.getByRole('button', { name: /all/i })).toBeVisible();
    expect(screen.getByRole('button', { name: /games/i })).toBeVisible();
    expect(screen.getByRole('button', { name: /users/i })).toBeVisible();
    expect(screen.getByRole('button', { name: /hubs/i })).toBeVisible();
    expect(screen.getByRole('button', { name: /achievements/i })).toBeVisible();
    expect(screen.getByRole('button', { name: /events/i })).toBeVisible();
    expect(screen.getByRole('button', { name: /forum posts/i })).toBeVisible();
    expect(screen.getByRole('button', { name: /comments/i })).toBeVisible();
  });

  it('given the user clicks a scope button, calls onScopeChange with the correct scope', async () => {
    // ARRANGE
    const onScopeChange = vi.fn();
    render(<ScopeSelector scope="all" onScopeChange={onScopeChange} />);

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /games/i }));

    // ASSERT
    expect(onScopeChange).toHaveBeenCalledWith('games');
  });

  it('given a scope is selected, marks that button as pressed', () => {
    // ARRANGE
    render(<ScopeSelector scope="users" onScopeChange={vi.fn()} />);

    // ASSERT
    expect(screen.getByRole('button', { name: /users/i })).toBePressed();
  });

  it('given a scope is not selected, marks that button as not pressed', () => {
    // ARRANGE
    render(<ScopeSelector scope="users" onScopeChange={vi.fn()} />);

    // ASSERT
    expect(screen.getByRole('button', { name: /games/i })).not.toBePressed();
  });
});
