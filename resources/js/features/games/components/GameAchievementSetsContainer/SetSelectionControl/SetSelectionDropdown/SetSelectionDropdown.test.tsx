import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';

import { currentListViewAtom } from '@/features/games/state/games.atoms';
import { BASE_SET_LABEL } from '@/features/games/utils/baseSetLabel';
import { render, screen } from '@/test';
import { createAchievementSet, createGame, createGameAchievementSet } from '@/test/factories';

import { SetSelectionDropdown } from './SetSelectionDropdown';

describe('Component: SetSelectionDropdown', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.spyOn(router, 'visit').mockImplementation(vi.fn());

    window.HTMLElement.prototype.hasPointerCapture = vi.fn();
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<SetSelectionDropdown activeTab={null} />, {
      pageProps: {
        game: createGame(),
        selectableGameAchievementSets: [createGameAchievementSet()],
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there are no selectable achievement sets, displays nothing', () => {
    // ARRANGE
    render(<SetSelectionDropdown activeTab={null} />, {
      pageProps: {
        game: createGame(),
        selectableGameAchievementSets: [], // !!
      },
    });

    // ASSERT
    expect(screen.queryByRole('combobox')).not.toBeInTheDocument();
  });

  it('given there are selectable achievement sets, renders the dropdown', () => {
    // ARRANGE
    render(<SetSelectionDropdown activeTab={null} />, {
      pageProps: {
        game: createGame(),
        selectableGameAchievementSets: [
          createGameAchievementSet({ title: 'Base Game' }),
          createGameAchievementSet({ title: 'Bonus Set' }),
        ],
      },
    });

    // ASSERT
    expect(screen.getByRole('combobox')).toBeVisible();
  });

  it('given activeTab is null, defaults to the first achievement set', () => {
    // ARRANGE
    const firstSet = createGameAchievementSet({
      achievementSet: createAchievementSet({ id: 123 }),
      title: 'First Set',
    });
    const secondSet = createGameAchievementSet({
      achievementSet: createAchievementSet({ id: 456 }),
      title: 'Second Set',
    });

    render(<SetSelectionDropdown activeTab={null} />, {
      pageProps: {
        game: createGame(),
        selectableGameAchievementSets: [firstSet, secondSet],
      },
    });

    // ASSERT
    expect(screen.getByRole('combobox')).toHaveTextContent('First Set');
  });

  it('given activeTab is provided, selects the matching achievement set', () => {
    // ARRANGE
    const firstSet = createGameAchievementSet({
      achievementSet: createAchievementSet({ id: 123 }),
      title: 'First Set',
    });
    const secondSet = createGameAchievementSet({
      achievementSet: createAchievementSet({ id: 456 }),
      title: 'Second Set',
    });

    render(<SetSelectionDropdown activeTab={456} />, {
      pageProps: {
        game: createGame(),
        selectableGameAchievementSets: [firstSet, secondSet],
      },
    });

    // ASSERT
    expect(screen.getByRole('combobox')).toHaveTextContent('Second Set');
  });

  it('given the user selects a different set, navigates to the appropriate route', async () => {
    // ARRANGE
    const game = createGame({ id: 999 });
    const firstSet = createGameAchievementSet({
      achievementSet: createAchievementSet({ id: 123 }),
      title: 'First Set',
      type: 'core',
    });
    const secondSet = createGameAchievementSet({
      achievementSet: createAchievementSet({ id: 456 }),
      title: 'Second Set',
      type: 'bonus',
    });

    render(<SetSelectionDropdown activeTab={123} />, {
      pageProps: {
        game,
        selectableGameAchievementSets: [firstSet, secondSet],
      },
      jotaiAtoms: [
        [currentListViewAtom, 'achievements'],
        //
      ],
    });

    // ACT
    await userEvent.click(screen.getByRole('combobox'));
    await userEvent.click(screen.getByText('Second Set'));

    // ASSERT
    expect(router.visit).toHaveBeenCalledWith(
      [
        'game2.show',
        {
          game: 999,
          set: 456,
          view: undefined,
        },
      ],
      { preserveScroll: true },
    );
  });

  it('given the user selects the core set, does not include a set parameter in the route transition', async () => {
    // ARRANGE
    const game = createGame({ id: 777 });
    const coreSet = createGameAchievementSet({
      achievementSet: createAchievementSet({ id: 123 }),
      title: 'Core Set',
      type: 'core',
    });
    const bonusSet = createGameAchievementSet({
      achievementSet: createAchievementSet({ id: 456 }),
      title: 'Bonus Set',
      type: 'bonus',
    });

    render(<SetSelectionDropdown activeTab={456} />, {
      pageProps: {
        game,
        selectableGameAchievementSets: [coreSet, bonusSet],
      },
      jotaiAtoms: [
        [currentListViewAtom, 'achievements'],
        //
      ],
    });

    // ACT
    await userEvent.click(screen.getByRole('combobox'));
    await userEvent.click(screen.getByText('Core Set'));

    // ASSERT
    expect(router.visit).toHaveBeenCalledWith(
      [
        'game2.show',
        {
          game: 777,
          set: undefined, // !!
          view: undefined,
        },
      ],
      { preserveScroll: true },
    );
  });

  it('given the current view is leaderboards, preserves the leaderboards view when navigating', async () => {
    // ARRANGE
    const game = createGame({ id: 888 });
    const firstSet = createGameAchievementSet({
      achievementSet: createAchievementSet({ id: 123 }),
      title: 'First Set',
      type: 'bonus',
    });
    const secondSet = createGameAchievementSet({
      achievementSet: createAchievementSet({ id: 456 }),
      title: 'Second Set',
      type: 'bonus',
    });

    render(<SetSelectionDropdown activeTab={123} />, {
      pageProps: {
        game,
        selectableGameAchievementSets: [firstSet, secondSet],
      },
      jotaiAtoms: [
        [currentListViewAtom, 'leaderboards'],
        //
      ],
    });

    // ACT
    await userEvent.click(screen.getByRole('combobox'));
    await userEvent.click(screen.getByText('Second Set'));

    // ASSERT
    expect(router.visit).toHaveBeenCalledWith(
      [
        'game2.show',
        {
          game: 888,
          set: 456,
          view: 'leaderboards', // !!
        },
      ],
      { preserveScroll: true },
    );
  });

  it('given a set has no title, displays the BASE_SET_LABEL', async () => {
    // ARRANGE
    const setWithoutTitle = createGameAchievementSet({
      achievementSet: createAchievementSet({ id: 123 }),
      title: null, // !!
    });
    const setWithTitle = createGameAchievementSet({
      achievementSet: createAchievementSet({ id: 456 }),
      title: 'Named Set',
    });

    render(<SetSelectionDropdown activeTab={123} />, {
      pageProps: {
        game: createGame(),
        selectableGameAchievementSets: [setWithoutTitle, setWithTitle],
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('combobox'));

    // ASSERT
    expect(screen.getAllByText(BASE_SET_LABEL)[0]).toBeVisible();
    expect(screen.getByText('Named Set')).toBeVisible();
  });
});
