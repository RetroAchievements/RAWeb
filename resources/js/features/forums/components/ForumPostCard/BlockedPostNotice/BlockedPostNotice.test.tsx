import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';

import { BlockedPostNotice } from './BlockedPostNotice';

describe('Component: BlockedPostNotice', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <BlockedPostNotice authorDisplayName="Scott" onReveal={vi.fn()} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given no reveal handler, it names nobody and offers no control', () => {
    // ARRANGE
    render(<BlockedPostNotice />);

    // ASSERT
    expect(screen.getByText('Hidden post')).toBeVisible();
    expect(screen.queryByRole('button')).not.toBeInTheDocument();
  });

  it('given an author name but no reveal handler, the name is still withheld', () => {
    // ARRANGE
    render(<BlockedPostNotice authorDisplayName="Scott" />);

    // ASSERT
    expect(screen.getByText('Hidden post')).toBeVisible();
    expect(screen.queryByText(/scott/i)).not.toBeInTheDocument();
  });

  it('given an author display name and a reveal handler, names them in the notice', () => {
    // ARRANGE
    render(<BlockedPostNotice authorDisplayName="Scott" onReveal={vi.fn()} />);

    // ASSERT
    expect(screen.getByText(/hidden post from scott/i)).toBeVisible();
  });

  it('given the user activates the reveal control, calls the reveal handler', async () => {
    // ARRANGE
    const onReveal = vi.fn();
    render(<BlockedPostNotice authorDisplayName="Scott" onReveal={onReveal} />);

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Show post' }));

    // ASSERT
    expect(onReveal).toHaveBeenCalledOnce();
  });
});
