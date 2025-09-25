import userEvent from '@testing-library/user-event';

import { render, screen, waitFor } from '@/test';

import { useCanShowScrollToTopButton } from '../../hooks/useCanShowScrollToTopButton';
import { ScrollToTopButton } from './ScrollToTopButton';

vi.mock('../../hooks/useCanShowScrollToTopButton');

describe('Component: ScrollToTopButton', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders without crashing', () => {
    // ARRANGE
    vi.mocked(useCanShowScrollToTopButton).mockReturnValue(false);

    const { container } = render(<ScrollToTopButton />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the button cannot be shown, does not render the button', () => {
    // ARRANGE
    vi.mocked(useCanShowScrollToTopButton).mockReturnValue(false);

    render(<ScrollToTopButton />);

    // ASSERT
    expect(screen.queryByRole('button', { name: /scroll to top/i })).not.toBeInTheDocument();
  });

  it('given the button can be shown, renders the button', async () => {
    // ARRANGE
    vi.mocked(useCanShowScrollToTopButton).mockReturnValue(true);

    render(<ScrollToTopButton />);

    // ASSERT
    await waitFor(() => {
      expect(screen.getByRole('button', { name: /scroll to top/i })).toBeVisible();
    });
  });

  it('given the user clicks the button, scrolls to the top of the page', async () => {
    // ARRANGE
    vi.mocked(useCanShowScrollToTopButton).mockReturnValue(true);
    const scrollToSpy = vi.spyOn(window, 'scrollTo').mockImplementation(() => {});

    render(<ScrollToTopButton />);

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /scroll to top/i }));

    // ASSERT
    expect(scrollToSpy).toHaveBeenCalledWith({ top: 0, behavior: 'smooth' });
  });
});
