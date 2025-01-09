import { render, screen } from '@/test';
import {
  createParsedUserAgent,
  createPlayerGameActivityEvent,
  createPlayerGameActivitySession,
  createUser,
} from '@/test/factories';

import { ClientLabel } from './ClientLabel';

describe('Component: ClientLabel', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <ClientLabel session={createPlayerGameActivitySession({ type: 'reconstructed' })} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the session is reconstructed, shows a reconstructed label', () => {
    // ARRANGE
    render(<ClientLabel session={createPlayerGameActivitySession({ type: 'reconstructed' })} />);

    // ASSERT
    expect(screen.getByText(/reconstructed session/i)).toBeVisible();
  });

  it('given the session is a manual unlock, shows a manual unlock label', () => {
    // ARRANGE
    const unlocker = createUser({ displayName: 'Searo' });

    render(
      <ClientLabel
        session={createPlayerGameActivitySession({
          type: 'manual-unlock',
          events: [createPlayerGameActivityEvent({ unlocker, type: 'unlock' })],
        })}
      />,
    );

    // ASSERT
    expect(screen.getByText(/searo/i)).toBeVisible();
    expect(screen.getByText(/awarded a manual unlock/i)).toBeVisible();
  });

  it('given the session has no user agent data, shows an unknown emulator message', () => {
    // ARRANGE
    render(
      <ClientLabel
        session={createPlayerGameActivitySession({
          type: 'player-session',
          userAgent: null,
          parsedUserAgent: null,
        })}
      />,
    );

    // ASSERT
    expect(screen.getByText(/unknown emulator/i)).toBeVisible();
  });

  it('given the emulator label comes back as Unknown, shows an unknown emulator message', () => {
    // ARRANGE
    render(
      <ClientLabel
        session={createPlayerGameActivitySession({
          type: 'player-session',
          userAgent: '[not provided]',
          parsedUserAgent: createParsedUserAgent({ client: 'Unknown', clientVersion: 'Unknown' }),
        })}
      />,
    );

    // ASSERT
    expect(screen.getByText(/unknown emulator/i)).toBeVisible();
  });

  it('given the emulator is known, shows the emulator label', () => {
    // ARRANGE
    render(
      <ClientLabel
        session={createPlayerGameActivitySession({
          type: 'player-session',
          userAgent: 'RetroArch/1.19.1 (Linux 4.9) mgba_libretro/0.11-dev',
          parsedUserAgent: createParsedUserAgent({
            client: 'RetroArch',
            clientVersion: '1.19.1',
            os: 'Linux 4.9',
          }),
        })}
      />,
    );

    // ASSERT
    expect(screen.getByText(/retroarch/i)).toBeVisible();
    expect(screen.getByText(/1.19.1/i)).toBeVisible();
    expect(screen.getByText(/linux 4.9/i)).toBeVisible();
  });
});
