import { useAchievementGroupAnimation } from '@/common/hooks/useAchievementGroupAnimation';
import { render, screen, waitFor } from '@/test';

import { AchievementGroupSection } from './AchievementGroupSection';

vi.mock('@/common/hooks/useAchievementGroupAnimation', () => ({
  useAchievementGroupAnimation: vi.fn(),
}));

describe('Component: AchievementGroupSection', () => {
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
      <AchievementGroupSection title="title" isInitiallyOpened={true} achievementCount={2}>
        children
      </AchievementGroupSection>,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the provided title', async () => {
    // ARRANGE
    render(
      <AchievementGroupSection
        title="Test Group Title"
        isInitiallyOpened={true}
        achievementCount={2}
      >
        children
      </AchievementGroupSection>,
    );

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/test group title/i)).toBeVisible();
    });
  });

  it('displays children', { retry: 3 }, async () => {
    // ARRANGE
    render(
      <AchievementGroupSection title="title" isInitiallyOpened={true} achievementCount={2}>
        children
      </AchievementGroupSection>,
    );

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/children/i)).toBeVisible();
    });
  });

  it('displays the count of achievements', () => {
    // ARRANGE
    render(
      <AchievementGroupSection title="title" isInitiallyOpened={true} achievementCount={5}>
        children
      </AchievementGroupSection>,
    );

    // ASSERT
    expect(screen.getByText(/5 achievements/i)).toBeInTheDocument(); // not using .toBeVisible() because motion.li starts with opacity: 0
  });

  it('given an iconUrl is provided, displays the icon image', () => {
    // ARRANGE
    render(
      <AchievementGroupSection
        title="title"
        isInitiallyOpened={true}
        achievementCount={2}
        iconUrl="https://example.com/icon.png"
      >
        children
      </AchievementGroupSection>,
    );

    // ASSERT
    const imgEl = screen.getByRole('presentation');
    expect(imgEl).toBeInTheDocument(); // not using .toBeVisible() because motion.li starts with opacity: 0
    expect(imgEl).toHaveAttribute('src', 'https://example.com/icon.png');
  });

  it('given no iconUrl is provided, does not display an icon image', () => {
    // ARRANGE
    render(
      <AchievementGroupSection title="title" isInitiallyOpened={true} achievementCount={2}>
        children
      </AchievementGroupSection>,
    );

    // ASSERT
    expect(screen.queryByRole('presentation')).not.toBeInTheDocument();
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
      <AchievementGroupSection title="title" isInitiallyOpened={false} achievementCount={2}>
        <div data-testid="child-content">children</div>
      </AchievementGroupSection>,
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
      <AchievementGroupSection title="title" isInitiallyOpened={true} achievementCount={2}>
        <div data-testid="child-content">children</div>
      </AchievementGroupSection>,
    );

    // ASSERT
    const contentDiv = screen.getByText(/children/i).parentElement?.parentElement;
    expect(contentDiv).not.toHaveClass('h-0');
    expect(contentDiv).not.toHaveClass('overflow-hidden');
  });
});
