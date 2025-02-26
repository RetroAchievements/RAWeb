/* eslint-disable testing-library/no-node-access -- need to test literal DOM nodes */

import userEvent from '@testing-library/user-event';

import { __UNSAFE_VERY_DANGEROUS_SLEEP, render, screen, waitFor } from '@/test';

import { EventAchievementSection } from './EventAchievementSection';
import { useEventAchievementSectionAnimation } from './useEventAchievementSectionAnimation';

vi.mock('./useEventAchievementSectionAnimation', () => ({
  useEventAchievementSectionAnimation: vi.fn(),
}));

describe('Component: EventAchievementSection', () => {
  beforeEach(() => {
    const mockUseAnimation = vi.mocked(useEventAchievementSectionAnimation);
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
      <EventAchievementSection title="title" isInitiallyOpened={true}>
        children
      </EventAchievementSection>,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the provided title', async () => {
    // ARRANGE
    render(
      <EventAchievementSection title="title" isInitiallyOpened={true}>
        children
      </EventAchievementSection>,
    );

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/title/i)).toBeVisible();
    });
  });

  it('displays children', async () => {
    // ARRANGE
    render(
      <EventAchievementSection title="title" isInitiallyOpened={true}>
        children
      </EventAchievementSection>,
    );

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/children/i)).toBeVisible();
    });
  });

  it('given the user clicks on the trigger, toggles the section visibility', async () => {
    // ARRANGE
    render(
      <EventAchievementSection title="title" isInitiallyOpened={true}>
        children
      </EventAchievementSection>,
    );

    // ACT
    await userEvent.click(screen.getByText(/title/i));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/children/i)).not.toBeVisible();
    });
  });

  it('given the user clicks on the trigger twice, makes section content visible again', async () => {
    // ARRANGE
    render(
      <EventAchievementSection title="title" isInitiallyOpened={true}>
        children
      </EventAchievementSection>,
    );

    // ACT
    await userEvent.click(screen.getByText(/title/i));
    await __UNSAFE_VERY_DANGEROUS_SLEEP(2000); // we can't use a waitFor or a findBy* fn
    await userEvent.click(screen.getByText(/title/i));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/children/i)).toBeVisible();
    });
  });

  it('given the section is initially closed, applies the hidden class', () => {
    // ARRANGE
    vi.mocked(useEventAchievementSectionAnimation).mockReturnValue({
      childContainerRef: { current: null },
      contentRef: { current: null },
      isInitialRender: { current: true },
      isOpen: false,
      setIsOpen: vi.fn(),
    });

    // ACT
    render(
      <EventAchievementSection title="title" isInitiallyOpened={true}>
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
    vi.mocked(useEventAchievementSectionAnimation).mockReturnValue({
      childContainerRef: { current: null },
      contentRef: { current: null },
      isInitialRender: { current: false },
      isOpen: false,
      setIsOpen: vi.fn(),
    });

    // ACT
    render(
      <EventAchievementSection title="title" isInitiallyOpened={true}>
        <div data-testid="child-content">children</div>
      </EventAchievementSection>,
    );

    // ASSERT
    const contentDiv = screen.getByText(/children/i).parentElement?.parentElement;
    expect(contentDiv).not.toHaveClass('h-0');
    expect(contentDiv).not.toHaveClass('overflow-hidden');
  });
});
