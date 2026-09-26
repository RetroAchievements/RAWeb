import { faker } from '@faker-js/faker';

import { render, screen } from '@/test';
import { createAchievement, createGame, createSystem } from '@/test/factories';

import { ReportIssueMainRoot } from './ReportIssueMainRoot';
import { testId } from './UnlockStatusLabel';

describe('Component: ReportIssueMainRoot', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.ReportAchievementIssuePageProps>(
      <ReportIssueMainRoot />,
      {
        pageProps: {
          achievement: createAchievement(),
          can: { createTicket: true },
        },
      },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('renders a breadcrumb to the achievement page', () => {
    // ARRANGE
    const achievement = createAchievement();

    render<App.Platform.Data.ReportAchievementIssuePageProps>(<ReportIssueMainRoot />, {
      pageProps: {
        achievement,
        can: { createTicket: true },
      },
    });

    // ASSERT
    expect(screen.getAllByRole('link', { name: achievement.title }).length).toBeGreaterThanOrEqual(
      1,
    );
  });

  it('renders an accessible heading', () => {
    // ARRANGE
    const achievement = createAchievement();

    render<App.Platform.Data.ReportAchievementIssuePageProps>(<ReportIssueMainRoot />, {
      pageProps: {
        achievement,
        can: { createTicket: true },
      },
    });

    // ASSERT
    expect(screen.getByRole('heading', { name: /report issue/i })).toBeVisible();
  });

  it('given the user has no session, will not display an unlock status label', () => {
    // ARRANGE
    const achievement = createAchievement();

    render<App.Platform.Data.ReportAchievementIssuePageProps>(<ReportIssueMainRoot />, {
      pageProps: {
        achievement,
        hasSession: false,
        ticketBlockReason: 'no_play_session',
        can: { createTicket: false },
      },
    });

    // ASSERT
    expect(screen.queryByText(/unlocked this achievement/i)).not.toBeInTheDocument();
    expect(screen.queryByTestId(testId)).not.toBeInTheDocument();
  });

  it('given the user has a session but has no unlock, tells them', () => {
    // ARRANGE
    const achievement = createAchievement({ unlockedAt: undefined, unlockedHardcoreAt: undefined });

    render<App.Platform.Data.ReportAchievementIssuePageProps>(<ReportIssueMainRoot />, {
      pageProps: {
        achievement,
        hasSession: true,
        can: { createTicket: true },
      },
    });

    // ASSERT
    const labelEl = screen.getByTestId(testId);

    expect(labelEl).toBeVisible();
    expect(labelEl).toHaveTextContent(/have not unlocked this achievement/i);
  });

  it('given the user has a session but only has a casual unlock, tells them', () => {
    // ARRANGE
    const achievement = createAchievement({
      unlockedAt: faker.date.recent().toISOString(),
      unlockedHardcoreAt: undefined,
    });

    render<App.Platform.Data.ReportAchievementIssuePageProps>(<ReportIssueMainRoot />, {
      pageProps: {
        achievement,
        hasSession: true,
        can: { createTicket: true },
      },
    });

    // ASSERT
    const labelEl = screen.getByTestId(testId);

    expect(labelEl).toBeVisible();
    expect(labelEl).toHaveTextContent(/have unlocked this achievement in casual/i);
  });

  it('given the user has a session and has a hardcore unlock, tells them', () => {
    // ARRANGE
    const achievement = createAchievement({
      unlockedAt: faker.date.recent().toISOString(),
      unlockedHardcoreAt: faker.date.recent().toISOString(),
    });

    render<App.Platform.Data.ReportAchievementIssuePageProps>(<ReportIssueMainRoot />, {
      pageProps: {
        achievement,
        hasSession: true,
        can: { createTicket: true },
      },
    });

    // ASSERT
    const labelEl = screen.getByTestId(testId);

    expect(labelEl).toBeVisible();
    expect(labelEl).toHaveTextContent(/have unlocked this achievement/i);
    expect(labelEl).not.toHaveTextContent(/in casual/i);
  });

  it('given the user has no session, they do not see a link to open a ticket', () => {
    // ARRANGE
    const achievement = createAchievement({
      unlockedAt: undefined,
      unlockedHardcoreAt: undefined,
    });

    render<App.Platform.Data.ReportAchievementIssuePageProps>(<ReportIssueMainRoot />, {
      pageProps: {
        achievement,
        hasSession: false,
        ticketBlockReason: 'no_play_session',
        can: { createTicket: false },
      },
    });

    // ASSERT
    const linkEls = screen.getAllByRole('link');

    for (const linkEl of linkEls) {
      expect(linkEl).not.toHaveAttribute(
        'href',
        expect.stringContaining('achievement.tickets.create'),
      );
    }
  });

  it('given the user has a session, they always see at least one link to open a ticket', () => {
    // ARRANGE
    const achievement = createAchievement({
      unlockedAt: undefined,
      unlockedHardcoreAt: undefined,
    });

    render<App.Platform.Data.ReportAchievementIssuePageProps>(<ReportIssueMainRoot />, {
      pageProps: {
        achievement,
        hasSession: true,
        can: { createTicket: true },
      },
    });

    // ASSERT
    const linkEls = screen.getAllByRole('link');
    const hasCreateTicketLink = linkEls.some((linkEl) =>
      linkEl.getAttribute('href')?.includes('achievement.tickets.create'),
    );

    expect(hasCreateTicketLink).toBeTruthy();
  });

  it('given the back-end determines the ticket type should be of `DidNotTrigger`, shows the correct issue report links', () => {
    // ARRANGE
    const achievement = createAchievement({
      unlockedAt: undefined,
      unlockedHardcoreAt: undefined,
    });

    render<App.Platform.Data.ReportAchievementIssuePageProps>(<ReportIssueMainRoot />, {
      pageProps: {
        achievement,
        hasSession: true,
        ticketType: 'did_not_trigger',
        can: { createTicket: true },
      },
    });

    // ASSERT
    expect(
      screen.getByText(/met the requirements, but the achievement did not trigger/i),
    ).toBeVisible();

    expect(
      screen.getByText(/unlocked this achievement without meeting the requirements/i),
    ).toBeVisible();

    expect(screen.getByRole('link', { name: 'Request Manual Unlock' })).toBeVisible();
  });

  it('given the ticket type is `DidNotTrigger`, shows the correct ordered options under their headings', () => {
    // ARRANGE
    const achievement = createAchievement({
      unlockedAt: undefined,
      unlockedHardcoreAt: undefined,
    });

    render<App.Platform.Data.ReportAchievementIssuePageProps>(<ReportIssueMainRoot />, {
      pageProps: {
        achievement,
        hasSession: true,
        ticketType: 'did_not_trigger',
        can: { createTicket: true },
      },
    });

    // ASSERT
    const headings = screen.getAllByRole('heading', { level: 2 });
    expect(headings.map((heading) => heading.textContent)).toEqual([
      'I earned this achievement, but it is missing from my profile',
      'The achievement has a bug',
      'Something else is wrong with the achievement',
    ]);

    expect(screen.getByRole('link', { name: 'Request Manual Unlock' })).toBeVisible();
    expect(
      screen.getByText(/you need proof: a screenshot of the achievement popup/i),
    ).toBeVisible();

    expect(screen.getByText(/it does not add the achievement to your profile/i)).toBeVisible();
    expect(screen.getAllByRole('link', { name: 'Create Ticket' }).length).toEqual(2);

    expect(screen.getByRole('link', { name: 'Message QATeam' })).toBeVisible();
  });

  it('given the user has only a casual unlock and the ticket type is `DidNotTrigger`, says the hardcore unlock is missing', () => {
    // ARRANGE
    const achievement = createAchievement({
      unlockedAt: new Date().toISOString(),
      unlockedHardcoreAt: undefined,
    });

    render<App.Platform.Data.ReportAchievementIssuePageProps>(<ReportIssueMainRoot />, {
      pageProps: {
        achievement,
        hasSession: true,
        ticketType: 'did_not_trigger',
        can: { createTicket: true },
      },
    });

    // ASSERT
    expect(
      screen.getByRole('heading', {
        name: 'I earned this achievement in hardcore mode, but my profile shows only the casual unlock',
      }),
    ).toBeVisible();
    expect(
      screen.queryByRole('heading', {
        name: 'I earned this achievement, but it is missing from my profile',
      }),
    ).not.toBeInTheDocument();
    expect(screen.getByRole('link', { name: 'Request Manual Unlock' })).toBeVisible();
  });

  it('given the casual unlock came from a client that does not allow hardcore, explains why and hides the unlock request', () => {
    // ARRANGE
    const achievement = createAchievement({
      unlockedAt: new Date().toISOString(),
      unlockedHardcoreAt: undefined,
    });

    render<App.Platform.Data.ReportAchievementIssuePageProps>(<ReportIssueMainRoot />, {
      pageProps: {
        achievement,
        hasSession: true,
        ticketType: 'did_not_trigger',
        hasCasualUnlockFromRestrictedClient: true,
        can: { createTicket: true },
      },
    });

    // ASSERT
    expect(screen.getByText(/we cannot change it to hardcore/i)).toBeVisible();
    expect(screen.getByRole('link', { name: 'Read about emulator support' })).toHaveAttribute(
      'href',
      'https://docs.retroachievements.org/general/emulator-support-and-issues.html',
    );
    expect(screen.queryByRole('link', { name: 'Request Manual Unlock' })).not.toBeInTheDocument();
  });

  it('given the user already unlocked the achievement, does not show the missing unlock heading', () => {
    // ARRANGE
    const achievement = createAchievement({
      unlockedAt: new Date().toISOString(),
      unlockedHardcoreAt: undefined,
    });

    render<App.Platform.Data.ReportAchievementIssuePageProps>(<ReportIssueMainRoot />, {
      pageProps: {
        achievement,
        hasSession: true,
        ticketType: 'triggered_at_wrong_time',
        can: { createTicket: true },
      },
    });

    // ASSERT
    expect(
      screen.queryByRole('heading', {
        name: 'I earned this achievement, but it is missing from my profile',
      }),
    ).not.toBeInTheDocument();
    expect(screen.queryByRole('link', { name: 'Request Manual Unlock' })).not.toBeInTheDocument();
    expect(screen.getByRole('heading', { name: 'The achievement has a bug' })).toBeVisible();
  });

  it('given the back-end determines the ticket type should be of `TriggeredAtWrongTime`, shows the correct issue report links', () => {
    // ARRANGE
    const achievement = createAchievement({
      unlockedAt: undefined,
      unlockedHardcoreAt: undefined,
    });

    render<App.Platform.Data.ReportAchievementIssuePageProps>(<ReportIssueMainRoot />, {
      pageProps: {
        achievement,
        hasSession: true,
        ticketType: 'triggered_at_wrong_time',
        can: { createTicket: true },
      },
    });

    // ASSERT
    expect(
      screen.getByText(/unlocked this achievement without meeting the requirements/i),
    ).toBeVisible();

    expect(
      screen.getByText(/the achievement did not trigger. it did trigger on a later attempt/i),
    ).toBeVisible();

    expect(screen.getAllByText(/create ticket/i).length).toEqual(2);
  });

  it('given the user comes in with an `extra` query param, passes that along to the Create Ticket page', () => {
    // ARRANGE
    const achievement = createAchievement({
      unlockedAt: undefined,
      unlockedHardcoreAt: undefined,
    });

    const extra = faker.string.uuid();

    render<App.Platform.Data.ReportAchievementIssuePageProps>(<ReportIssueMainRoot />, {
      pageProps: {
        achievement,
        extra,
        hasSession: true,
        ticketType: 'triggered_at_wrong_time',
        can: { createTicket: true },
      },
    });

    // ASSERT
    expect(screen.getAllByText(/create ticket/i).length).toEqual(2);

    const linkEl = screen.getAllByRole('link', { name: /create ticket/i })[0];
    expect(linkEl).toHaveAttribute('href', expect.stringContaining('achievement.tickets.create'));

    const linkEl2 = screen.getAllByRole('link', { name: /create ticket/i })[1];
    expect(linkEl2).toHaveAttribute('href', expect.stringContaining('achievement.tickets.create'));
  });

  it('always shows the user various team account reporting links', () => {
    // ARRANGE
    const achievement = createAchievement({
      unlockedAt: undefined,
      unlockedHardcoreAt: undefined,
    });

    render<App.Platform.Data.ReportAchievementIssuePageProps>(<ReportIssueMainRoot />, {
      pageProps: {
        achievement,
        hasSession: true,
        can: { createTicket: true },
      },
    });

    // ASSERT
    expect(screen.getByText(/spelling or grammatical error/i)).toBeVisible();
    expect(screen.getByRole('link', { name: /report to writingteam/i })).toBeVisible();

    expect(screen.getByText(/achievement type/i)).toBeVisible();
    expect(screen.getByText(/that is not described above/i)).toBeVisible();
    expect(screen.getAllByRole('link', { name: /qateam/i }).length).toBeGreaterThanOrEqual(2);

    expect(screen.getByText(/unwelcome concept/i)).toBeVisible();
    expect(screen.getByRole('link', { name: /report to devcompliance/i })).toBeVisible();
  });

  it('given the user does not have permission to create tickets, does not show a create ticket button', () => {
    // ARRANGE
    const achievement = createAchievement({
      unlockedAt: undefined,
      unlockedHardcoreAt: undefined,
    });

    render<App.Platform.Data.ReportAchievementIssuePageProps>(<ReportIssueMainRoot />, {
      pageProps: {
        achievement,
        hasSession: true,
        ticketType: 'did_not_trigger',
        can: {
          createTicket: false, // !!
        },
      },
    });

    // ASSERT
    expect(
      screen.queryByText(/met the requirements, but the achievement did not trigger/i),
    ).not.toBeInTheDocument();

    expect(
      screen.queryByText(/unlocked this achievement without meeting the requirements/i),
    ).not.toBeInTheDocument();

    expect(screen.getByRole('link', { name: 'Request Manual Unlock' })).toBeVisible();
  });

  it('given the back-end determines the ticket type should be of `TriggeredAtWrongTime` but the user does not have permission to create tickets, does not show a single Create Ticket link', () => {
    // ARRANGE
    const achievement = createAchievement({
      unlockedAt: undefined,
      unlockedHardcoreAt: undefined,
    });

    render<App.Platform.Data.ReportAchievementIssuePageProps>(<ReportIssueMainRoot />, {
      pageProps: {
        achievement,
        hasSession: true,
        ticketType: 'triggered_at_wrong_time',
        can: {
          createTicket: false, // !!
        },
      },
    });

    // ASSERT
    expect(
      screen.queryByText(/unlocked this achievement without meeting the requirements/i),
    ).not.toBeInTheDocument();

    expect(screen.queryAllByText(/create ticket/i).length).toEqual(0);
  });

  it('given there is no block reason, does not show a notice', () => {
    // ARRANGE
    render<App.Platform.Data.ReportAchievementIssuePageProps>(<ReportIssueMainRoot />, {
      pageProps: {
        achievement: createAchievement(),
        hasSession: true,
        ticketType: 'did_not_trigger',
        can: { createTicket: true },
      },
    });

    // ASSERT
    expect(screen.queryByText(/no record of a play session/i)).not.toBeInTheDocument();
    expect(screen.getAllByText(/create ticket/i).length).toBeGreaterThanOrEqual(1);
  });

  it('given there is no play session, shows the reason with a link to the emulator docs and hides the ticket buttons', () => {
    // ARRANGE
    render<App.Platform.Data.ReportAchievementIssuePageProps>(<ReportIssueMainRoot />, {
      pageProps: {
        achievement: createAchievement(),
        hasSession: false,
        ticketType: 'did_not_trigger',
        ticketBlockReason: 'no_play_session',
        can: { createTicket: false },
      },
    });

    // ASSERT
    expect(screen.getByText(/play the game with a supported emulator/i)).toBeVisible();

    expect(
      screen.queryByRole('heading', {
        name: 'I earned this achievement, but it is missing from my profile',
      }),
    ).not.toBeInTheDocument();

    const link = screen.getByRole('link', { name: 'Read about emulator support' });
    expect(link).toHaveAttribute('target', '_blank');
    expect(link).toHaveAttribute(
      'href',
      'https://docs.retroachievements.org/general/emulator-support-and-issues.html',
    );

    expect(screen.queryAllByText(/create ticket/i).length).toEqual(0);
  });

  it('given there is no play session for a Standalone game, tells the player to log in and hides the emulator docs link', () => {
    // ARRANGE
    render<App.Platform.Data.ReportAchievementIssuePageProps>(<ReportIssueMainRoot />, {
      pageProps: {
        achievement: createAchievement({
          game: createGame({ system: createSystem({ id: 102, name: 'Standalone' }) }),
        }),
        hasSession: false,
        ticketType: 'did_not_trigger',
        ticketBlockReason: 'no_play_session',
        can: { createTicket: false },
      },
    });

    // ASSERT
    expect(screen.getByText(/play the game while logged in to your account/i)).toBeVisible();
    expect(screen.queryByText(/emulator/i)).not.toBeInTheDocument();
    expect(
      screen.queryByRole('link', { name: 'Read about emulator support' }),
    ).not.toBeInTheDocument();
  });

  it('given the game has no hashes or emulators, shows the reason without a link', () => {
    // ARRANGE
    render<App.Platform.Data.ReportAchievementIssuePageProps>(<ReportIssueMainRoot />, {
      pageProps: {
        achievement: createAchievement(),
        hasSession: true,
        ticketType: 'did_not_trigger',
        ticketBlockReason: 'game_not_ticketable',
        can: { createTicket: false },
      },
    });

    // ASSERT
    expect(screen.getByText(/tickets are not available for this game/i)).toBeVisible();
    expect(
      screen.queryByRole('link', { name: 'Read about emulator support' }),
    ).not.toBeInTheDocument();
    expect(screen.queryAllByText(/create ticket/i).length).toEqual(0);
  });

  it('given a block reason exists, keeps the manual unlock option', () => {
    // ARRANGE
    render<App.Platform.Data.ReportAchievementIssuePageProps>(<ReportIssueMainRoot />, {
      pageProps: {
        achievement: createAchievement(),
        hasSession: true,
        ticketType: 'did_not_trigger',
        ticketBlockReason: 'game_not_ticketable',
        can: { createTicket: false },
      },
    });

    // ASSERT
    expect(screen.getByRole('link', { name: 'Request Manual Unlock' })).toBeVisible();
    expect(screen.getByRole('link', { name: /request manual unlock/i })).toBeVisible();
  });
});
