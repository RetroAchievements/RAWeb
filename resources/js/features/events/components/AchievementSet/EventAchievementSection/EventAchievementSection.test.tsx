import { render, screen, waitFor } from '@/test';

import { EventAchievementSection } from './EventAchievementSection';

describe('Component: EventAchievementSection', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <EventAchievementSection title="title">children</EventAchievementSection>,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the provided title', async () => {
    // ARRANGE
    render(<EventAchievementSection title="title">children</EventAchievementSection>);

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/title/i)).toBeVisible();
    });
  });

  it('displays children', async () => {
    // ARRANGE
    render(<EventAchievementSection title="title">children</EventAchievementSection>);

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/children/i)).toBeVisible();
    });
  });
});
