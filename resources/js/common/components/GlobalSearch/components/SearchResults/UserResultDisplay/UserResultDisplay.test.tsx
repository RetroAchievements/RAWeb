import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';

import { render, screen } from '@/test';
import { createUser } from '@/test/factories';

import { UserResultDisplay } from './UserResultDisplay';

dayjs.extend(utc);

describe('Component: UserResultDisplay', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const user = createUser();

    const { container } = render(<UserResultDisplay user={user} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the user avatar with correct attributes', () => {
    // ARRANGE
    const user = createUser({
      displayName: 'JohnDoe',
      avatarUrl: 'https://example.com/john-avatar.png',
    });

    render(<UserResultDisplay user={user} />);

    // ACT
    const avatar = screen.getByRole('img');

    // ASSERT
    expect(avatar).toBeVisible();
    expect(avatar).toHaveAttribute('src', 'https://example.com/john-avatar.png');
    expect(avatar).toHaveAttribute('alt', 'JohnDoe');
  });

  it('displays the user display name', () => {
    // ARRANGE
    const user = createUser({ displayName: 'JaneSmith' });

    render(<UserResultDisplay user={user} />);

    // ASSERT
    expect(screen.getByText('JaneSmith')).toBeVisible();
  });

  it('given the user was active within the last 5 minutes, shows the active indicator', () => {
    // ARRANGE
    const mockCurrentTime = dayjs.utc('2024-01-15T12:00:00Z').toDate();
    vi.setSystemTime(mockCurrentTime);

    const user = createUser({
      lastActivityAt: '2024-01-15T11:57:00Z', // !! 3 minutes ago
    });

    render(<UserResultDisplay user={user} />);

    // ASSERT
    expect(screen.getByTestId('active-indicator')).toBeVisible();
  });

  it('given the user was active more than 5 minutes ago, does not show the active indicator', () => {
    // ARRANGE
    const mockCurrentTime = dayjs.utc('2024-01-15T12:00:00Z').toDate();
    vi.setSystemTime(mockCurrentTime);

    const user = createUser({
      lastActivityAt: '2024-01-15T11:54:00Z', // !! 6 minutes ago
    });

    render(<UserResultDisplay user={user} />);

    // ASSERT
    expect(screen.queryByTestId('active-indicator')).not.toBeInTheDocument();
  });

  it('given the user has no last activity, does not show the active indicator', () => {
    // ARRANGE
    const user = createUser({ lastActivityAt: undefined });

    render(<UserResultDisplay user={user} />);

    // ASSERT
    expect(screen.queryByTestId('active-indicator')).not.toBeInTheDocument();
  });

  it('given the user has last activity, shows the last seen label', () => {
    // ARRANGE
    const mockCurrentTime = dayjs.utc('2024-01-15T12:00:00Z').toDate();
    vi.setSystemTime(mockCurrentTime);

    const user = createUser({
      lastActivityAt: '2024-01-15T10:00:00Z', // !! 2 hours ago
    });

    render(<UserResultDisplay user={user} />);

    // ASSERT
    expect(screen.getByText(/last seen/i)).toBeVisible();
  });

  it('given the user has no last activity, does not show the last seen label', () => {
    // ARRANGE
    const user = createUser({ lastActivityAt: undefined });

    render(<UserResultDisplay user={user} />);

    // ASSERT
    expect(screen.queryByText(/last seen/i)).not.toBeInTheDocument();
  });
});
