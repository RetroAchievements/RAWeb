import { render, screen } from '@/test';
import { createEventAward, createRaEvent } from '@/test/factories';

import { BigStatusLabel } from './BigStatusLabel';

describe('Component: BigStatusLabel', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const event = createRaEvent();
    const { container } = render(<BigStatusLabel event={event} isMastered={false} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the event has no awards, shows Unfinished in muted text', () => {
    // ARRANGE
    const event = createRaEvent({ eventAwards: [] });

    render(<BigStatusLabel event={event} isMastered={false} />);

    // ASSERT
    const label = screen.getByText(/unfinished/i);
    expect(label).toBeVisible();
    expect(label.parentElement).toHaveClass('text-text-muted');
  });

  it('given the player has a single earned award, shows Awarded in yellow text', () => {
    // ARRANGE
    const event = createRaEvent({
      eventAwards: [createEventAward({ earnedAt: '2023-01-01', label: 'Bronze' })],
    });

    render(<BigStatusLabel event={event} isMastered={true} />);

    // ASSERT
    const label = screen.getByText(/awarded/i);
    expect(label).toBeVisible();
    expect(label.parentElement).toHaveClass('text-yellow-400');
  });

  it('given the player has multiple awards with one earned, shows the earned award label in neutral text', () => {
    // ARRANGE
    const event = createRaEvent({
      eventAwards: [
        createEventAward({ earnedAt: '2023-01-01', label: 'Bronze' }),
        createEventAward({ earnedAt: null, label: 'Silver' }),
      ],
    });

    render(<BigStatusLabel event={event} isMastered={false} />);

    // ASSERT
    const label = screen.getByText(/bronze/i);
    expect(label).toBeVisible();
    expect(label.parentElement).toHaveClass('text-neutral-300');
  });

  it('given the player has all awards earned, shows the highest earned award label in gold text', () => {
    // ARRANGE
    const event = createRaEvent({
      eventAwards: [
        createEventAward({ earnedAt: '2023-01-01', label: 'Bronze', pointsRequired: 12 }),
        createEventAward({ earnedAt: '2023-01-02', label: 'Silver', pointsRequired: 30 }),
      ],
    });

    render(<BigStatusLabel event={event} isMastered={true} />);

    // ASSERT
    const label = screen.getByText(/silver/i);
    expect(label).toBeVisible();
    expect(label.parentElement).toHaveClass('text-yellow-400');
  });

  it('given the player has "mastered" the event and there are no event award tiers, shows the Awarded label in gold text', () => {
    // ARRANGE
    const event = createRaEvent({
      eventAwards: [],
    });

    render(<BigStatusLabel event={event} isMastered={true} />);

    // ASSERT
    const label = screen.getByText(/awarded/i);
    expect(label).toBeVisible();
    expect(label.parentElement).toHaveClass('text-yellow-400');
  });
});
