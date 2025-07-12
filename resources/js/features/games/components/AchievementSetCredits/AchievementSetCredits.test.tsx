import { render, screen } from '@/test';
import { createUserCredits } from '@/test/factories';

import { AchievementSetCredits } from './AchievementSetCredits';

// Shallow render the children.
vi.mock('./AchievementAuthorsDisplay', () => ({
  AchievementAuthorsDisplay: vi.fn(() => <div data-testid="achievement-authors-display" />),
}));
vi.mock('./ArtworkCreditsDisplay', () => ({
  ArtworkCreditsDisplay: vi.fn(() => <div data-testid="artwork-credits-display" />),
}));
vi.mock('./CodeCreditsDisplay', () => ({
  CodeCreditsDisplay: vi.fn(() => <div data-testid="code-credits-display" />),
}));
vi.mock('./DesignCreditsDisplay', () => ({
  DesignCreditsDisplay: vi.fn(() => <div data-testid="design-credits-display" />),
}));

describe('Component: AchievementSetCredits', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<AchievementSetCredits />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given no aggregate credits, returns null', () => {
    // ARRANGE
    render(<AchievementSetCredits />, {
      pageProps: { aggregateCredits: null },
    });

    // ASSERT
    expect(screen.queryByTestId('set-credits')).not.toBeInTheDocument();
  });

  it('given aggregate credits exist, always shows the achievement authors display', () => {
    // ARRANGE
    const aggregateCredits = {
      achievementsAuthors: [],
      achievementSetArtwork: [],
      achievementsArtwork: [],
      achievementsLogic: [],
      achievementsMaintainers: [],
      achievementsDesign: [],
      achievementsTesting: [],
      achievementsWriting: [],
      hashCompatibilityTesting: [],
    };

    render(<AchievementSetCredits />, { pageProps: { aggregateCredits } });

    // ASSERT
    expect(screen.getByTestId('set-credits')).toBeVisible();
    expect(screen.getByTestId('achievement-authors-display')).toBeVisible();
  });

  it('given artwork credits exist, shows the artwork credits display', () => {
    // ARRANGE
    const aggregateCredits = {
      achievementsAuthors: [],
      achievementSetArtwork: [createUserCredits({ displayName: 'Alice' })],
      achievementsArtwork: [createUserCredits({ displayName: 'Bob' })],
      achievementsLogic: [],
      achievementsMaintainers: [],
      achievementsDesign: [],
      achievementsTesting: [],
      achievementsWriting: [],
      hashCompatibilityTesting: [],
    };

    render(<AchievementSetCredits />, { pageProps: { aggregateCredits } });

    // ASSERT
    expect(screen.getByTestId('artwork-credits-display')).toBeVisible();
  });

  it('given coding credits exist, shows the code credits display', () => {
    // ARRANGE
    const aggregateCredits = {
      achievementsAuthors: [],
      achievementSetArtwork: [],
      achievementsArtwork: [],
      achievementsLogic: [createUserCredits({ displayName: 'Charlie' })],
      achievementsMaintainers: [createUserCredits({ displayName: 'David' })],
      achievementsDesign: [],
      achievementsTesting: [],
      achievementsWriting: [],
      hashCompatibilityTesting: [],
    };

    render(<AchievementSetCredits />, { pageProps: { aggregateCredits } });

    // ASSERT
    expect(screen.getByTestId('code-credits-display')).toBeVisible();
  });

  it('given design credits exist, shows the design credits display', () => {
    // ARRANGE
    const aggregateCredits = {
      achievementsAuthors: [],
      achievementSetArtwork: [],
      achievementsArtwork: [],
      achievementsLogic: [],
      achievementsMaintainers: [],
      achievementsDesign: [createUserCredits({ displayName: 'Eve' })],
      achievementsTesting: [createUserCredits({ displayName: 'Frank' })],
      achievementsWriting: [createUserCredits({ displayName: 'Grace' })],
      hashCompatibilityTesting: [],
    };

    render(<AchievementSetCredits />, { pageProps: { aggregateCredits } });

    // ASSERT
    expect(screen.getByTestId('design-credits-display')).toBeVisible();
  });

  it('given duplicate users in artwork credits, deduplicates them before deciding to show the component', () => {
    // ARRANGE
    const sharedUser = createUserCredits({ displayName: 'Alice' });
    const aggregateCredits = {
      achievementsAuthors: [],
      achievementSetArtwork: [sharedUser],
      achievementsArtwork: [sharedUser], // !! same user in both arrays
      achievementsLogic: [],
      achievementsMaintainers: [],
      achievementsDesign: [],
      achievementsTesting: [],
      achievementsWriting: [],
      hashCompatibilityTesting: [],
    };

    render(<AchievementSetCredits />, { pageProps: { aggregateCredits } });

    // ASSERT
    // ... component should still show because there's at least one unique user ...
    expect(screen.getByTestId('artwork-credits-display')).toBeVisible();
  });

  it('given all logic users are already authors, does not show the code credits display', () => {
    // ARRANGE
    const author = createUserCredits({ displayName: 'Alice' });
    const aggregateCredits = {
      achievementsAuthors: [author],
      achievementSetArtwork: [],
      achievementsArtwork: [],
      achievementsLogic: [author], // !! same user is both author and logic credit
      achievementsMaintainers: [], // !! no maintainers
      achievementsDesign: [],
      achievementsTesting: [],
      achievementsWriting: [],
      hashCompatibilityTesting: [],
    };

    render(<AchievementSetCredits />, { pageProps: { aggregateCredits } });

    // ASSERT
    // ... code credits should not show because filtered logic credits is empty and no maintainers ...
    expect(screen.queryByTestId('code-credits-display')).not.toBeInTheDocument();
  });

  it('given only empty credit arrays, only shows the authors display', () => {
    // ARRANGE
    const aggregateCredits = {
      achievementsAuthors: [],
      achievementSetArtwork: [],
      achievementsArtwork: [],
      achievementsLogic: [],
      achievementsMaintainers: [],
      achievementsDesign: [],
      achievementsTesting: [],
      achievementsWriting: [],
      hashCompatibilityTesting: [],
    };

    render(<AchievementSetCredits />, { pageProps: { aggregateCredits } });

    // ASSERT
    expect(screen.getByTestId('achievement-authors-display')).toBeVisible();
    expect(screen.queryByTestId('artwork-credits-display')).not.toBeInTheDocument();
    expect(screen.queryByTestId('code-credits-display')).not.toBeInTheDocument();
    expect(screen.queryByTestId('design-credits-display')).not.toBeInTheDocument();
  });

  it('given all credit types have users, shows all displays', () => {
    // ARRANGE
    const aggregateCredits = {
      achievementsAuthors: [createUserCredits({ displayName: 'Author1' })],
      achievementSetArtwork: [createUserCredits({ displayName: 'Artist1' })],
      achievementsArtwork: [],
      achievementsLogic: [createUserCredits({ displayName: 'Logic1' })],
      achievementsMaintainers: [],
      achievementsDesign: [createUserCredits({ displayName: 'Designer1' })],
      achievementsTesting: [],
      achievementsWriting: [],
      hashCompatibilityTesting: [],
    };

    render(<AchievementSetCredits />, { pageProps: { aggregateCredits } });

    // ASSERT
    expect(screen.getByTestId('achievement-authors-display')).toBeVisible();
    expect(screen.getByTestId('artwork-credits-display')).toBeVisible();
    expect(screen.getByTestId('code-credits-display')).toBeVisible();
    expect(screen.getByTestId('design-credits-display')).toBeVisible();
  });

  it('given hash compatibility testing credits exist, shows the design credits display', () => {
    // ARRANGE
    const aggregateCredits = {
      achievementsAuthors: [],
      achievementSetArtwork: [],
      achievementsArtwork: [],
      achievementsLogic: [],
      achievementsMaintainers: [],
      achievementsDesign: [],
      achievementsTesting: [],
      achievementsWriting: [],
      hashCompatibilityTesting: [createUserCredits({ displayName: 'HashTester1' })], // !!
    };

    render(<AchievementSetCredits />, { pageProps: { aggregateCredits } });

    // ASSERT
    expect(screen.getByTestId('design-credits-display')).toBeVisible();
  });
});
