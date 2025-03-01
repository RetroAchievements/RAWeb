import { render, screen } from '@/test';
import { createEventAward, createRaEvent } from '@/test/factories';

import { EventAwardTiers } from './EventAwardTiers';

describe('Component: EventAwardTiers', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const event = createRaEvent();

    // ASSERT
    expect(() => render(<EventAwardTiers event={event} />)).not.toThrow();
  });

  it('given an event with no awards, should render nothing', () => {
    // ARRANGE
    const event = createRaEvent({ eventAwards: [] });
    render(<EventAwardTiers event={event} />);

    // ASSERT
    expect(screen.queryByTestId('award-tiers')).not.toBeInTheDocument();
  });

  it('given an event with only one award tier, should render nothing', () => {
    // ARRANGE
    const event = createRaEvent({ eventAwards: [createEventAward()] });
    render(<EventAwardTiers event={event} />);

    // ASSERT
    expect(screen.queryByTestId('award-tiers')).not.toBeInTheDocument();
  });

  it('given an event with multiple award tiers, should show the Award Tiers heading', () => {
    // ARRANGE
    const event = createRaEvent({
      eventAwards: [createEventAward(), createEventAward()],
    });

    render(<EventAwardTiers event={event} />);

    // ASSERT
    expect(screen.getByText(/award tiers/i)).toBeVisible();
  });

  it('given multiple awards, should render them in descending order by points required', () => {
    // ARRANGE
    const event = createRaEvent({
      eventAwards: [
        createEventAward({ pointsRequired: 5, label: '5 points award' }),
        createEventAward({ pointsRequired: 10, label: '10 points award' }),
        createEventAward({ pointsRequired: 3, label: '3 points award' }),
      ],
    });

    render(<EventAwardTiers event={event} />);

    // ASSERT
    const awardLabels = screen.getAllByText(/points award/i).map((el) => el.textContent);
    expect(awardLabels).toEqual(['10 points award', '5 points award', '3 points award']);
  });

  it('given multiple awards, should render an AwardTierItem for each award', () => {
    // ARRANGE
    const event = createRaEvent({
      eventAwards: [createEventAward(), createEventAward(), createEventAward()],
    });

    render(<EventAwardTiers event={event} />);

    // ASSERT
    const awardItems = screen.getAllByRole('img');
    expect(awardItems).toHaveLength(3);
  });
});
