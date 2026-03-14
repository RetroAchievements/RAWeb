import { render, screen } from '@/test';
import {
  createAchievement,
  createEventAchievement,
  createGame,
  createSystem,
} from '@/test/factories';

import { AchievementEventInfo } from './AchievementEventInfo';

describe('Component: AchievementEventInfo', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const achievement = createAchievement();

    const { container } = render(<AchievementEventInfo />, {
      pageProps: {
        achievement,
        eventAchievement: createEventAchievement({
          sourceAchievement: createAchievement({
            game: createGame({ title: 'Sonic the Hedgehog', system: createSystem() }),
          }),
          activeFrom: '2025-01-06',
          activeThrough: '2025-01-12',
        }),
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there is no eventAchievement, renders nothing', () => {
    // ARRANGE
    const achievement = createAchievement();

    render(<AchievementEventInfo />, {
      pageProps: { achievement, eventAchievement: null },
    });

    // ASSERT
    expect(screen.queryByRole('table')).not.toBeInTheDocument();
  });

  it('given the event achievement has a source game, displays the source game avatar', () => {
    // ARRANGE
    const achievement = createAchievement();

    render(<AchievementEventInfo />, {
      pageProps: {
        achievement,
        eventAchievement: createEventAchievement({
          sourceAchievement: createAchievement({
            game: createGame({ title: 'Sonic the Hedgehog', system: createSystem() }),
          }),
        }),
      },
    });

    // ASSERT
    expect(screen.getByText(/sonic the hedgehog/i)).toBeVisible();
  });

  it('given the event achievement has no source game, shows a dash for the From row', () => {
    // ARRANGE
    const achievement = createAchievement();

    render(<AchievementEventInfo />, {
      pageProps: {
        achievement,
        eventAchievement: createEventAchievement({
          sourceAchievement: null,
        }),
      },
    });

    // ASSERT
    expect(screen.getByText(/from/i)).toBeVisible();
    expect(screen.getAllByText('–').length).toBeGreaterThan(0);
  });

  it('given the event achievement has active dates, displays the date range', () => {
    // ARRANGE
    const achievement = createAchievement();

    render(<AchievementEventInfo />, {
      pageProps: {
        achievement,
        eventAchievement: createEventAchievement({
          sourceAchievement: null,
          activeFrom: '2025-01-06',
          activeThrough: '2025-01-12',
        }),
      },
    });

    // ASSERT
    expect(screen.getByText(/jan 6, 2025/i)).toBeVisible();
    expect(screen.getByText(/jan 12, 2025/i)).toBeVisible();
  });

  it('given the event achievement has no active dates, shows a dash for the Active row', () => {
    // ARRANGE
    const achievement = createAchievement();

    render(<AchievementEventInfo />, {
      pageProps: {
        achievement,
        eventAchievement: createEventAchievement({
          sourceAchievement: null,
          activeFrom: undefined,
          activeThrough: undefined,
        }),
      },
    });

    // ASSERT
    expect(screen.getByText(/active/i)).toBeVisible();
    expect(screen.getAllByText('–').length).toBeGreaterThan(0);
  });

  it('given the event achievement has both a source game and active dates, displays both', () => {
    // ARRANGE
    const achievement = createAchievement();

    render(<AchievementEventInfo />, {
      pageProps: {
        achievement,
        eventAchievement: createEventAchievement({
          sourceAchievement: createAchievement({
            game: createGame({ title: 'Action Man: Robot Atak', system: createSystem() }),
          }),
          activeFrom: '2025-01-06',
          activeThrough: '2025-01-12',
        }),
      },
    });

    // ASSERT
    expect(screen.getByText(/from/i)).toBeVisible();
    expect(screen.getByText(/action man: robot atak/i)).toBeVisible();
    expect(screen.getByText(/active/i)).toBeVisible();
  });
});
