import { render, screen } from '@/test';

import { AchievementPoints } from './AchievementPoints';

describe('Component: AchievementPoints', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<AchievementPoints points={10} isEvent={false} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there are no points, renders nothing', () => {
    // ARRANGE
    render(<AchievementPoints points={0} isEvent={false} />);

    // ASSERT
    expect(screen.queryByText(/\(0\)/i)).not.toBeInTheDocument();
  });

  it('given it is an event achievement with 1 point, renders nothing', () => {
    // ARRANGE
    render(<AchievementPoints points={1} isEvent={true} />);

    // ASSERT
    expect(screen.queryByText(/\(1\)/i)).not.toBeInTheDocument();
  });

  it('given more than 0 points, displays the points value', () => {
    // ARRANGE
    render(<AchievementPoints points={10} isEvent={false} />);

    // ASSERT
    expect(screen.getByText(/\(10\)/i)).toBeVisible();
  });

  it('given it has weighted points and is not an event, displays both regular and weighted points', () => {
    // ARRANGE
    render(<AchievementPoints points={10} pointsWeighted={20} isEvent={false} />);

    // ASSERT
    expect(screen.getByText(/\(10\)/i)).toBeVisible();
    expect(screen.getByText(/\(20\)/i)).toBeVisible();
  });

  it('given it is an event achievement with weighted points, only shows regular points', () => {
    // ARRANGE
    render(<AchievementPoints points={10} pointsWeighted={20} isEvent={true} />);

    // ASSERT
    expect(screen.getByText(/\(10\)/i)).toBeVisible();
    expect(screen.queryByText(/\(20\)/i)).not.toBeInTheDocument();
  });

  it('formats weighted points correctly', () => {
    // ARRANGE
    render(<AchievementPoints points={10} pointsWeighted={2000} isEvent={false} />);

    // ASSERT
    expect(screen.getByText(/2,000/i)).toBeVisible();
  });
});
