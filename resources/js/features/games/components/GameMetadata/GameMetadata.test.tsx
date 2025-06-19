import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';
import { createGame, createGameRelease, createGameSet } from '@/test/factories';

import { GameMetadata } from './GameMetadata';

describe('Component: GameMetadata', () => {
  const createMockMetaRowElements = (overrides = {}) => ({
    creditRowElements: [{ label: 'Credit 1' }],
    developerRowElements: [{ label: 'Developer 1' }, { label: 'Developer 2' }],
    featureRowElements: [{ label: 'Feature 1' }],
    genreRowElements: [{ label: 'Genre 1', href: '/genres/1' }],
    hackOfRowElements: [],
    languageRowElements: [{ label: 'English' }, { label: 'Spanish' }],
    miscRowElements: [{ label: 'Misc 1' }],
    perspectiveRowElements: [{ label: 'First Person' }],
    protagonistRowElements: [{ label: 'Protagonist 1' }],
    publisherRowElements: [{ label: 'Publisher 1' }],
    raFeatureRowElements: [{ label: 'RA Feature 1' }],
    regionalRowElements: [{ label: 'Regional 1' }],
    settingRowElements: [{ label: 'Setting 1' }],
    technicalRowElements: [{ label: 'Technical 1' }],
    themeRowElements: [{ label: 'Theme 1' }],
    formatRowElements: [],
    ...overrides,
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <GameMetadata
        allMetaRowElements={createMockMetaRowElements() as any}
        game={createGame()}
        hubs={[]}
      />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given initial render, shows only the primary metadata rows', () => {
    // ARRANGE
    render(
      <GameMetadata
        allMetaRowElements={createMockMetaRowElements() as any}
        game={createGame()}
        hubs={[]}
      />,
    );

    // ASSERT
    // ... primary rows should be visible ...
    expect(screen.getByText(/developer 1/i)).toBeVisible();
    expect(screen.getByText(/publisher 1/i)).toBeVisible();
    expect(screen.getByText(/genre 1/i)).toBeVisible();
    expect(screen.getByText(/english/i)).toBeVisible();
    expect(screen.getByText(/feature 1/i)).toBeVisible();

    // ... secondary rows should not be initially visible ...
    expect(screen.queryByText(/theme 1/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/setting 1/i)).not.toBeInTheDocument();
  });

  it('given the game has releases with dates, shows the releases row', () => {
    // ARRANGE
    const game = createGame({
      releases: [createGameRelease(), createGameRelease()],
    });

    render(
      <GameMetadata
        allMetaRowElements={createMockMetaRowElements() as any}
        game={game}
        hubs={[]}
      />,
    );

    // ASSERT
    expect(screen.getByText(/release/i)).toBeVisible();
  });

  it('given the game has no release date, does not show the release date row', () => {
    // ARRANGE
    const game = createGame({ releasedAt: null });

    render(
      <GameMetadata
        allMetaRowElements={createMockMetaRowElements() as any}
        game={game}
        hubs={[]}
      />,
    );

    // ASSERT
    expect(screen.queryByText(/released/i)).not.toBeInTheDocument();
  });

  it('given the user clicks see more, shows additional metadata rows', async () => {
    // ARRANGE
    render(
      <GameMetadata
        allMetaRowElements={createMockMetaRowElements() as any}
        game={createGame()}
        hubs={[]}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /see more/i }));

    // ASSERT
    expect(screen.getByText(/theme 1/i)).toBeVisible();
    expect(screen.getByText(/setting 1/i)).toBeVisible();
    expect(screen.getByText(/protagonist 1/i)).toBeVisible();
    expect(screen.getByText(/technical 1/i)).toBeVisible();
    expect(screen.getByText(/regional 1/i)).toBeVisible();
    expect(screen.getByText(/misc 1/i)).toBeVisible();
    expect(screen.getByText(/ra feature 1/i)).toBeVisible();
  });

  it('given the user has expanded the metadata, hides the see more button', async () => {
    // ARRANGE
    render(
      <GameMetadata
        allMetaRowElements={createMockMetaRowElements() as any}
        game={createGame()}
        hubs={[]}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /see more/i }));

    // ASSERT
    expect(screen.queryByRole('button', { name: /see more/i })).not.toBeInTheDocument();
  });

  it('given a metadata row has links, renders them as clickable elements', () => {
    // ARRANGE
    render(
      <GameMetadata
        allMetaRowElements={createMockMetaRowElements() as any}
        game={createGame()}
        hubs={[]}
      />,
    );

    // ASSERT
    const link = screen.getByRole('link', { name: /genre 1/i });
    expect(link).toBeVisible();
    expect(link).toHaveAttribute('href', '/genres/1');
  });

  it('given publisher elements exist and no hack-of elements, shows the publisher row', () => {
    // ARRANGE
    const metaRowElements = createMockMetaRowElements({
      publisherRowElements: [{ label: 'Publisher 1' }],
      hackOfRowElements: [],
    });

    render(
      <GameMetadata allMetaRowElements={metaRowElements as any} game={createGame()} hubs={[]} />,
    );

    // ASSERT
    expect(screen.getByText(/publisher 1/i)).toBeVisible();
  });

  it('given publisher elements contain "Hack -" and hack-of elements exist, hides the publisher row', () => {
    // ARRANGE
    const metaRowElements = createMockMetaRowElements({
      publisherRowElements: [{ label: 'Hack - Super Mario 64' }],
      hackOfRowElements: [{ label: 'Super Mario 64' }],
    });

    render(
      <GameMetadata allMetaRowElements={metaRowElements as any} game={createGame()} hubs={[]} />,
    );

    // ASSERT
    expect(screen.queryByText(/publisher/i)).not.toBeInTheDocument();

    expect(screen.getByText(/hack of/i)).toBeVisible();
    expect(screen.getByText(/super mario 64/i)).toBeVisible();
  });

  it('given publisher elements with mixed content and hack-of elements exist, shows the publisher row', () => {
    // ARRANGE
    const metaRowElements = createMockMetaRowElements({
      publisherRowElements: [{ label: 'Hack - Publisher' }, { label: 'Regular Publisher' }],
      hackOfRowElements: [{ label: 'Original Game' }],
    });

    render(
      <GameMetadata allMetaRowElements={metaRowElements as any} game={createGame()} hubs={[]} />,
    );

    // ASSERT
    expect(screen.getByText(/hack - publisher/i)).toBeVisible(); // !! only one match
    expect(screen.getByText(/regular publisher/i)).toBeVisible();
    expect(screen.getByText(/original game/i)).toBeVisible();
  });

  it('given the game has event hubs, processes and displays them when see more is clicked', async () => {
    // ARRANGE
    const hubs = [
      createGameSet({ id: 1, title: 'Regular Hub', isEventHub: false }),
      createGameSet({ id: 2, title: 'Event Hub 2023', isEventHub: true }),
      createGameSet({ id: 3, title: 'AotW 2023-10', isEventHub: true }),
      createGameSet({ id: 4, title: 'RA Awards 2022', isEventHub: true }),
    ];

    render(
      <GameMetadata
        allMetaRowElements={createMockMetaRowElements() as any}
        game={createGame()}
        hubs={hubs}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /see more/i }));

    // ASSERT
    expect(screen.queryByText(/regular hub/i)).not.toBeInTheDocument();

    expect(screen.getByText(/event hub 2023/i)).toBeVisible();
    expect(screen.getByText(/aotw 2023-10/i)).toBeVisible();
    expect(screen.getByText(/ra awards 2022/i)).toBeVisible();
  });

  it('given the game has no event hubs, does not display any event hub entries', async () => {
    // ARRANGE
    const hubs = [
      createGameSet({ id: 1, title: 'Regular Hub 1', isEventHub: false }),
      createGameSet({ id: 2, title: 'Regular Hub 2', isEventHub: false }),
    ];

    render(
      <GameMetadata
        allMetaRowElements={createMockMetaRowElements() as any}
        game={createGame()}
        hubs={hubs}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /see more/i }));

    // ASSERT
    expect(screen.queryByText(/regular hub 1/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/regular hub 2/i)).not.toBeInTheDocument();
  });

  it('given there is only one see more row with content, automatically expands that section', () => {
    // ARRANGE
    const metaRowElements = createMockMetaRowElements({
      // Empty all "see more" sections except one
      protagonistRowElements: [],
      themeRowElements: [],
      settingRowElements: [],
      formatRowElements: [],
      technicalRowElements: [],
      regionalRowElements: [],
      miscRowElements: [{ label: 'Only See More Item' }],
      raFeatureRowElements: [],
    });

    render(
      <GameMetadata allMetaRowElements={metaRowElements as any} game={createGame()} hubs={[]} />,
    );

    // ASSERT
    expect(screen.getByText(/only see more item/i)).toBeVisible();

    expect(screen.queryByRole('button', { name: /see more/i })).not.toBeInTheDocument();
  });
});
