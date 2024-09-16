import { render, screen } from '@/test';

import { SiteAwardsSection } from './SiteAwardsSection';

describe('Component: SiteAwardsSection', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<SiteAwardsSection />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('has a visible link to the reorder site awards page', () => {
    // ARRANGE
    render(<SiteAwardsSection />);

    // ASSERT
    expect(screen.getByRole('link', { name: /reorder site awards/i })).toBeVisible();
  });
});
