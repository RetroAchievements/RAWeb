import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { eventAtom } from '@/features/events/state/events.atoms';
import { render, screen } from '@/test';
import { createRaEvent } from '@/test/factories';
import { createAchievement } from '@/test/factories/createAchievement';
import { createEventAchievement } from '@/test/factories/createEventAchievement';

import { AchievementDateMeta } from './AchievementDateMeta';

dayjs.extend(utc);

describe('AchievementDateMeta', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<AchievementDateMeta achievement={createAchievement()} />, {
      jotaiAtoms: [
        [eventAtom, createRaEvent({ state: 'active' })],
        //
      ],
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('should render unlock date for unlocked achievement.', () => {
    // ARRANGE
    const achievement = createAchievement({
      unlockedAt: '2024-03-15T12:00:00Z',
    });

    render(<AchievementDateMeta achievement={achievement} />, {
      jotaiAtoms: [
        [eventAtom, createRaEvent({ state: 'active' })],
        //
      ],
    });

    // ASSERT
    expect(screen.getByText(/Unlocked/)).toBeInTheDocument();
    expect(screen.getByText(/Mar 15, 2024/)).toBeInTheDocument();
  });

  it('should render hardcore unlock date when available.', () => {
    // ARRANGE
    const achievement = createAchievement({
      unlockedAt: '2024-03-15T12:00:00Z',
      unlockedHardcoreAt: '2024-03-16T12:00:00Z',
    });

    render(<AchievementDateMeta achievement={achievement} />, {
      jotaiAtoms: [
        [eventAtom, createRaEvent({ state: 'active' })],
        //
      ],
    });

    // ASSERT
    expect(screen.getByText(/Unlocked/)).toBeInTheDocument();
    expect(screen.getByText(/Mar 16, 2024/)).toBeInTheDocument();
  });

  it('should render active status for current event achievement.', () => {
    // ARRANGE
    const now = dayjs.utc();
    const eventAchievement = createEventAchievement({
      activeFrom: now.subtract(1, 'day').toISOString(),
      activeThrough: now.add(1, 'day').toISOString(),
    });

    render(
      <AchievementDateMeta achievement={createAchievement()} eventAchievement={eventAchievement} />,
      {
        jotaiAtoms: [
          [eventAtom, createRaEvent({ state: 'active' })],
          //
        ],
      },
    );

    // ASSERT
    expect(screen.getByText(/Active until/)).toBeInTheDocument();
  });

  it('should render upcoming status for future event achievement.', () => {
    // ARRANGE
    const now = dayjs.utc();
    const eventAchievement = createEventAchievement({
      activeFrom: now.add(1, 'day').toISOString(),
      activeThrough: now.add(2, 'day').toISOString(),
    });

    render(
      <AchievementDateMeta achievement={createAchievement()} eventAchievement={eventAchievement} />,
      {
        jotaiAtoms: [
          [eventAtom, createRaEvent({ state: 'active' })],
          //
        ],
      },
    );

    // ASSERT
    expect(screen.getByText(/Starts/)).toBeInTheDocument();
  });

  it('should render expired status for past event achievement.', () => {
    // ARRANGE
    const now = dayjs.utc();
    const eventAchievement = createEventAchievement({
      activeFrom: now.subtract(2, 'day').toISOString(),
      activeThrough: now.subtract(1, 'day').toISOString(),
    });

    render(
      <AchievementDateMeta achievement={createAchievement()} eventAchievement={eventAchievement} />,
    );

    // ASSERT
    expect(screen.getByText(/Ended/)).toBeInTheDocument();
  });

  it('should not render anything for locked non-event achievement.', () => {
    // ARRANGE
    const achievement = createAchievement({
      unlockedAt: undefined,
      unlockedHardcoreAt: undefined,
    });

    render(<AchievementDateMeta achievement={achievement} />);

    // ASSERT
    expect(screen.queryByTestId('date-meta')).not.toBeInTheDocument();
  });

  it('should not show event status for evergreen events.', () => {
    // ARRANGE
    const now = dayjs.utc();
    const eventAchievement = createEventAchievement({
      activeFrom: now.subtract(1, 'day').toISOString(),
      activeThrough: now.add(1, 'day').toISOString(),
    });

    render(
      <AchievementDateMeta achievement={createAchievement()} eventAchievement={eventAchievement} />,
      {
        jotaiAtoms: [
          [eventAtom, createRaEvent({ state: 'evergreen' })],
          //
        ],
      },
    );

    // ASSERT
    expect(screen.queryByTestId('date-meta')).not.toBeInTheDocument();
  });

  it('should apply custom className when provided.', () => {
    // ARRANGE
    const achievement = createAchievement({
      unlockedAt: '2024-03-15T12:00:00Z',
    });

    const { container } = render(
      <AchievementDateMeta achievement={achievement} className="custom-class" />,
    );

    // ASSERT
    expect(container.firstChild).toHaveClass('custom-class');
  });
});
