import { render, screen } from '@/test';
import { createAchievementSetClaim, createGame, createSystem, createUser } from '@/test/factories';

import { ClaimMobileBlock } from './ClaimMobileBlock';

describe('Component: ClaimMobileBlock', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<ClaimMobileBlock claim={createAchievementSetClaim()} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the game title', () => {
    // ARRANGE
    const game = createGame({ title: 'Sonic the Hedgehog' });
    const claim = createAchievementSetClaim({ game });

    render(<ClaimMobileBlock claim={claim} />);

    // ASSERT
    expect(screen.getByText(/sonic the hedgehog/i)).toBeVisible();
  });

  it('given a system name, displays the game system name', () => {
    // ARRANGE
    const system = createSystem({ name: 'NES/Famicom' });
    const game = createGame({ system });
    const claim = createAchievementSetClaim({ game });

    render(<ClaimMobileBlock claim={claim} />);

    // ASSERT
    expect(screen.getByTestId('claim-system')).toBeVisible();
    expect(screen.getByText('NES/Famicom')).toBeVisible();
  });

  it('given there is no system associated with the game model, still renders successfully', () => {
    // ARRANGE
    const game = createGame({ system: undefined });
    const claim = createAchievementSetClaim({ game });

    render(<ClaimMobileBlock claim={claim} />);

    // ASSERT
    expect(screen.queryByTestId('claim-system')).not.toBeInTheDocument();
  });

  it('displays the developer display name', () => {
    // ARRANGE
    const user = createUser({ displayName: 'Scott' });
    const claim = createAchievementSetClaim({ users: [user] });

    render(<ClaimMobileBlock claim={claim} />);

    // ASSERT
    expect(screen.getByText(/scott/i)).toBeVisible();
  });
});
