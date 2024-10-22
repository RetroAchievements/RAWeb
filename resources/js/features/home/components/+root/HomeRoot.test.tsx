import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';

import { HomeRoot } from './HomeRoot';

// recharts is going to throw errors in JSDOM that we don't care about.
console.warn = vi.fn();

describe('Component: HomeRoot', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<HomeRoot />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays several section components', () => {
    // ARRANGE
    render(<HomeRoot />);

    // ASSERT
    expect(screen.getByRole('heading', { name: /news/i })).toBeVisible();
    expect(screen.getByRole('heading', { name: /just released/i })).toBeVisible();
    expect(screen.getByRole('heading', { name: /active players/i })).toBeVisible();
    expect(screen.getByRole('heading', { name: /currently online/i })).toBeVisible();
    expect(screen.getByRole('heading', { name: /latest sets in progress/i })).toBeVisible();
    expect(screen.getByRole('heading', { name: /forum recent posts/i })).toBeVisible();
  });

  it('given the user is not logged in, shows a welcome section', () => {
    // ARRANGE
    render(<HomeRoot />, { pageProps: { auth: null } });

    // ASSERT
    expect(screen.getByRole('heading', { name: /welcome/i })).toBeVisible();
  });

  it('given the user is logged in, does not show a welcome section', () => {
    // ARRANGE
    render(<HomeRoot />, { pageProps: { auth: { user: createAuthenticatedUser() } } });

    // ASSERT
    expect(screen.queryByRole('heading', { name: /welcome/i })).not.toBeInTheDocument();
  });
});
