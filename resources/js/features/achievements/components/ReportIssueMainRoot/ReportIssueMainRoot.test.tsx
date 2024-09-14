import { faker } from '@faker-js/faker';

import { TicketType } from '@/common/utils/generatedAppConstants';
import { render, screen } from '@/test';
import { createAchievement } from '@/test/factories';

import { ReportIssueMainRoot } from './ReportIssueMainRoot';
import { testId } from './UnlockStatusLabel';

describe('Component: ReportIssueMainRoot', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.ReportAchievementIssuePageProps>(
      <ReportIssueMainRoot />,
      { pageProps: { achievement: createAchievement() } },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('renders a breadcrumb to the achievement page', () => {
    // ARRANGE
    const achievement = createAchievement();

    render<App.Platform.Data.ReportAchievementIssuePageProps>(<ReportIssueMainRoot />, {
      pageProps: { achievement },
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
      pageProps: { achievement },
    });

    // ASSERT
    expect(screen.getByRole('heading', { name: /report issue/i })).toBeVisible();
  });

  it('given the user has no session, will not display an unlock status label', () => {
    // ARRANGE
    const achievement = createAchievement();

    render<App.Platform.Data.ReportAchievementIssuePageProps>(<ReportIssueMainRoot />, {
      pageProps: { achievement, hasSession: false },
    });

    // ASSERT
    expect(screen.queryByText(/unlocked this achievement/i)).not.toBeInTheDocument();
    expect(screen.queryByTestId(testId)).not.toBeInTheDocument();
  });

  it('given the user has a session but has no unlock, tells them', () => {
    // ARRANGE
    const achievement = createAchievement({ unlockedAt: undefined, unlockedHardcoreAt: undefined });

    render<App.Platform.Data.ReportAchievementIssuePageProps>(<ReportIssueMainRoot />, {
      pageProps: { achievement, hasSession: true },
    });

    // ASSERT
    const labelEl = screen.getByTestId(testId);

    expect(labelEl).toBeVisible();
    expect(labelEl).toHaveTextContent(/have not unlocked this achievement/i);
  });

  it('given the user has a session but only has a softcore unlock, tells them', () => {
    // ARRANGE
    const achievement = createAchievement({
      unlockedAt: faker.date.recent().toISOString(),
      unlockedHardcoreAt: undefined,
    });

    render<App.Platform.Data.ReportAchievementIssuePageProps>(<ReportIssueMainRoot />, {
      pageProps: { achievement, hasSession: true },
    });

    // ASSERT
    const labelEl = screen.getByTestId(testId);

    expect(labelEl).toBeVisible();
    expect(labelEl).toHaveTextContent(/have unlocked this achievement in softcore/i);
  });

  it('given the user has a session and has a hardcore unlock, tells them', () => {
    // ARRANGE
    const achievement = createAchievement({
      unlockedAt: faker.date.recent().toISOString(),
      unlockedHardcoreAt: faker.date.recent().toISOString(),
    });

    render<App.Platform.Data.ReportAchievementIssuePageProps>(<ReportIssueMainRoot />, {
      pageProps: { achievement, hasSession: true },
    });

    // ASSERT
    const labelEl = screen.getByTestId(testId);

    expect(labelEl).toBeVisible();
    expect(labelEl).toHaveTextContent(/have unlocked this achievement/i);
    expect(labelEl).not.toHaveTextContent(/in softcore/i);
  });

  it('given the user has no session, they do not see a link to open a ticket', () => {
    // ARRANGE
    const achievement = createAchievement({
      unlockedAt: undefined,
      unlockedHardcoreAt: undefined,
    });

    render<App.Platform.Data.ReportAchievementIssuePageProps>(<ReportIssueMainRoot />, {
      pageProps: { achievement, hasSession: false },
    });

    // ASSERT
    const linkEls = screen.getAllByRole('link');

    for (const linkEl of linkEls) {
      expect(linkEl).not.toHaveAttribute(
        'href',
        expect.stringContaining('achievement.create-ticket'),
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
      pageProps: { achievement, hasSession: true },
    });

    // ASSERT
    const linkEls = screen.getAllByRole('link');
    const hasCreateTicketLink = linkEls.some((linkEl) =>
      linkEl.getAttribute('href')?.includes('achievement.create-ticket'),
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
      pageProps: { achievement, hasSession: true, ticketType: TicketType.DidNotTrigger },
    });

    // ASSERT
    expect(
      screen.getByText(/met the requirements, but the achievement did not trigger/i),
    ).toBeVisible();

    expect(
      screen.getByText(/unlocked this achievement without meeting the requirements/i),
    ).toBeVisible();

    expect(screen.getByText(/achievement triggered, but the unlock didn't appear/i)).toBeVisible();
  });

  it('given the back-end determines the ticket type should be of `TriggeredAtWrongTime`, only shows one Create Ticket link', () => {
    // ARRANGE
    const achievement = createAchievement({
      unlockedAt: undefined,
      unlockedHardcoreAt: undefined,
    });

    render<App.Platform.Data.ReportAchievementIssuePageProps>(<ReportIssueMainRoot />, {
      pageProps: { achievement, hasSession: true, ticketType: TicketType.TriggeredAtWrongTime },
    });

    // ASSERT
    expect(
      screen.queryByText(/met the requirements, but the achievement did not trigger/i),
    ).not.toBeInTheDocument();

    expect(
      screen.queryByText(/achievement triggered, but the unlock didn't appear/i),
    ).not.toBeInTheDocument();

    expect(
      screen.getByText(/unlocked this achievement without meeting the requirements/i),
    ).toBeVisible();

    expect(screen.getAllByText(/create ticket/i).length).toEqual(1);
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
        ticketType: TicketType.TriggeredAtWrongTime,
      },
    });

    // ASSERT
    const linkEl = screen.getByRole('link', { name: /create ticket/i });

    expect(linkEl).toHaveAttribute(
      'href',
      `achievement.create-ticket,${{ achievement: achievement.id, type: TicketType.TriggeredAtWrongTime, extra }}`,
    );
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
});
