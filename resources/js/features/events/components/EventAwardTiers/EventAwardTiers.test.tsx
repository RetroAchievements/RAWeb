import { render, screen } from '@/test';
import {
  createAchievement,
  createEventAchievement,
  createEventAward,
  createGame,
  createRaEvent,
} from '@/test/factories';

import { EventAwardTiers } from './EventAwardTiers';

describe('Component: EventAwardTiers', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<EventAwardTiers event={createRaEvent()} numMasters={0} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given an event with no awards, should render nothing', () => {
    // ARRANGE
    const event = createRaEvent({ eventAwards: [] });
    render(<EventAwardTiers event={event} numMasters={0} />);

    // ASSERT
    expect(screen.queryByTestId('award-tiers')).not.toBeInTheDocument();
  });

  it('given an event with multiple award tiers, should show the Award Tiers heading', () => {
    // ARRANGE
    const event = createRaEvent({
      legacyGame: createGame(),
      eventAchievements: [createEventAchievement()],
      eventAwards: [createEventAward(), createEventAward()],
    });

    render(<EventAwardTiers event={event} numMasters={0} />);

    // ASSERT
    expect(screen.getByText(/award tiers/i)).toBeVisible();
  });

  it('given multiple awards, should render them in descending order by points required', () => {
    // ARRANGE
    const event = createRaEvent({
      legacyGame: createGame(),
      eventAchievements: [createEventAchievement()],
      eventAwards: [
        createEventAward({ pointsRequired: 5, label: '5 points award' }),
        createEventAward({ pointsRequired: 10, label: '10 points award' }),
        createEventAward({ pointsRequired: 3, label: '3 points award' }),
      ],
    });

    render(<EventAwardTiers event={event} numMasters={0} />);

    // ASSERT
    const awardLabels = screen.getAllByText(/points award/i).map((el) => el.textContent);
    expect(awardLabels).toEqual(['10 points award', '5 points award', '3 points award']);
  });

  it('given multiple awards, should render an AwardTierItem for each award', () => {
    // ARRANGE
    const event = createRaEvent({
      legacyGame: createGame(),
      eventAchievements: [createEventAchievement()],
      eventAwards: [createEventAward(), createEventAward(), createEventAward()],
    });

    render(<EventAwardTiers event={event} numMasters={0} />);

    // ASSERT
    const awardItems = screen.getAllByRole('img');
    expect(awardItems).toHaveLength(3);
  });

  it('given there are no event awards, displays a virtual award tier', () => {
    // ARRANGE
    const event = createRaEvent({
      legacyGame: createGame(),
      eventAchievements: [createEventAchievement()],
      eventAwards: [], // !!
    });

    render(<EventAwardTiers event={event} numMasters={1000} />);

    // ASSERT
    expect(screen.getByText(/1,000 players have earned this/i)).toBeVisible();
  });

  it('given there are no event awards and no event achievements, does not display a virtual award tier', () => {
    // ARRANGE
    const event = createRaEvent({
      legacyGame: createGame(),
      eventAchievements: [], // !!
      eventAwards: [], // !!
    });

    render(<EventAwardTiers event={event} numMasters={1000} />);

    // ASSERT
    expect(screen.queryByText(/1,000 players have earned this/i)).not.toBeInTheDocument();
  });

  it('given we need to create a virtual award tier and some lazy properties are missing, does not crash', () => {
    // ARRANGE
    const event = createRaEvent({
      id: 1,
      eventAchievements: [
        createEventAchievement({ achievement: createAchievement({ points: 5 }) }),
        createEventAchievement({ achievement: createAchievement({ points: 10 }) }),
        createEventAchievement({ achievement: createAchievement({ points: 25 }) }),
      ],
      legacyGame: createGame({
        title: undefined, // !!
        badgeUrl: 'https://example.com/badge.jpg',
      }),
    });

    const { container } = render(<EventAwardTiers event={event} numMasters={1000} />);

    // ASSERT
    expect(container).toBeTruthy();
  });
});
