import userEvent from '@testing-library/user-event';
import axios from 'axios';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen, waitFor } from '@/test';
import {
  createAchievement,
  createEmulator,
  createGame,
  createGameHash,
  createSystem,
  createZiggyProps,
} from '@/test/factories';

import { CreateAchievementTicketMainRoot } from './CreateAchievementTicketMainRoot';

// Suppress "Not implemented: navigation (except hash changes)"
console.error = vi.fn();

describe('Component: CreateAchievementTicketMainRoot', () => {
  beforeEach(() => {
    window.HTMLElement.prototype.hasPointerCapture = vi.fn();
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
    window.HTMLElement.prototype.setPointerCapture = vi.fn();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.CreateAchievementTicketPageProps>(
      <CreateAchievementTicketMainRoot />,
      {
        pageProps: {
          achievement: createAchievement(),
          auth: { user: createAuthenticatedUser() },
          gameHashes: [createGameHash()],
          emulators: [createEmulator()],
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays an accessible heading', () => {
    // ARRANGE
    render<App.Platform.Data.CreateAchievementTicketPageProps>(
      <CreateAchievementTicketMainRoot />,
      {
        pageProps: {
          achievement: createAchievement(),
          auth: { user: createAuthenticatedUser() },
          gameHashes: [createGameHash()],
          emulators: [createEmulator()],
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    expect(screen.getByRole('heading', { name: /create ticket/i })).toBeVisible();
  });

  it('displays breadcrumbs', () => {
    // ARRANGE
    const system = createSystem({ name: 'Nintendo 64' });
    const game = createGame({ system, title: 'StarCraft 64' });
    const achievement = createAchievement({ game, title: 'Saved' });

    render<App.Platform.Data.CreateAchievementTicketPageProps>(
      <CreateAchievementTicketMainRoot />,
      {
        pageProps: {
          achievement,
          auth: { user: createAuthenticatedUser() },
          gameHashes: [createGameHash()],
          emulators: [createEmulator()],
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    expect(screen.getByRole('navigation', { name: /breadcrumb/i })).toBeVisible();
    expect(screen.getByRole('link', { name: /all games/i })).toBeVisible();
    expect(screen.getByRole('link', { name: /nintendo 64/i })).toBeVisible();
    expect(screen.getByRole('link', { name: /starcraft 64/i })).toBeVisible();
    expect(screen.getAllByRole('link', { name: /saved/i })[0]).toBeVisible();
  });

  it('given there is no type query param, does not set a default value for the Issue select field', () => {
    // ARRANGE
    render<App.Platform.Data.CreateAchievementTicketPageProps>(
      <CreateAchievementTicketMainRoot />,
      {
        pageProps: {
          achievement: createAchievement(),
          auth: { user: createAuthenticatedUser() },
          gameHashes: [createGameHash()],
          emulators: [createEmulator()],
          ziggy: createZiggyProps({ query: {} }),
        },
      },
    );

    // ASSERT
    const issueFieldEl = screen.getByRole('combobox', { name: /issue/i });

    expect(issueFieldEl).toBeVisible();
    expect(issueFieldEl).toHaveTextContent(/select an issue/i);
  });

  it('given there is a type=1 query param, sets the Issue select to "Triggered at the wrong time"', () => {
    // ARRANGE
    render<App.Platform.Data.CreateAchievementTicketPageProps>(
      <CreateAchievementTicketMainRoot />,
      {
        pageProps: {
          achievement: createAchievement(),
          auth: { user: createAuthenticatedUser() },
          gameHashes: [createGameHash()],
          emulators: [createEmulator()],
          ziggy: createZiggyProps({ query: { type: '1' } }),
        },
      },
    );

    // ASSERT
    const issueFieldEl = screen.getByRole('combobox', { name: /issue/i });

    expect(issueFieldEl).toBeVisible();
    expect(issueFieldEl).toHaveTextContent(/triggered at the wrong time/i);
    expect(screen.getByText(/the achievement unlocked unexpectedly/i)).toBeVisible();
  });

  it('given there is a type=2 query param, sets the Issue select to "Did not trigger"', () => {
    // ARRANGE
    render<App.Platform.Data.CreateAchievementTicketPageProps>(
      <CreateAchievementTicketMainRoot />,
      {
        pageProps: {
          achievement: createAchievement(),
          auth: { user: createAuthenticatedUser() },
          gameHashes: [createGameHash()],
          emulators: [createEmulator()],
          ziggy: createZiggyProps({ query: { type: '2' } }),
        },
      },
    );

    // ASSERT
    const issueFieldEl = screen.getByRole('combobox', { name: /issue/i });

    expect(issueFieldEl).toBeVisible();
    expect(issueFieldEl).toHaveTextContent(/did not trigger/i);
    expect(screen.getByText(/it didn't unlock/i)).toBeVisible();
  });

  it('given the user has no known selected emulator, tells them to select an emulator', () => {
    // ARRANGE
    const emulators = [
      createEmulator({ name: 'Bizhawk' }),
      createEmulator({ name: 'RALibRetro' }),
      createEmulator({ name: 'RetroArch' }),
    ];

    render<App.Platform.Data.CreateAchievementTicketPageProps>(
      <CreateAchievementTicketMainRoot />,
      {
        pageProps: {
          emulators,
          selectedEmulator: null, // !!
          achievement: createAchievement(),
          auth: { user: createAuthenticatedUser() },
          gameHashes: [createGameHash()],
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    const comboboxEl = screen.getByRole('combobox', { name: 'Emulator' });

    expect(comboboxEl).toHaveTextContent(/select an emulator/i);
  });

  it('given the user has a known selected valid emulator, pre-selects the emulator for them', () => {
    // ARRANGE
    const emulators = [
      createEmulator({ name: 'Bizhawk' }),
      createEmulator({ name: 'RALibRetro' }),
      createEmulator({ name: 'RetroArch' }),
    ];

    render<App.Platform.Data.CreateAchievementTicketPageProps>(
      <CreateAchievementTicketMainRoot />,
      {
        pageProps: {
          emulators,
          selectedEmulator: 'RetroArch', // !!
          achievement: createAchievement(),
          auth: { user: createAuthenticatedUser() },
          gameHashes: [createGameHash()],
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    expect(screen.queryByText(/select an emulator/i)).not.toBeInTheDocument();

    const comboboxEl = screen.getByRole('combobox', { name: 'Emulator' });
    expect(comboboxEl).toHaveTextContent('RetroArch');
  });

  it('given the user has a selected emulator but that emulator is unsupported or invalid, tells them to select an emulator', () => {
    // ARRANGE
    const emulators = [
      createEmulator({ name: 'Bizhawk' }),
      createEmulator({ name: 'RALibRetro' }),
      createEmulator({ name: 'RetroArch' }),
    ];

    render<App.Platform.Data.CreateAchievementTicketPageProps>(
      <CreateAchievementTicketMainRoot />,
      {
        pageProps: {
          emulators,
          selectedEmulator: 'PizzaBoy GBA', // !!
          achievement: createAchievement(),
          auth: { user: createAuthenticatedUser() },
          gameHashes: [createGameHash()],
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    const comboboxEl = screen.getByRole('combobox', { name: 'Emulator' });
    expect(comboboxEl).toHaveTextContent(/select an emulator/i);
  });

  it('given the user has a known emulator version, prepopulates the field', () => {
    // ARRANGE
    render<App.Platform.Data.CreateAchievementTicketPageProps>(
      <CreateAchievementTicketMainRoot />,
      {
        pageProps: {
          emulatorVersion: '1.16.0', // !!
          achievement: createAchievement(),
          auth: { user: createAuthenticatedUser() },
          gameHashes: [createGameHash()],
          emulators: [createEmulator()],
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    const emulatorVersionFieldEl = screen.getByRole('textbox', { name: /emulator version/i });

    expect(emulatorVersionFieldEl).toBeVisible();
    expect(emulatorVersionFieldEl).toHaveValue('1.16.0');
  });

  it('given the user has a known emulator core, prepopulates the field', () => {
    // ARRANGE
    render<App.Platform.Data.CreateAchievementTicketPageProps>(
      <CreateAchievementTicketMainRoot />,
      {
        pageProps: {
          emulatorCore: 'mupen64plus-next', // !!
          achievement: createAchievement(),
          auth: { user: createAuthenticatedUser() },
          gameHashes: [createGameHash()],
          emulators: [createEmulator()],
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    const emulatorCoreFieldEl = screen.getByRole('textbox', { name: /emulator core/i });

    expect(emulatorCoreFieldEl).toBeVisible();
    expect(emulatorCoreFieldEl).toHaveValue('mupen64plus-next');
  });

  it('given the server does not specify the user mode and the user has no points, does not preselect a mode', () => {
    // ARRANGE
    render<App.Platform.Data.CreateAchievementTicketPageProps>(
      <CreateAchievementTicketMainRoot />,
      {
        pageProps: {
          achievement: createAchievement(),
          auth: { user: createAuthenticatedUser({ points: 0, pointsSoftcore: 0 }) },
          gameHashes: [createGameHash()],
          emulators: [createEmulator()],
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    expect(screen.getByRole('radio', { name: /toggle hardcore/i })).not.toBeChecked();
    expect(screen.getByRole('radio', { name: /toggle softcore/i })).not.toBeChecked();
  });

  it('given the server does not specify the user mode and the user has equal points in hardcore and softcore, does not preselect a mode', () => {
    // ARRANGE
    render<App.Platform.Data.CreateAchievementTicketPageProps>(
      <CreateAchievementTicketMainRoot />,
      {
        pageProps: {
          achievement: createAchievement(),
          auth: { user: createAuthenticatedUser({ points: 500, pointsSoftcore: 500 }) },
          gameHashes: [createGameHash()],
          emulators: [createEmulator()],
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    expect(screen.getByRole('radio', { name: /toggle hardcore/i })).not.toBeChecked();
    expect(screen.getByRole('radio', { name: /toggle softcore/i })).not.toBeChecked();
  });

  it('given the server states the user had a hardcore session, preselects hardcore mode', () => {
    // ARRANGE
    render<App.Platform.Data.CreateAchievementTicketPageProps>(
      <CreateAchievementTicketMainRoot />,
      {
        pageProps: {
          selectedMode: 1, // !!
          achievement: createAchievement(),
          auth: { user: createAuthenticatedUser() },
          gameHashes: [createGameHash()],
          emulators: [createEmulator()],
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    expect(screen.getByRole('radio', { name: /toggle hardcore/i })).toBeChecked();
  });

  it('given the server states the user had a softcore session, preselects softcore mode', () => {
    // ARRANGE
    render<App.Platform.Data.CreateAchievementTicketPageProps>(
      <CreateAchievementTicketMainRoot />,
      {
        pageProps: {
          selectedMode: 0, // !!
          achievement: createAchievement(),
          auth: { user: createAuthenticatedUser() },
          gameHashes: [createGameHash()],
          emulators: [createEmulator()],
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    expect(screen.getByRole('radio', { name: /toggle softcore/i })).toBeChecked();
  });

  it('given the server does not specify the user mode and the user has more hardcore points than softcore points, preselects hardcore mode', () => {
    // ARRANGE
    render<App.Platform.Data.CreateAchievementTicketPageProps>(
      <CreateAchievementTicketMainRoot />,
      {
        pageProps: {
          achievement: createAchievement(),
          auth: { user: createAuthenticatedUser({ points: 1000, pointsSoftcore: 0 }) },
          gameHashes: [createGameHash()],
          emulators: [createEmulator()],
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    expect(screen.getByRole('radio', { name: /toggle hardcore/i })).toBeChecked();
  });

  it('given the server does not specify the user mode and the user has more softcore points than hardcore points, preselects select mode', () => {
    // ARRANGE
    render<App.Platform.Data.CreateAchievementTicketPageProps>(
      <CreateAchievementTicketMainRoot />,
      {
        pageProps: {
          achievement: createAchievement(),
          auth: { user: createAuthenticatedUser({ points: 0, pointsSoftcore: 1000 }) },
          gameHashes: [createGameHash()],
          emulators: [createEmulator()],
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    expect(screen.getByRole('radio', { name: /toggle softcore/i })).toBeChecked();
  });

  it('given the server does not specify the selected hash id and there are multiple hashes available, does not preselect a hash', () => {
    // ARRANGE
    const gameHashes = [createGameHash({ name: 'Hash A' }), createGameHash({ name: 'Hash B' })];

    render<App.Platform.Data.CreateAchievementTicketPageProps>(
      <CreateAchievementTicketMainRoot />,
      {
        pageProps: {
          gameHashes,
          achievement: createAchievement(),
          auth: { user: createAuthenticatedUser({ points: 0, pointsSoftcore: 1000 }) },
          emulators: [createEmulator()],
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    const comboboxEl = screen.getByRole('combobox', { name: /game file/i });

    expect(comboboxEl).toBeVisible();
    expect(comboboxEl).toHaveTextContent(/select a file/i);
  });

  it('given a selectable hash has no name, still lets the user select it by md5', async () => {
    // ARRANGE
    const gameHashes = [createGameHash({ name: null }), createGameHash({ name: null })];

    render<App.Platform.Data.CreateAchievementTicketPageProps>(
      <CreateAchievementTicketMainRoot />,
      {
        pageProps: {
          gameHashes,
          achievement: createAchievement(),
          auth: { user: createAuthenticatedUser({ points: 0, pointsSoftcore: 1000 }) },
          emulators: [createEmulator()],
          ziggy: createZiggyProps(),
        },
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('combobox', { name: /game file/i }));

    expect(screen.getByRole('option', { name: gameHashes[0].md5 })).toBeVisible();
    expect(screen.getByRole('option', { name: gameHashes[1].md5 })).toBeVisible();
  });

  it('given there is only a single game hash available, preselects that game hash', () => {
    // ARRANGE
    const gameHashes = [createGameHash({ name: 'Hash A' })];

    render<App.Platform.Data.CreateAchievementTicketPageProps>(
      <CreateAchievementTicketMainRoot />,
      {
        pageProps: {
          gameHashes,
          achievement: createAchievement(),
          auth: { user: createAuthenticatedUser({ points: 0, pointsSoftcore: 1000 }) },
          emulators: [createEmulator()],
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    const comboboxEl = screen.getByRole('combobox', { name: /game file/i });

    expect(comboboxEl).toHaveTextContent(/hash a/i);
  });

  it('given the server specifies the selected hash, preselects the hash if it is still linked', () => {
    // ARRANGE
    const gameHashes = [createGameHash({ name: 'Hash A' }), createGameHash({ name: 'Hash B' })];

    render<App.Platform.Data.CreateAchievementTicketPageProps>(
      <CreateAchievementTicketMainRoot />,
      {
        pageProps: {
          gameHashes,
          selectedGameHashId: gameHashes[1].id,
          achievement: createAchievement(),
          auth: { user: createAuthenticatedUser({ points: 0, pointsSoftcore: 1000 }) },
          emulators: [createEmulator()],
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    const comboboxEl = screen.getByRole('combobox', { name: /game file/i });

    expect(comboboxEl).toHaveTextContent(/hash b/i);
  });

  it('given the server specifies the selected hash, does not preselect a hash if that one is now unlinked', () => {
    // ARRANGE
    const gameHashes = [createGameHash({ name: 'Hash A' }), createGameHash({ name: 'Hash B' })];

    render<App.Platform.Data.CreateAchievementTicketPageProps>(
      <CreateAchievementTicketMainRoot />,
      {
        pageProps: {
          gameHashes,
          selectedGameHashId: 23487234,
          achievement: createAchievement(),
          auth: { user: createAuthenticatedUser({ points: 0, pointsSoftcore: 1000 }) },
          emulators: [createEmulator()],
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    const comboboxEl = screen.getByRole('combobox', { name: /game file/i });

    expect(comboboxEl).toHaveTextContent(/select a file/i);
  });

  it('given the user selects they had a network problem, shows a link to the Discord and disables the submit button', async () => {
    // ARRANGE
    render<App.Platform.Data.CreateAchievementTicketPageProps>(
      <CreateAchievementTicketMainRoot />,
      {
        pageProps: {
          achievement: createAchievement(),
          auth: { user: createAuthenticatedUser() },
          gameHashes: [createGameHash()],
          emulators: [createEmulator()],
          ziggy: createZiggyProps({ query: {} }),
        },
      },
    );

    // ACT
    await userEvent.type(screen.getByRole('textbox', { name: /description/i }), 'asdfasdfasdf'); // make this field dirty

    await userEvent.click(screen.getByRole('combobox', { name: /issue/i }));
    await userEvent.click(screen.getByRole('option', { name: /not showing as earned/i }));

    // ASSERT
    expect(screen.getByText(/please do not create a ticket for this issue/i)).toBeVisible();
    expect(screen.getByRole('link', { name: /discord server/i })).toBeVisible();
    expect(screen.getByRole('button', { name: /submit/i })).toBeDisabled();
  });

  it('given the user does not modify the description field, keeps the Submit button disabled', async () => {
    // ARRANGE
    const achievement = createAchievement();
    const gameHashes = [createGameHash({ name: 'Hash A' }), createGameHash({ name: 'Hash B' })];
    const emulators = [
      createEmulator({ name: 'Bizhawk' }),
      createEmulator({ name: 'RALibRetro' }),
      createEmulator({ name: 'RetroArch' }),
    ];

    render<App.Platform.Data.CreateAchievementTicketPageProps>(
      <CreateAchievementTicketMainRoot />,
      {
        pageProps: {
          achievement,
          emulators,
          gameHashes,
          auth: { user: createAuthenticatedUser({ points: 500 }) },
          ziggy: createZiggyProps({ query: {} }),
        },
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('combobox', { name: /issue/i }));
    await userEvent.click(screen.getByRole('option', { name: /did not trigger/i }));

    await userEvent.click(screen.getByRole('combobox', { name: /emulator/i }));
    await userEvent.click(screen.getByRole('option', { name: /retroarch/i }));

    await userEvent.type(screen.getByRole('textbox', { name: /emulator version/i }), '1.16.0');

    await userEvent.type(screen.getByRole('textbox', { name: /emulator core/i }), 'gambatte');

    await userEvent.click(screen.getByRole('radio', { name: /softcore/i }));
    await userEvent.click(screen.getByText(/softcore/i));

    await userEvent.click(screen.getByRole('combobox', { name: /supported game file/i }));
    await userEvent.click(screen.getByRole('option', { name: /hash a/i }));

    // ASSERT
    expect(screen.getByRole('button', { name: /submit/i })).toBeDisabled();
  });

  it('allows the user to submit the form with no prepopulated values', async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ data: { ticketId: 123 } });

    const achievement = createAchievement();
    const gameHashes = [createGameHash({ name: 'Hash A' }), createGameHash({ name: 'Hash B' })];
    const emulators = [
      createEmulator({ name: 'Bizhawk' }),
      createEmulator({ name: 'RALibRetro' }),
      createEmulator({ name: 'RetroArch' }),
    ];

    render<App.Platform.Data.CreateAchievementTicketPageProps>(
      <CreateAchievementTicketMainRoot />,
      {
        pageProps: {
          achievement,
          emulators,
          gameHashes,
          auth: { user: createAuthenticatedUser({ points: 500 }) },
          ziggy: createZiggyProps({ query: {} }),
        },
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('combobox', { name: /issue/i }));
    await userEvent.click(screen.getByRole('option', { name: /did not trigger/i }));

    await userEvent.click(screen.getByRole('combobox', { name: /emulator/i }));
    await userEvent.click(screen.getByRole('option', { name: /retroarch/i }));

    await userEvent.type(screen.getByRole('textbox', { name: /emulator version/i }), '1.16.0');

    await userEvent.type(screen.getByRole('textbox', { name: /emulator core/i }), 'gambatte');

    await userEvent.click(screen.getByRole('radio', { name: /softcore/i }));
    await userEvent.click(screen.getByText(/softcore/i));

    await userEvent.click(screen.getByRole('combobox', { name: /supported game file/i }));
    await userEvent.click(screen.getByRole('option', { name: /hash a/i }));

    await userEvent.type(
      screen.getByRole('textbox', { name: /description/i }),
      'Something is very wrong with this achievement. I tried many things and it just wont unlock. Help.',
    );

    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    await waitFor(() => {
      expect(postSpy).toHaveBeenCalledOnce();
    });

    expect(postSpy).toHaveBeenCalledWith(['api.ticket.store'], {
      core: 'gambatte',
      description:
        'Something is very wrong with this achievement. I tried many things and it just wont unlock. Help.',
      emulator: 'RetroArch',
      emulatorVersion: '1.16.0',
      extra: null,
      gameHashId: gameHashes[0].id,
      issue: 2,
      mode: 'softcore',
      ticketableId: achievement.id,
      ticketableModel: 'achievement',
    });
  });

  it('allows the user to submit the form without including an emulator version', async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ data: { ticketId: 123 } });

    const achievement = createAchievement();
    const gameHashes = [createGameHash({ name: 'Hash A' }), createGameHash({ name: 'Hash B' })];
    const emulators = [
      createEmulator({ name: 'Bizhawk' }),
      createEmulator({ name: 'RALibRetro' }),
      createEmulator({ name: 'RetroArch' }),
    ];

    render<App.Platform.Data.CreateAchievementTicketPageProps>(
      <CreateAchievementTicketMainRoot />,
      {
        pageProps: {
          achievement,
          emulators,
          gameHashes,
          auth: { user: createAuthenticatedUser({ points: 500 }) },
          ziggy: createZiggyProps({ query: {} }),
        },
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('combobox', { name: /issue/i }));
    await userEvent.click(screen.getByRole('option', { name: /did not trigger/i }));

    await userEvent.click(screen.getByRole('combobox', { name: /emulator/i }));
    await userEvent.click(screen.getByRole('option', { name: /retroarch/i }));

    await userEvent.type(screen.getByRole('textbox', { name: /emulator core/i }), 'gambatte');

    await userEvent.click(screen.getByRole('radio', { name: /softcore/i }));
    await userEvent.click(screen.getByText(/softcore/i));

    await userEvent.click(screen.getByRole('combobox', { name: /supported game file/i }));
    await userEvent.click(screen.getByRole('option', { name: /hash a/i }));

    await userEvent.type(
      screen.getByRole('textbox', { name: /description/i }),
      'Something is very wrong with this achievement. I tried many things and it just wont unlock. Help.',
    );

    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    await waitFor(() => {
      expect(postSpy).toHaveBeenCalledOnce();
    });

    expect(postSpy).toHaveBeenCalledWith(['api.ticket.store'], {
      core: 'gambatte',
      description:
        'Something is very wrong with this achievement. I tried many things and it just wont unlock. Help.',
      emulator: 'RetroArch',
      emulatorVersion: null,
      extra: null,
      gameHashId: gameHashes[0].id,
      issue: 2,
      mode: 'softcore',
      ticketableId: achievement.id,
      ticketableModel: 'achievement',
    });
  });

  it(
    'allows the user to submit the form with the "Triggered at the wrong time" issue type',
    { retry: 2, timeout: 15000 },
    async () => {
      // ARRANGE
      const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ data: { ticketId: 123 } });

      const achievement = createAchievement();
      const gameHashes = [createGameHash({ name: 'Hash A' }), createGameHash({ name: 'Hash B' })];
      const emulators = [
        createEmulator({ name: 'Bizhawk' }),
        createEmulator({ name: 'RALibRetro' }),
        createEmulator({ name: 'RetroArch' }),
      ];

      render<App.Platform.Data.CreateAchievementTicketPageProps>(
        <CreateAchievementTicketMainRoot />,
        {
          pageProps: {
            achievement,
            emulators,
            gameHashes,
            auth: { user: createAuthenticatedUser({ points: 500 }) },
            ziggy: createZiggyProps({ query: {} }),
          },
        },
      );

      // ACT
      await userEvent.click(screen.getByRole('combobox', { name: /issue/i }));
      await userEvent.click(screen.getByRole('option', { name: /triggered at the wrong time/i }));

      await userEvent.click(screen.getByRole('combobox', { name: /emulator/i }));
      await userEvent.click(screen.getByRole('option', { name: /retroarch/i }));

      await userEvent.type(screen.getByRole('textbox', { name: /emulator version/i }), '1.16.0');

      await userEvent.type(screen.getByRole('textbox', { name: /emulator core/i }), 'gambatte');

      await userEvent.click(screen.getByRole('radio', { name: /softcore/i }));
      await userEvent.click(screen.getByText(/softcore/i));

      await userEvent.click(screen.getByRole('combobox', { name: /supported game file/i }));
      await userEvent.click(screen.getByRole('option', { name: /hash a/i }));

      await userEvent.type(
        screen.getByRole('textbox', { name: /description/i }),
        'Something is very wrong with this achievement. I tried many things and it just wont unlock. Help.',
      );

      await userEvent.click(screen.getByRole('button', { name: /submit/i }));

      // ASSERT
      await waitFor(
        () => {
          expect(postSpy).toHaveBeenCalledOnce();
        },
        { timeout: 5000 },
      );

      expect(postSpy).toHaveBeenCalledWith(['api.ticket.store'], {
        core: 'gambatte',
        description:
          'Something is very wrong with this achievement. I tried many things and it just wont unlock. Help.',
        emulator: 'RetroArch',
        emulatorVersion: '1.16.0',
        extra: null,
        gameHashId: gameHashes[0].id,
        issue: 1,
        mode: 'softcore',
        ticketableId: achievement.id,
        ticketableModel: 'achievement',
      });
    },
  );

  it('sends along data from the ?extra query param if that data is provided', async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ data: { ticketId: 123 } });

    const achievement = createAchievement();
    const gameHashes = [createGameHash({ name: 'Hash A' }), createGameHash({ name: 'Hash B' })];
    const emulators = [
      createEmulator({ name: 'Bizhawk' }),
      createEmulator({ name: 'RALibRetro' }),
      createEmulator({ name: 'RetroArch' }),
    ];

    render<App.Platform.Data.CreateAchievementTicketPageProps>(
      <CreateAchievementTicketMainRoot />,
      {
        pageProps: {
          achievement,
          emulators,
          gameHashes,
          auth: { user: createAuthenticatedUser({ points: 500 }) },
          ziggy: createZiggyProps({
            query: {
              // !!!!!
              extra:
                'eyJ0cmlnZ2VyUmljaFByZXNlbmNlIjoi8J+Qukxpbmsg8J+Xuu+4j0RlYXRoIE1vdW50YWluIOKdpO+4jzMvMyDwn5GlMS80IPCfp78wLzQg8J+RuzAvNjAg8J+QnDAvMjQg8J+SgDUg8J+VmTEyOjAwIEFN8J+MmSJ9',
            },
          }),
        },
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('combobox', { name: /issue/i }));
    await userEvent.click(screen.getByRole('option', { name: /did not trigger/i }));

    await userEvent.click(screen.getByRole('combobox', { name: /emulator/i }));
    await userEvent.click(screen.getByRole('option', { name: /retroarch/i }));

    await userEvent.type(screen.getByRole('textbox', { name: /emulator core/i }), 'gambatte');

    await userEvent.click(screen.getByRole('radio', { name: /softcore/i }));
    await userEvent.click(screen.getByText(/softcore/i));

    await userEvent.click(screen.getByRole('combobox', { name: /supported game file/i }));
    await userEvent.click(screen.getByRole('option', { name: /hash a/i }));

    await userEvent.type(
      screen.getByRole('textbox', { name: /description/i }),
      'Something is very wrong with this achievement. I tried many things and it just wont unlock. Help.',
    );

    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    await waitFor(
      () => {
        expect(postSpy).toHaveBeenCalledOnce();
      },
      { timeout: 3000 },
    );

    expect(postSpy).toHaveBeenCalledWith(['api.ticket.store'], {
      core: 'gambatte',
      description:
        'Something is very wrong with this achievement. I tried many things and it just wont unlock. Help.',
      emulator: 'RetroArch',
      emulatorVersion: null,
      extra:
        'eyJ0cmlnZ2VyUmljaFByZXNlbmNlIjoi8J+Qukxpbmsg8J+Xuu+4j0RlYXRoIE1vdW50YWluIOKdpO+4jzMvMyDwn5GlMS80IPCfp78wLzQg8J+RuzAvNjAg8J+QnDAvMjQg8J+SgDUg8J+VmTEyOjAwIEFN8J+MmSJ9',
      gameHashId: gameHashes[0].id,
      issue: 2,
      mode: 'softcore',
      ticketableId: achievement.id,
      ticketableModel: 'achievement',
    });
  });

  it('given the user is using a non-English locale, shows a warning about their ticket description', () => {
    // ARRANGE
    render<App.Platform.Data.CreateAchievementTicketPageProps>(
      <CreateAchievementTicketMainRoot />,
      {
        pageProps: {
          achievement: createAchievement(),
          auth: { user: createAuthenticatedUser({ locale: 'pt_BR' }) }, // !!
          gameHashes: [createGameHash()],
          emulators: [createEmulator()],
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    expect(screen.getByText(/please write your ticket description in english/i)).toBeVisible();
  });

  it('given the user enters some unhelpful text, displays a warning and does not allow the user to submit the form', async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ data: { ticketId: 123 } });

    const achievement = createAchievement();
    const gameHashes = [createGameHash({ name: 'Hash A' }), createGameHash({ name: 'Hash B' })];
    const emulators = [
      createEmulator({ name: 'Bizhawk' }),
      createEmulator({ name: 'RALibRetro' }),
      createEmulator({ name: 'RetroArch' }),
    ];

    render<App.Platform.Data.CreateAchievementTicketPageProps>(
      <CreateAchievementTicketMainRoot />,
      {
        pageProps: {
          achievement,
          emulators,
          gameHashes,
          auth: { user: createAuthenticatedUser({ points: 500 }) },
          ziggy: createZiggyProps({ query: {} }),
        },
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('combobox', { name: /issue/i }));
    await userEvent.click(screen.getByRole('option', { name: /did not trigger/i }));

    await userEvent.click(screen.getByRole('combobox', { name: /emulator/i }));
    await userEvent.click(screen.getByRole('option', { name: /retroarch/i }));

    await userEvent.type(screen.getByRole('textbox', { name: /emulator core/i }), 'gambatte');

    await userEvent.click(screen.getByRole('radio', { name: /softcore/i }));
    await userEvent.click(screen.getByText(/softcore/i));

    await userEvent.click(screen.getByRole('combobox', { name: /supported game file/i }));
    await userEvent.click(screen.getByRole('option', { name: /hash a/i }));

    await userEvent.type(screen.getByRole('textbox', { name: /description/i }), "doesn't work"); // !!

    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/please be more specific with your issue/i)).toBeVisible();
    });

    expect(postSpy).not.toHaveBeenCalled();
  });

  it('given the user starts writing about network issues, displays a warning', async () => {
    // ARRANGE
    const achievement = createAchievement();
    const gameHashes = [createGameHash({ name: 'Hash A' }), createGameHash({ name: 'Hash B' })];
    const emulators = [
      createEmulator({ name: 'Bizhawk' }),
      createEmulator({ name: 'RALibRetro' }),
      createEmulator({ name: 'RetroArch' }),
    ];

    render<App.Platform.Data.CreateAchievementTicketPageProps>(
      <CreateAchievementTicketMainRoot />,
      {
        pageProps: {
          achievement,
          emulators,
          gameHashes,
          auth: { user: createAuthenticatedUser({ points: 500 }) },
          ziggy: createZiggyProps({ query: {} }),
        },
      },
    );

    // ACT
    await userEvent.type(screen.getByRole('textbox', { name: /description/i }), 'manual unlock');

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/please do not open tickets for network issues/i)).toBeVisible();
    });

    const linkEl = screen.getByRole('link', { name: 'here' });
    expect(linkEl).toBeVisible();
    expect(linkEl).toHaveAttribute('href', expect.stringContaining('docs.retroachievements.org'));
  });

  it('given the user writes a description "this achievement doesn\'t work", displays a warning on attempted submit', async () => {
    // ARRANGE
    const achievement = createAchievement();
    const gameHashes = [createGameHash({ name: 'Hash A' }), createGameHash({ name: 'Hash B' })];
    const emulators = [
      createEmulator({ name: 'Bizhawk' }),
      createEmulator({ name: 'RALibRetro' }),
      createEmulator({ name: 'RetroArch' }),
    ];

    render<App.Platform.Data.CreateAchievementTicketPageProps>(
      <CreateAchievementTicketMainRoot />,
      {
        pageProps: {
          achievement,
          emulators,
          gameHashes,
          auth: { user: createAuthenticatedUser({ points: 500 }) },
          ziggy: createZiggyProps({ query: {} }),
        },
      },
    );

    // ACT
    await userEvent.type(
      screen.getByRole('textbox', { name: /description/i }),
      "this achievement doesn't work",
    );

    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/please be more specific/i)).toBeVisible();
    });
  });

  it('given the user does not select an emulator and a hash, shows required validation messages', async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ data: { ticketId: 123 } });

    const achievement = createAchievement();
    const gameHashes = [createGameHash({ name: 'Hash A' }), createGameHash({ name: 'Hash B' })];
    const emulators = [
      createEmulator({ name: 'Bizhawk' }),
      createEmulator({ name: 'RALibRetro' }),
      createEmulator({ name: 'RetroArch' }),
    ];

    render<App.Platform.Data.CreateAchievementTicketPageProps>(
      <CreateAchievementTicketMainRoot />,
      {
        pageProps: {
          achievement,
          emulators,
          gameHashes,
          auth: { user: createAuthenticatedUser({ points: 500 }) },
          ziggy: createZiggyProps({ query: {} }),
        },
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('combobox', { name: /issue/i }));
    await userEvent.click(screen.getByRole('option', { name: /did not trigger/i }));

    // !! no emulator selected
    // await userEvent.click(screen.getByRole('combobox', { name: /emulator/i }));
    // await userEvent.click(screen.getByRole('option', { name: /retroarch/i }));

    await userEvent.type(screen.getByRole('textbox', { name: /emulator core/i }), 'gambatte');

    await userEvent.click(screen.getByRole('radio', { name: /softcore/i }));
    await userEvent.click(screen.getByText(/softcore/i));

    // !! no hash selected
    // await userEvent.click(screen.getByRole('combobox', { name: /supported game file/i }));
    // await userEvent.click(screen.getByRole('option', { name: /hash a/i }));

    await userEvent.type(
      screen.getByRole('textbox', { name: /description/i }),
      'this is my sample description this is hopefully a helpful label so you can repro the issue',
    );

    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    expect(screen.getAllByText('Required').length).toBeGreaterThanOrEqual(2);
    expect(postSpy).not.toHaveBeenCalled();
  });
});
