/* eslint-disable jsx-a11y/no-static-element-interactions -- doesn't matter in a test suite */
/* eslint-disable jsx-a11y/click-events-have-key-events -- doesn't matter in a test suite */

import userEvent from '@testing-library/user-event';
import { route } from 'ziggy-js';

import { currentListViewAtom } from '@/features/games/state/games.atoms';
import { render, screen } from '@/test';
import {
  createAchievementSet,
  createGame,
  createGameAchievementSet,
  createZiggyProps,
} from '@/test/factories';

import { SetSelectionTabs } from './SetSelectionTabs';

vi.mock('@/common/components/InertiaLink', () => ({
  // InertiaLink doesn't play nicely with onClick in tests.
  // eslint-disable-next-line @typescript-eslint/no-unused-vars -- preserveScroll is an intentional pick
  InertiaLink: ({ children, onClick, preserveScroll, ...props }: any) => (
    <a
      {...props}
      onClick={(e: any) => {
        e.preventDefault(); // prevent navigation errors
        onClick?.();
      }}
    >
      {children}
    </a>
  ),
}));

describe('Component: SetSelectionTabs', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const game = createGame();
    const selectableGameAchievementSets = [createGameAchievementSet()];

    const { container } = render(<SetSelectionTabs activeTab={null} />, {
      pageProps: {
        game,
        selectableGameAchievementSets,
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given no selectable game achievement sets, renders nothing', () => {
    // ARRANGE
    const game = createGame();

    render(<SetSelectionTabs activeTab={null} />, {
      pageProps: {
        game,
        selectableGameAchievementSets: [],
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(screen.queryByRole('link')).not.toBeInTheDocument();
  });

  it('given selectable game achievement sets exist, renders tab images', () => {
    // ARRANGE
    const game = createGame();
    const achievementSet1 = createAchievementSet({
      id: 1,
      imageAssetPathUrl: 'https://example.com/set1.png',
    });
    const achievementSet2 = createAchievementSet({
      id: 2,
      imageAssetPathUrl: 'https://example.com/set2.png',
    });
    const selectableGameAchievementSets = [
      createGameAchievementSet({ title: 'Core Set', achievementSet: achievementSet1 }),
      createGameAchievementSet({ title: 'Bonus Set', achievementSet: achievementSet2 }),
    ];

    render(<SetSelectionTabs activeTab={null} />, {
      pageProps: {
        game,
        selectableGameAchievementSets,
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    const images = screen.getAllByRole('img');
    expect(images).toHaveLength(2);
    expect(images[0]).toHaveAttribute('src', 'https://example.com/set1.png');
    expect(images[0]).toHaveAttribute('alt', 'Core Set');
    expect(images[1]).toHaveAttribute('src', 'https://example.com/set2.png');
    expect(images[1]).toHaveAttribute('alt', 'Bonus Set');
  });

  it('given an achievement set without a title, uses "Base Set" as alt text', () => {
    // ARRANGE
    const game = createGame();
    const selectableGameAchievementSets = [createGameAchievementSet({ title: null })];

    render(<SetSelectionTabs activeTab={null} />, {
      pageProps: {
        game,
        selectableGameAchievementSets,
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    const image = screen.getByRole('img');
    expect(image).toHaveAttribute('alt', 'Base Set');
  });

  it('given an activeTab prop matches a set ID, finds the correct initial index', () => {
    // ARRANGE
    const game = createGame();
    const achievementSet1 = createAchievementSet({ id: 100 });
    const achievementSet2 = createAchievementSet({ id: 200 });
    const achievementSet3 = createAchievementSet({ id: 300 });
    const selectableGameAchievementSets = [
      createGameAchievementSet({ achievementSet: achievementSet1, title: 'Set 1' }),
      createGameAchievementSet({ achievementSet: achievementSet2, title: 'Set 2' }),
      createGameAchievementSet({ achievementSet: achievementSet3, title: 'Set 3' }),
    ];

    render(<SetSelectionTabs activeTab={200} />, {
      // !! activeTab matches second achievement set's ID
      pageProps: {
        game,
        selectableGameAchievementSets,
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    // eslint-disable-next-line testing-library/no-node-access -- required for this specific test
    const secondTabDiv = screen.getByAltText('Set 2').closest('div');
    expect(secondTabDiv).toHaveClass('text-white');
  });

  it('given activeTab does not match any set ID, defaults to first tab', () => {
    // ARRANGE
    const game = createGame();
    const achievementSet1 = createAchievementSet({ id: 100 });
    const achievementSet2 = createAchievementSet({ id: 200 });
    const selectableGameAchievementSets = [
      createGameAchievementSet({ achievementSet: achievementSet1, title: 'Set 1' }),
      createGameAchievementSet({ achievementSet: achievementSet2, title: 'Set 2' }),
    ];

    render(<SetSelectionTabs activeTab={999} />, {
      // !! activeTab doesn't match any achievement set id
      pageProps: {
        game,
        selectableGameAchievementSets,
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    // eslint-disable-next-line testing-library/no-node-access -- required for this specific test
    const firstTabDiv = screen.getByAltText('Set 1').closest('div');
    expect(firstTabDiv).toHaveClass('text-white');
  });

  it('does not crash when indicator animations are ready', () => {
    // ARRANGE
    const game = createGame();
    const selectableGameAchievementSets = [createGameAchievementSet()];

    const { container } = render(<SetSelectionTabs activeTab={null} />, {
      pageProps: {
        game,
        selectableGameAchievementSets,
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a tab is clicked, does not crash', async () => {
    // ARRANGE
    const game = createGame();
    const achievementSet1 = createAchievementSet({ id: 10 });
    const achievementSet2 = createAchievementSet({ id: 20 });
    const selectableGameAchievementSets = [
      createGameAchievementSet({ achievementSet: achievementSet1, title: 'Set 1' }),
      createGameAchievementSet({ achievementSet: achievementSet2, title: 'Set 2' }),
    ];

    const { container } = render(<SetSelectionTabs activeTab={10} />, {
      pageProps: {
        game,
        selectableGameAchievementSets,
        ziggy: createZiggyProps(),
      },
    });

    // ACT
    const secondTabLink = screen.getAllByRole('link')[1];
    await userEvent.click(secondTabLink);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the device is mobile, does not display any tooltips', async () => {
    // ARRANGE
    const game = createGame();
    const achievementSet1 = createAchievementSet({ id: 10 });
    const achievementSet2 = createAchievementSet({ id: 20 });
    const selectableGameAchievementSets = [
      createGameAchievementSet({ achievementSet: achievementSet1, title: 'Set 1' }),
      createGameAchievementSet({ achievementSet: achievementSet2, title: 'Set 2' }),
    ];

    render(<SetSelectionTabs activeTab={10} />, {
      pageProps: {
        game,
        selectableGameAchievementSets,
        ziggy: createZiggyProps({ device: 'mobile' }),
      },
    });

    // ACT
    await userEvent.hover(screen.getByRole('link', { name: /set 1/i }));

    // ASSERT
    expect(screen.queryByRole('tooltip')).not.toBeInTheDocument();
  });

  it('given the user is viewing leaderboards, respects that in the hrefs', async () => {
    // ARRANGE
    const game = createGame({ id: 123 });
    const achievementSet1 = createAchievementSet({ id: 10 });
    const achievementSet2 = createAchievementSet({ id: 20 });
    const selectableGameAchievementSets = [
      createGameAchievementSet({ achievementSet: achievementSet1, title: 'Set 1', type: 'core' }),
      createGameAchievementSet({ achievementSet: achievementSet2, title: 'Set 2', type: 'bonus' }),
    ];

    render(<SetSelectionTabs activeTab={10} />, {
      pageProps: {
        game,
        selectableGameAchievementSets,
        ziggy: createZiggyProps(),
      },
      jotaiAtoms: [
        [currentListViewAtom, 'leaderboards'], // !!
        //
      ],
    });

    // ASSERT
    expect(route).toHaveBeenCalledWith(
      'game2.show',
      expect.objectContaining({
        view: 'leaderboards',
      }),
    );
  });
});
