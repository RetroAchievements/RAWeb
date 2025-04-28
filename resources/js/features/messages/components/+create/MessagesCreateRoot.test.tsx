import userEvent from '@testing-library/user-event';

import { render, screen, waitFor } from '@/test';

import { MessagesCreateRoot } from './MessagesCreateRoot';

// Suppress JSDOM errors that are not relevant.
console.error = vi.fn();

describe('Component: MessagesCreateRoot', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<MessagesCreateRoot />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given no preview content, does not show the preview section', () => {
    // ARRANGE
    render(<MessagesCreateRoot />);

    // ASSERT
    expect(screen.queryByRole('region', { name: /preview/i })).not.toBeInTheDocument();
  });

  it('given the user previews their message, shows the preview content', async () => {
    // ARRANGE
    render(<MessagesCreateRoot />);

    // ACT
    await userEvent.type(screen.getByPlaceholderText(/don't ask for links/i), 'hello world');
    await userEvent.click(screen.getByRole('button', { name: /preview/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getAllByText('hello world')[0]).toBeVisible();
    });
  });

  it('has accessible breadcrumbs', () => {
    // ARRANGE
    render(<MessagesCreateRoot />);

    // ASSERT
    expect(screen.getByRole('navigation')).toBeVisible();
    expect(screen.getByText(/start new message thread/i)).toBeVisible();
  });
});
