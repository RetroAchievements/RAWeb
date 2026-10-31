import { render } from '@/test';

import { TicketUnlockCountFact } from './TicketUnlockCountFact';

describe('Component: TicketUnlockCountFact', () => {
  it('renders without crashing', () => {
    // ARRANGE
    vi.setSystemTime(new Date('2024-05-11T12:00:00Z'));

    const { container } = render(
      <TicketUnlockCountFact reportedAt="2024-05-01T12:00:00Z" unlocksSinceReported={1} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given zero unlocks since the report, states that nobody unlocked it', () => {
    // ARRANGE
    vi.setSystemTime(new Date('2024-05-11T12:00:00Z'));

    const { container } = render(
      <TicketUnlockCountFact reportedAt="2024-05-01T12:00:00Z" unlocksSinceReported={0} />,
    );

    // ASSERT
    expect(container).toHaveTextContent(
      'No one has unlocked this achievement in the 1 week since the report.',
    );
  });

  it('given a single unlock since the report, uses the singular sentence', () => {
    // ARRANGE
    vi.setSystemTime(new Date('2024-05-11T12:00:00Z'));

    const { container } = render(
      <TicketUnlockCountFact reportedAt="2024-05-01T12:00:00Z" unlocksSinceReported={1} />,
    );

    // ASSERT
    expect(container).toHaveTextContent(
      '1 player has unlocked this achievement in the 1 week since the report.',
    );
  });

  it('given multiple unlocks since the report, uses the plural sentence and formats the count', () => {
    // ARRANGE
    vi.setSystemTime(new Date('2024-05-11T12:00:00Z'));

    const { container } = render(
      <TicketUnlockCountFact reportedAt="2024-05-01T12:00:00Z" unlocksSinceReported={1234} />,
    );

    // ASSERT
    expect(container).toHaveTextContent(
      '1,234 players have unlocked this achievement in the 1 week since the report.',
    );
  });

  it('given the logic changed after the report, measures the window up to the logic change', () => {
    // ARRANGE
    vi.setSystemTime(new Date('2024-09-01T12:00:00Z'));

    const { container } = render(
      <TicketUnlockCountFact
        logicChangedAt="2024-05-15T12:00:00Z"
        reportedAt="2024-05-01T12:00:00Z"
        unlocksSinceReported={3}
      />,
    );

    // ASSERT
    expect(container).toHaveTextContent(
      '3 players unlocked this achievement in the 2 weeks before the logic was updated.',
    );
  });

  it('given the component follows the verdict line and the logic changed, shows the correct label', () => {
    // ARRANGE
    vi.setSystemTime(new Date('2024-09-01T12:00:00Z'));

    const { container } = render(
      <TicketUnlockCountFact
        isFollowUp={true}
        logicChangedAt="2024-05-15T12:00:00Z"
        reportedAt="2024-05-01T12:00:00Z"
        unlocksSinceReported={3}
      />,
    );

    // ASSERT
    expect(container).toHaveTextContent(
      '3 players unlocked it in the 2 weeks after the report, before the logic was updated.',
    );
  });
});
