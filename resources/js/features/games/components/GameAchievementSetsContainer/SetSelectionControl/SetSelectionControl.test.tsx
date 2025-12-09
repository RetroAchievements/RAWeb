import { render, screen } from '@/test';
import { createGame, createGameAchievementSet, createZiggyProps } from '@/test/factories';

import { SetSelectionControl } from './SetSelectionControl';

// We'll just shallow render the children, it's not a huge deal.
vi.mock('./SetSelectionDropdown', () => ({
  SetSelectionDropdown: () => <div data-testid="set-selection-dropdown" />,
}));
vi.mock('./SetSelectionTabs', () => ({
  SetSelectionTabs: () => <div data-testid="set-selection-tabs" />,
}));

describe('Component: SetSelectionControl', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<SetSelectionControl activeTab={null} />, {
      pageProps: {
        game: createGame(),
        selectableGameAchievementSets: [],
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the device is mobile and there are 5 or more sets, renders the dropdown', () => {
    // ARRANGE
    const sets = [
      createGameAchievementSet(),
      createGameAchievementSet(),
      createGameAchievementSet(),
      createGameAchievementSet(),
      createGameAchievementSet(),
    ];

    render(<SetSelectionControl activeTab={123} />, {
      pageProps: {
        game: createGame(),
        selectableGameAchievementSets: sets,
        ziggy: createZiggyProps({ device: 'mobile' }), // !!
      },
    });

    // ASSERT
    expect(screen.getByTestId('set-selection-dropdown')).toBeVisible();
    expect(screen.queryByTestId('set-selection-tabs')).not.toBeInTheDocument();
  });

  it('given the device is mobile and there are fewer than 5 sets, renders the tabs', () => {
    // ARRANGE
    const sets = [
      createGameAchievementSet(),
      createGameAchievementSet(),
      createGameAchievementSet(),
      createGameAchievementSet(), // only 4 sets
    ];

    render(<SetSelectionControl activeTab={456} />, {
      pageProps: {
        game: createGame(),
        selectableGameAchievementSets: sets,
        ziggy: createZiggyProps({ device: 'mobile' }), // !!
      },
    });

    // ASSERT
    expect(screen.getByTestId('set-selection-tabs')).toBeVisible();
    expect(screen.queryByTestId('set-selection-dropdown')).not.toBeInTheDocument();
  });

  it('given the device is desktop and there are 5 or more sets, renders the tabs', () => {
    // ARRANGE
    const sets = [
      createGameAchievementSet(),
      createGameAchievementSet(),
      createGameAchievementSet(),
      createGameAchievementSet(),
      createGameAchievementSet(),
    ];

    render(<SetSelectionControl activeTab={789} />, {
      pageProps: {
        game: createGame(),
        selectableGameAchievementSets: sets,
        ziggy: createZiggyProps({ device: 'desktop' }), // !!
      },
    });

    // ASSERT
    expect(screen.getByTestId('set-selection-tabs')).toBeVisible();
    expect(screen.queryByTestId('set-selection-dropdown')).not.toBeInTheDocument();
  });

  it('given the device is desktop and there are fewer than 5 sets, renders the tabs', () => {
    // ARRANGE
    const sets = [createGameAchievementSet()];

    render(<SetSelectionControl activeTab={null} />, {
      pageProps: {
        game: createGame(),
        selectableGameAchievementSets: sets,
        ziggy: createZiggyProps({ device: 'desktop' }), // !!
      },
    });

    // ASSERT
    expect(screen.getByTestId('set-selection-tabs')).toBeVisible();
    expect(screen.queryByTestId('set-selection-dropdown')).not.toBeInTheDocument();
  });
});
