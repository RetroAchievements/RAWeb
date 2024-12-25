import { render, screen } from '@/test';
import type { TranslatedString } from '@/types/i18next';

import { FeedStatCard } from './FeedStatCard';

describe('Component: FeedStatCard', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <FeedStatCard t_label={'Test Label' as TranslatedString}>Test Content</FeedStatCard>,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a label and content, displays them both', () => {
    // ARRANGE
    render(<FeedStatCard t_label={'Test Label' as TranslatedString}>Test Content</FeedStatCard>);

    // ASSERT
    expect(screen.getByText(/test label/i)).toBeVisible();
    expect(screen.getByText(/test content/i)).toBeVisible();
  });
});
