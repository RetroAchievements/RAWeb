import { render, screen } from '@/test';

import { AchievementOfTheWeek } from './AchievementOfTheWeek';

describe('Component: AchievementOfTheWeek', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<AchievementOfTheWeek />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays an accessible heading', () => {
    // ARRANGE
    render(<AchievementOfTheWeek />);

    // ASSERT
    expect(screen.getByRole('heading', { name: /achievement of the week/i })).toBeVisible();
  });

  it.todo('given there is no game system, still renders successfully');
  it.todo('has an accessible link to the event page');
  it.todo('has a link to the achievement');
  it.todo('has a link to the game');
  it.todo('displays the achievement title and description');
  it.todo('displays the game title and description');
});
