import { render, screen } from '@/test';
import { createAchievement } from '@/test/factories/createAchievement';
import { createUser } from '@/test/factories/createUser';

import { AchievementMetaDetails } from './AchievementMetaDetails';

describe('Component: AchievementMetaDetails', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const achievement = createAchievement({
      developer: createUser(),
      createdAt: '2023-06-15T12:00:00.000000Z',
      modifiedAt: '2024-01-20T08:30:00.000000Z',
    });

    const { container } = render(<AchievementMetaDetails />, {
      pageProps: { achievement },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the developer name', () => {
    // ARRANGE
    const achievement = createAchievement({
      developer: createUser({ displayName: 'Scott' }),
      createdAt: '2023-06-15T12:00:00.000000Z',
      modifiedAt: '2024-01-20T08:30:00.000000Z',
    });

    render(<AchievementMetaDetails />, {
      pageProps: { achievement },
    });

    // ASSERT
    expect(screen.getByText(/created by/i)).toBeVisible();
    expect(screen.getByText('Scott')).toBeVisible();
  });

  it('given the achievement has an active maintainer, displays the maintainer name', () => {
    // ARRANGE
    const achievement = createAchievement({
      developer: createUser(),
      activeMaintainer: createUser({ displayName: 'suspect15' }),
      createdAt: '2023-06-15T12:00:00.000000Z',
      modifiedAt: '2024-01-20T08:30:00.000000Z',
    });

    render(<AchievementMetaDetails />, {
      pageProps: { achievement },
    });

    // ASSERT
    expect(screen.getByText(/maintained by/i)).toBeVisible();
    expect(screen.getByText('suspect15')).toBeVisible();
  });

  it('given the achievement has no active maintainer, does not show the Maintained by row', () => {
    // ARRANGE
    const achievement = createAchievement({
      developer: createUser(),
      activeMaintainer: undefined,
      createdAt: '2023-06-15T12:00:00.000000Z',
      modifiedAt: '2024-01-20T08:30:00.000000Z',
    });

    render(<AchievementMetaDetails />, {
      pageProps: { achievement },
    });

    // ASSERT
    expect(screen.queryByText(/maintained by/i)).not.toBeInTheDocument();
  });

  it('displays the formatted created date', () => {
    // ARRANGE
    const achievement = createAchievement({
      developer: createUser(),
      createdAt: '2023-06-15T12:00:00.000000Z',
      modifiedAt: '2024-01-20T08:30:00.000000Z',
    });

    render(<AchievementMetaDetails />, {
      pageProps: { achievement },
    });

    // ASSERT
    expect(screen.getByText(/jun 15, 2023/i)).toBeVisible();
  });

  it('displays the formatted modified date', () => {
    // ARRANGE
    const achievement = createAchievement({
      developer: createUser(),
      createdAt: '2023-06-15T12:00:00.000000Z',
      modifiedAt: '2024-01-20T08:30:00.000000Z',
    });

    render(<AchievementMetaDetails />, {
      pageProps: { achievement },
    });

    // ASSERT
    expect(screen.getByText(/jan 20, 2024/i)).toBeVisible();
  });
});
