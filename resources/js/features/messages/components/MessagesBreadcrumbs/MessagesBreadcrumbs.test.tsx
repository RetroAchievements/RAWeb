import { render, screen } from '@/test';
import type { TranslatedString } from '@/types/i18next';

import { MessagesBreadcrumbs } from './MessagesBreadcrumbs';

describe('Component: MessagesBreadcrumbs', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <MessagesBreadcrumbs t_currentPageLabel={'Inbox' as TranslatedString} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given no delegated user display name is provided, by default has a link to Your Inbox', () => {
    // ARRANGE
    render(<MessagesBreadcrumbs />);

    // ASSERT
    expect(screen.getByRole('link', { name: /your inbox/i })).toBeVisible();
  });

  it('given a delegated user display name is provided, shows a link to their inbox', () => {
    // ARRANGE
    render(<MessagesBreadcrumbs delegatedUserDisplayName="RAdmin" />);

    // ASSERT
    expect(screen.getByRole('link', { name: "RAdmin's Inbox" })).toBeVisible();
  });

  it('can prevent the inbox label from being a link', () => {
    // ARRANGE
    render(
      <MessagesBreadcrumbs
        delegatedUserDisplayName="RAdmin"
        shouldShowInboxLinkCrumb={false}
        t_currentPageLabel={'Current Page' as TranslatedString}
      />,
    );

    // ASSERT
    expect(screen.getByText('Current Page')).toBeVisible();
    expect(screen.queryByRole('link', { name: /radmin/i })).not.toBeInTheDocument();
  });
});
