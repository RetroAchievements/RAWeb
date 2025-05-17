import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';
import { createGame } from '@/test/factories';

import { GameMetadata } from './GameMetadata';

describe('Component: GameMetadata', () => {
  const createMockMetaRowElements = () => ({
    developerRowElements: [{ label: 'Developer 1' }, { label: 'Developer 2' }],
    publisherRowElements: [{ label: 'Publisher 1' }],
    genreRowElements: [{ label: 'Genre 1', href: '/genres/1' }],
    languageRowElements: [{ label: 'English' }, { label: 'Spanish' }],
    featureRowElements: [{ label: 'Feature 1' }],
    perspectiveRowElements: [{ label: 'First Person' }],
    themeRowElements: [{ label: 'Theme 1' }],
    creditRowElements: [{ label: 'Credit 1' }],
    technicalRowElements: [{ label: 'Technical 1' }],
    miscRowElements: [{ label: 'Misc 1' }],
    protagonistRowElements: [{ label: 'Protagonist 1' }],
    settingRowElements: [{ label: 'Setting 1' }],
    regionalRowElements: [{ label: 'Regional 1' }],
    raFeatureRowElements: [{ label: 'RA Feature 1' }],
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <GameMetadata allMetaRowElements={createMockMetaRowElements() as any} game={createGame()} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given initial render, shows only the primary metadata rows', () => {
    // ARRANGE
    render(
      <GameMetadata allMetaRowElements={createMockMetaRowElements() as any} game={createGame()} />,
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

  it('given the game has a release date, shows it in the metadata', () => {
    // ARRANGE
    const game = createGame({
      releasedAt: '2023-01-01',
      releasedAtGranularity: 'day',
    });

    render(<GameMetadata allMetaRowElements={createMockMetaRowElements() as any} game={game} />);

    // ASSERT
    expect(screen.getByText(/jan 1, 2023/i)).toBeVisible();
  });

  it('given the game has no release date, does not show the release date row', () => {
    // ARRANGE
    const game = createGame({ releasedAt: null });

    render(<GameMetadata allMetaRowElements={createMockMetaRowElements() as any} game={game} />);

    // ASSERT
    expect(screen.queryByText(/released/i)).not.toBeInTheDocument();
  });

  it('given the user clicks see more, shows additional metadata rows', async () => {
    // ARRANGE
    render(
      <GameMetadata allMetaRowElements={createMockMetaRowElements() as any} game={createGame()} />,
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
      <GameMetadata allMetaRowElements={createMockMetaRowElements() as any} game={createGame()} />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /see more/i }));

    // ASSERT
    expect(screen.queryByRole('button', { name: /see more/i })).not.toBeInTheDocument();
  });

  it('given a metadata row has links, renders them as clickable elements', () => {
    // ARRANGE
    render(
      <GameMetadata allMetaRowElements={createMockMetaRowElements() as any} game={createGame()} />,
    );

    // ASSERT
    const link = screen.getByRole('link', { name: /genre 1/i });
    expect(link).toBeVisible();
    expect(link).toHaveAttribute('href', '/genres/1');
  });
});
