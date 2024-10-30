import { render, screen } from '@/test';

import { GlobalStatistics } from './GlobalStatistics';

describe('Component: GlobalStatistics', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<GlobalStatistics />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays an accessible heading', () => {
    // ARRANGE
    render(<GlobalStatistics />);

    // ASSERT
    expect(screen.getByRole('heading', { name: /statistics/i })).toBeVisible();
  });

  // use screen.getByLabelText()
  it.todo('displays the correct count of games and has the right href on that element');
  it.todo('displays the correct count of achievements and has the right href on that element');
  it.todo('displays the correct count of games mastered and has the right href on that element');
  it.todo('displays the correct count of games beaten and has the right href on that element');
  it.todo(
    'displays the correct count of registered players and has the right href on that element',
  );
  it.todo(
    'displays the correct count of achievement unlocks and has the right href on that element',
  );
  it.todo('displays the correct count of points earned since site launch');
});
