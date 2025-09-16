import { useAchievementGroupAnimation } from '@/common/hooks/useAchievementGroupAnimation';
import { render, screen, waitFor } from '@/test';

import { EventAchievementSection } from './EventAchievementSection';

vi.mock('@/common/hooks/useAchievementGroupAnimation', () => ({
  useAchievementGroupAnimation: vi.fn(),
}));

describe('Component: EventAchievementSection', () => {
  beforeEach(() => {
    const mockUseAnimation = vi.mocked(useAchievementGroupAnimation);
    mockUseAnimation.mockReturnValue({
      childContainerRef: { current: null },
      contentRef: { current: null },
      isInitialRender: { current: true },
      isOpen: true,
      setIsOpen: vi.fn(),
    });
  });

  afterEach(() => {
    vi.clearAllMocks();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <EventAchievementSection title="title" isInitiallyOpened={true} achievementCount={2}>
        children
      </EventAchievementSection>,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the provided title', async () => {
    // ARRANGE
    render(
      <EventAchievementSection title="title" isInitiallyOpened={true} achievementCount={2}>
        children
      </EventAchievementSection>,
    );

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/title/i)).toBeVisible();
    });
  });

  it('displays children', { retry: 3 }, async () => {
    // ARRANGE
    render(
      <EventAchievementSection title="title" isInitiallyOpened={true} achievementCount={2}>
        children
      </EventAchievementSection>,
    );

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/children/i)).toBeVisible();
    });
  });

  it('displays the count of achievements', () => {
    // ARRANGE
    render(
      <EventAchievementSection title="title" isInitiallyOpened={true} achievementCount={2}>
        children
      </EventAchievementSection>,
    );

    // ASSERT
    expect(screen.getByText(/2 achievements/i)).toBeInTheDocument();
  });

  it('given the section is initially closed, applies the hidden class', () => {
    // ARRANGE
    vi.mocked(useAchievementGroupAnimation).mockReturnValue({
      childContainerRef: { current: null },
      contentRef: { current: null },
      isInitialRender: { current: true },
      isOpen: false,
      setIsOpen: vi.fn(),
    });

    // ACT
    render(
      <EventAchievementSection title="title" isInitiallyOpened={false} achievementCount={2}>
        <div data-testid="child-content">children</div>
      </EventAchievementSection>,
    );

    // ASSERT
    const contentDiv = screen.getByText(/children/i).closest('div[class*="overflow-hidden"]');
    expect(contentDiv).toHaveClass('h-0');
    expect(contentDiv).toHaveClass('overflow-hidden');
  });

  it('given the section is closed but it is not the initial render, does not apply the hidden class', () => {
    // ARRANGE
    vi.mocked(useAchievementGroupAnimation).mockReturnValue({
      childContainerRef: { current: null },
      contentRef: { current: null },
      isInitialRender: { current: false },
      isOpen: false,
      setIsOpen: vi.fn(),
    });

    // ACT
    render(
      <EventAchievementSection title="title" isInitiallyOpened={true} achievementCount={2}>
        <div data-testid="child-content">children</div>
      </EventAchievementSection>,
    );

    // ASSERT
    const contentDiv = screen.getByText(/children/i).parentElement?.parentElement;
    expect(contentDiv).not.toHaveClass('h-0');
    expect(contentDiv).not.toHaveClass('overflow-hidden');
  });
});
