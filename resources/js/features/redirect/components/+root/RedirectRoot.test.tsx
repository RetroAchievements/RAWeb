import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';

import { RedirectRoot } from './RedirectRoot';

describe('Component: RedirectRoot', () => {
  it('renders without crashing', () => {
    // ACT
    const { container } = render(<RedirectRoot />, {
      pageProps: {
        url: 'https://retroachievements.org',
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('redirects properly when clicked', async () => {
    // ARRANGE
    const testUrl = 'https://example.com';
    const mockReplace = vi.fn();
    Object.defineProperty(window, 'location', {
      value: { replace: mockReplace },
      writable: true,
    });

    render(<RedirectRoot />, {
      pageProps: {
        url: testUrl,
      },
    });

    // ACT
    const button = screen.getByRole('link', { name: 'Continue to external site' });
    await userEvent.click(button);

    // ASSERT
    expect(mockReplace).toHaveBeenCalledWith(testUrl);
  });
});
