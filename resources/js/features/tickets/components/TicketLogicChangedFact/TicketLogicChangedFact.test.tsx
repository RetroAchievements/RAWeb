import { render, screen } from '@/test';

import { TicketLogicChangedFact } from './TicketLogicChangedFact';

describe('Component: TicketLogicChangedFact', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.TicketShowPageProps>(
      <TicketLogicChangedFact
        achievementId={99}
        currentTriggerVersion={5}
        reportedTriggerVersion={3}
      />,
      { pageProps: { can: {} } },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the viewer has the view logic perm, links each version to its logic page', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.TicketShowPageProps>(
      <TicketLogicChangedFact
        achievementId={99}
        currentTriggerVersion={5}
        reportedTriggerVersion={3}
      />,
      { pageProps: { can: { viewAchievementLogic: true } } },
    );

    // ASSERT
    expect(container).toHaveTextContent('The logic changed from v3 to v5 after the report.');
    expect(screen.getByRole('link', { name: 'v3' })).toHaveAttribute(
      'href',
      '/manage/achievements/99/logic?version=3',
    );
    expect(screen.getByRole('link', { name: 'v5' })).toHaveAttribute(
      'href',
      '/manage/achievements/99/logic?version=5',
    );
  });

  it('given the viewer does not have the view logic perm, links nothing', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.TicketShowPageProps>(
      <TicketLogicChangedFact
        achievementId={99}
        currentTriggerVersion={5}
        reportedTriggerVersion={3}
      />,
      { pageProps: { can: {} } },
    );

    // ASSERT
    expect(container).toHaveTextContent('The logic changed from v3 to v5 after the report.');
    expect(screen.queryByRole('link')).not.toBeInTheDocument();
  });
});
