import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';

import { render, screen } from '@/test';
import { createAchievementSetClaim, createGame, createSystem, createUser } from '@/test/factories';

import { ClaimMobileBlock } from './ClaimMobileBlock';

dayjs.extend(utc);

describe('Component: ClaimMobileBlock', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <ClaimMobileBlock claim={createAchievementSetClaim()} variant="completed" />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the game title', () => {
    // ARRANGE
    const game = createGame({ title: 'Sonic the Hedgehog' });
    const claim = createAchievementSetClaim({ game });

    render(<ClaimMobileBlock claim={claim} variant="completed" />);

    // ASSERT
    expect(screen.getByText(/sonic the hedgehog/i)).toBeVisible();
  });

  it('given a system name, displays the game system name', () => {
    // ARRANGE
    const system = createSystem({ name: 'NES/Famicom' });
    const game = createGame({ system });
    const claim = createAchievementSetClaim({ game });

    render(<ClaimMobileBlock claim={claim} variant="completed" />);

    // ASSERT
    expect(screen.getByTestId('claim-system')).toBeVisible();
    expect(screen.getByText('NES/Famicom')).toBeVisible();
  });

  it('given there is no system associated with the game model, still renders successfully', () => {
    // ARRANGE
    const game = createGame({ system: undefined });
    const claim = createAchievementSetClaim({ game });

    render(<ClaimMobileBlock claim={claim} variant="completed" />);

    // ASSERT
    expect(screen.queryByTestId('claim-system')).not.toBeInTheDocument();
  });

  it('displays the developer display name', () => {
    // ARRANGE
    const user = createUser({ displayName: 'Scott' });
    const claim = createAchievementSetClaim({ users: [user] });

    render(<ClaimMobileBlock claim={claim} variant="completed" />);

    // ASSERT
    expect(screen.getByText(/scott/i)).toBeVisible();
  });

  it('given it uses the completed variant, shows the correct timestamp', () => {
    // ARRANGE
    vi.setSystemTime(dayjs.utc('2023-10-25').toDate());

    const user = createUser({ displayName: 'Scott' });

    const created = dayjs.utc().subtract(1, 'week').toISOString();
    const finished = dayjs.utc().subtract(1, 'hour').toISOString();
    const claim = createAchievementSetClaim({ created, finished, users: [user] });

    render(<ClaimMobileBlock claim={claim} variant="completed" />);

    // ASSERT
    expect(screen.getByText(/1 hour ago/i)).toBeVisible();
    expect(screen.queryByText(/1 week ago/i)).not.toBeInTheDocument();
  });

  it('given it uses the new variant, shows the correct timestamp', () => {
    // ARRANGE
    vi.setSystemTime(dayjs.utc('2023-10-25').toDate());

    const user = createUser({ displayName: 'Scott' });

    const created = dayjs.utc().subtract(1, 'week').toISOString();
    const finished = dayjs.utc().subtract(1, 'hour').toISOString();
    const claim = createAchievementSetClaim({ created, finished, users: [user] });

    render(<ClaimMobileBlock claim={claim} variant="new" />);

    // ASSERT
    expect(screen.getByText(/1 week ago/i)).toBeVisible();
    expect(screen.queryByText(/1 hour ago/i)).not.toBeInTheDocument();
  });
});
