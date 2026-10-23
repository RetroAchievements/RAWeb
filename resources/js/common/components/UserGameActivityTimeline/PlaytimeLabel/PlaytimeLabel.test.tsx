import { render, screen } from '@/test';
import { createPlayerGameActivitySession } from '@/test/factories';

import { PlaytimeLabel } from './PlaytimeLabel';

describe('Component: PlaytimeLabel', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <PlaytimeLabel
        session={createPlayerGameActivitySession({ type: 'player-session', duration: 3600 })}
      />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the session is for a manual unlock, renders nothing', () => {
    // ARRANGE
    render(
      <PlaytimeLabel
        session={createPlayerGameActivitySession({ type: 'manual-unlock', duration: 12345 })}
      />,
    );

    // ASSERT
    expect(screen.queryByTestId('playtime-label')).not.toBeInTheDocument();
  });

  it('given the session is for ticket creation, renders nothing', () => {
    // ARRANGE
    render(
      <PlaytimeLabel
        session={createPlayerGameActivitySession({ type: 'ticket-created', duration: 12345 })}
      />,
    );

    // ASSERT
    expect(screen.queryByTestId('playtime-label')).not.toBeInTheDocument();
  });

  it('given the session has no duration, displays Unknown', () => {
    // ARRANGE
    render(
      <PlaytimeLabel
        session={createPlayerGameActivitySession({ type: 'player-session', duration: undefined })}
      />,
    );

    // ASSERT
    expect(screen.getByText(/unknown/i)).toBeVisible();
  });

  it('given the session duration is exactly 60 seconds, displays Boot Only', () => {
    // ARRANGE
    render(
      <PlaytimeLabel
        session={createPlayerGameActivitySession({ type: 'player-session', duration: 60 })}
      />,
    );

    // ASSERT
    expect(screen.getByText(/boot only/i)).toBeVisible();
  });

  it('given the session is reconstructed, shows the "estimated" label', () => {
    // ARRANGE
    render(
      <PlaytimeLabel
        session={createPlayerGameActivitySession({ type: 'reconstructed', duration: 120 })}
      />,
    );

    // ASSERT
    expect(screen.getByText(/estimated/i)).toBeVisible();
  });

  it('given the session has a duration, formats it correctly', () => {
    // ARRANGE
    render(
      <PlaytimeLabel
        session={createPlayerGameActivitySession({ type: 'player-session', duration: 126 })}
      />,
    );

    // ASSERT
    expect(screen.getByText(/2m 6s/i)).toBeVisible();
  });
});
