import userEvent from '@testing-library/user-event';
import dayjs from 'dayjs';

import { ClaimStatus } from '@/common/utils/generatedAppConstants';
import { render, screen, waitFor } from '@/test';
import {
  createAchievementSetClaim,
  createAggregateAchievementSetCredits,
  createUser,
  createUserCredits,
} from '@/test/factories';

import { MobileCreditDialogTrigger } from './MobileCreditDialogTrigger';

describe('Component: MobileCreditDialogTrigger', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <MobileCreditDialogTrigger
        achievementSetClaims={[]}
        aggregateCredits={createAggregateAchievementSetCredits()}
        artCreditUsers={[]}
        codingCreditUsers={[]}
        designCreditUsers={[]}
      />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given no claims and no credits, displays nothing', () => {
    // ARRANGE
    render(
      <MobileCreditDialogTrigger
        achievementSetClaims={[]}
        aggregateCredits={createAggregateAchievementSetCredits()}
        artCreditUsers={[]}
        codingCreditUsers={[]}
        designCreditUsers={[]}
      />,
    );

    // ASSERT
    expect(screen.queryByRole('button')).not.toBeInTheDocument();
  });

  it('given achievement set claims exist, shows the wrench icon', () => {
    // ARRANGE
    render(
      <MobileCreditDialogTrigger
        achievementSetClaims={[createAchievementSetClaim()]} // !!
        aggregateCredits={createAggregateAchievementSetCredits()}
        artCreditUsers={[]}
        codingCreditUsers={[]}
        designCreditUsers={[]}
      />,
    );

    // ASSERT
    expect(screen.getByRole('button')).toBeVisible();
    expect(screen.getByText(/claimed/i)).toBeVisible(); // !! sr-only
  });

  it('given achievement authors exist and there are less than 5, shows the user avatar stack', () => {
    // ARRANGE
    const aggregateCredits = createAggregateAchievementSetCredits({
      achievementsAuthors: [
        createUserCredits({ displayName: 'Author1' }),
        createUserCredits({ displayName: 'Author2' }),
        createUserCredits({ displayName: 'Author3' }),
      ], // !! 3 authors
    });

    render(
      <MobileCreditDialogTrigger
        achievementSetClaims={[]}
        aggregateCredits={aggregateCredits}
        artCreditUsers={[]}
        codingCreditUsers={[]}
        designCreditUsers={[]}
      />,
    );

    // ASSERT
    expect(screen.getByRole('button')).toBeVisible();
    expect(screen.getAllByRole('img')).toHaveLength(3);
  });

  it('given there are 5 or more achievement authors, does not show the avatar stack', () => {
    // ARRANGE
    const aggregateCredits = createAggregateAchievementSetCredits({
      achievementsAuthors: [
        createUserCredits({ displayName: 'Author1' }),
        createUserCredits({ displayName: 'Author2' }),
        createUserCredits({ displayName: 'Author3' }),
        createUserCredits({ displayName: 'Author4' }),
        createUserCredits({ displayName: 'Author5' }),
      ], // !! 5 authors
    });

    render(
      <MobileCreditDialogTrigger
        achievementSetClaims={[]}
        aggregateCredits={aggregateCredits}
        artCreditUsers={[]}
        codingCreditUsers={[]}
        designCreditUsers={[]}
      />,
    );

    // ASSERT
    expect(screen.queryByRole('img')).not.toBeInTheDocument();
    expect(screen.getByText(/5 authors/i)).toBeVisible();
  });

  it('given a single author, displays the count with proper pluralization', () => {
    // ARRANGE
    const aggregateCredits = createAggregateAchievementSetCredits({
      achievementsAuthors: [createUserCredits({ displayName: 'Author1' })], // !! just 1
    });

    render(
      <MobileCreditDialogTrigger
        achievementSetClaims={[]}
        aggregateCredits={aggregateCredits}
        artCreditUsers={[]}
        codingCreditUsers={[]}
        designCreditUsers={[]}
      />,
    );

    // ASSERT
    expect(screen.getByText('1 author')).toBeVisible();
  });

  it('given non-author contributors exist, shows the contributor count', () => {
    // ARRANGE
    render(
      <MobileCreditDialogTrigger
        achievementSetClaims={[]}
        aggregateCredits={createAggregateAchievementSetCredits()}
        artCreditUsers={[createUserCredits({ displayName: 'Artist1' })]}
        codingCreditUsers={[createUserCredits({ displayName: 'Coder1' })]}
        designCreditUsers={[createUserCredits({ displayName: 'Designer1' })]} // !! 3 unique contributors
      />,
    );

    // ASSERT
    expect(screen.getByText(/\+3 contributors/i)).toBeVisible();
  });

  it('given there are duplicate contributors across categories, deduplicates them in the count', () => {
    // ARRANGE
    const sharedUser = createUserCredits({ displayName: 'MultiTalented' });

    render(
      <MobileCreditDialogTrigger
        achievementSetClaims={[]}
        aggregateCredits={createAggregateAchievementSetCredits()}
        artCreditUsers={[sharedUser]}
        codingCreditUsers={[sharedUser]}
        designCreditUsers={[sharedUser]} // !! same user in all categories
      />,
    );

    // ASSERT
    expect(screen.getByText(/\+1 contributor/i)).toBeVisible();
  });

  it('given there are both claims and authors, shows a separator dot', () => {
    // ARRANGE
    render(
      <MobileCreditDialogTrigger
        achievementSetClaims={[createAchievementSetClaim()]} // !!
        aggregateCredits={createAggregateAchievementSetCredits({
          achievementsAuthors: [createUserCredits({ displayName: 'Author1' })], // !!
        })}
        artCreditUsers={[]}
        codingCreditUsers={[]}
        designCreditUsers={[]}
      />,
    );

    // ASSERT
    const separators = screen.getAllByText('·');
    expect(separators).toHaveLength(1);
  });

  it('given there are both authors and contributors, shows a separator dot', () => {
    // ARRANGE
    render(
      <MobileCreditDialogTrigger
        achievementSetClaims={[]}
        aggregateCredits={createAggregateAchievementSetCredits({
          achievementsAuthors: [createUserCredits({ displayName: 'Author1' })], // !!
        })}
        artCreditUsers={[createUserCredits({ displayName: 'Artist1' })]} // !!
        codingCreditUsers={[]}
        designCreditUsers={[]}
      />,
    );

    // ASSERT
    const separators = screen.getAllByText('·');
    expect(separators).toHaveLength(1);
  });

  it('given the button is clicked, opens the dialog', async () => {
    // ARRANGE
    render(
      <MobileCreditDialogTrigger
        achievementSetClaims={[createAchievementSetClaim()]}
        aggregateCredits={createAggregateAchievementSetCredits()}
        artCreditUsers={[]}
        codingCreditUsers={[]}
        designCreditUsers={[]}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button'));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByRole('dialog')).toBeVisible();
    });
    expect(screen.getByText(/credits/i)).toBeVisible();
    expect(
      screen.getByText(/the following users have contributed to this achievement set/i),
    ).toBeVisible();
  });

  it('given there are active claims, shows the Active Claims section in the dialog', async () => {
    // ARRANGE
    const achievementSetClaims = [
      createAchievementSetClaim({
        user: createUser({ displayName: 'ClaimUser1' }),
      }),
    ];

    render(
      <MobileCreditDialogTrigger
        achievementSetClaims={achievementSetClaims}
        aggregateCredits={createAggregateAchievementSetCredits()}
        artCreditUsers={[]}
        codingCreditUsers={[]}
        designCreditUsers={[]}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button'));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/active claims/i)).toBeVisible();
    });
    expect(screen.getByText(/claimuser1/i)).toBeVisible();
  });

  it('given a future finishedAt date on a claim, shows "Expires" text', async () => {
    // ARRANGE
    const futureDate = dayjs().add(1, 'month').toISOString();
    const achievementSetClaims = [
      createAchievementSetClaim({
        user: createUser({ displayName: 'ClaimUser1' }),
        finishedAt: futureDate, // !!
      }),
    ];

    render(
      <MobileCreditDialogTrigger
        achievementSetClaims={achievementSetClaims}
        aggregateCredits={createAggregateAchievementSetCredits()}
        artCreditUsers={[]}
        codingCreditUsers={[]}
        designCreditUsers={[]}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button'));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/expires/i)).toBeVisible();
    });
  });

  it('given a past finishedAt date on a claim, shows "Expired" text', async () => {
    // ARRANGE
    const pastDate = dayjs().subtract(1, 'month').toISOString();
    const achievementSetClaims = [
      createAchievementSetClaim({
        user: createUser({ displayName: 'ClaimUser1' }),
        finishedAt: pastDate, // !!
      }),
    ];

    render(
      <MobileCreditDialogTrigger
        achievementSetClaims={achievementSetClaims}
        aggregateCredits={createAggregateAchievementSetCredits()}
        artCreditUsers={[]}
        codingCreditUsers={[]}
        designCreditUsers={[]}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button'));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/expired/i)).toBeVisible();
    });
  });

  it('given there is achievement author credit, shows the Achievement Authors section in the dialog', async () => {
    // ARRANGE
    const aggregateCredits = createAggregateAchievementSetCredits({
      achievementsAuthors: [
        createUserCredits({ displayName: 'Author1', count: 10 }),
        createUserCredits({ displayName: 'Author2', count: 5 }),
      ],
    });

    render(
      <MobileCreditDialogTrigger
        achievementSetClaims={[]}
        aggregateCredits={aggregateCredits}
        artCreditUsers={[]}
        codingCreditUsers={[]}
        designCreditUsers={[]}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button'));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/achievement authors/i)).toBeVisible();
    });
    expect(screen.getByText(/author1/i)).toBeVisible();
    expect(screen.getByText(/author2/i)).toBeVisible();
  });

  it('given there is game badge artwork credit, shows the Game Badge Artwork section in the dialog', async () => {
    // ARRANGE
    const badgeArtist = createUserCredits({
      displayName: 'BadgeArtist1',
      dateCredited: '2024-01-15T00:00:00Z',
    });
    const aggregateCredits = createAggregateAchievementSetCredits({
      achievementSetArtwork: [badgeArtist],
    });

    render(
      <MobileCreditDialogTrigger
        achievementSetClaims={[]}
        aggregateCredits={aggregateCredits}
        artCreditUsers={[badgeArtist]}
        codingCreditUsers={[]}
        designCreditUsers={[]}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button'));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/game badge artwork/i)).toBeVisible();
    });
    expect(screen.getByText(/badgeartist1/i)).toBeVisible();
  });

  it('given there is achievement artwork credit, shows the Achievement Artwork section in the dialog', async () => {
    // ARRANGE
    const achArtist = createUserCredits({
      displayName: 'AchievementArtist1',
      count: 25,
    });
    const aggregateCredits = createAggregateAchievementSetCredits({
      achievementsArtwork: [achArtist],
    });

    render(
      <MobileCreditDialogTrigger
        achievementSetClaims={[]}
        aggregateCredits={aggregateCredits}
        artCreditUsers={[achArtist]}
        codingCreditUsers={[]}
        designCreditUsers={[]}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button'));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/achievement artwork/i)).toBeVisible();
    });
    expect(screen.getByText(/achievementartist1/i)).toBeVisible();
  });

  it('given there are achievement maintainers, shows the Achievement Maintainers section in the dialog', async () => {
    // ARRANGE
    const maintainer = createUserCredits({
      displayName: 'Maintainer1',
      dateCredited: '2024-02-01T00:00:00Z',
    });
    const aggregateCredits = createAggregateAchievementSetCredits({
      achievementsMaintainers: [maintainer],
    });

    render(
      <MobileCreditDialogTrigger
        achievementSetClaims={[]}
        aggregateCredits={aggregateCredits}
        artCreditUsers={[]}
        codingCreditUsers={[maintainer]}
        designCreditUsers={[]}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button'));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/achievement maintainers/i)).toBeVisible();
    });
    expect(screen.getByText(/maintainer1/i)).toBeVisible();
  });

  it('given there are design credits, shows the Achievement Design/Ideas section in the dialog', async () => {
    // ARRANGE
    const designer = createUserCredits({
      displayName: 'Designer1',
      count: 7,
    });
    const aggregateCredits = createAggregateAchievementSetCredits({
      achievementsDesign: [designer],
    });

    render(
      <MobileCreditDialogTrigger
        achievementSetClaims={[]}
        aggregateCredits={aggregateCredits}
        artCreditUsers={[]}
        codingCreditUsers={[]}
        designCreditUsers={[designer]}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button'));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/achievement design\/ideas/i)).toBeVisible();
    });
    expect(screen.getByText(/designer1/i)).toBeVisible();
  });

  it('given there are testing credits, shows the Playtesters section in the dialog', async () => {
    // ARRANGE
    const tester = createUserCredits({
      displayName: 'Tester1',
      count: 0,
    });
    const aggregateCredits = createAggregateAchievementSetCredits({
      achievementsTesting: [tester],
    });

    render(
      <MobileCreditDialogTrigger
        achievementSetClaims={[]}
        aggregateCredits={aggregateCredits}
        artCreditUsers={[]}
        codingCreditUsers={[]}
        designCreditUsers={[tester]}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button'));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/playtesters/i)).toBeVisible();
    });
    expect(screen.getByText(/tester1/i)).toBeVisible();
  });

  it('given there are hash compatibility testing credits, shows the Hash Compatibility Testing section in the dialog', async () => {
    // ARRANGE
    const hashTester = createUserCredits({
      displayName: 'HashTester1',
      dateCredited: '2024-03-01T00:00:00Z',
    });
    const aggregateCredits = createAggregateAchievementSetCredits({
      hashCompatibilityTesting: [hashTester],
    });

    render(
      <MobileCreditDialogTrigger
        achievementSetClaims={[]}
        aggregateCredits={aggregateCredits}
        artCreditUsers={[]}
        codingCreditUsers={[]}
        designCreditUsers={[hashTester]}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button'));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/hash compatibility testing/i)).toBeVisible();
    });
    expect(screen.getByText(/hashtester1/i)).toBeVisible();
  });

  it('given there are writing credits, shows the Writing Contributions section in the dialog', async () => {
    // ARRANGE
    const writer = createUserCredits({
      displayName: 'Writer1',
      count: 15,
    });
    const aggregateCredits = createAggregateAchievementSetCredits({
      achievementsWriting: [writer],
    });

    render(
      <MobileCreditDialogTrigger
        achievementSetClaims={[]}
        aggregateCredits={aggregateCredits}
        artCreditUsers={[]}
        codingCreditUsers={[]}
        designCreditUsers={[writer]}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button'));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/writing contributions/i)).toBeVisible();
    });
    expect(screen.getByText(/writer1/i)).toBeVisible();
  });

  it('given there are only authors and no other kinds of credit, only shows authors in the button', () => {
    // ARRANGE
    const aggregateCredits = createAggregateAchievementSetCredits({
      achievementsAuthors: [createUserCredits({ displayName: 'OnlyAuthor' })],
    });

    render(
      <MobileCreditDialogTrigger
        achievementSetClaims={[]}
        aggregateCredits={aggregateCredits}
        artCreditUsers={[]}
        codingCreditUsers={[]}
        designCreditUsers={[]}
      />,
    );

    // ASSERT
    expect(screen.getByText(/1 author/i)).toBeVisible();
    expect(screen.queryByText(/contributor/i)).not.toBeInTheDocument();
  });

  it('given a user is both an author and a contributor, does not count them twice', () => {
    // ARRANGE
    const sharedUser = createUserCredits({ displayName: 'AuthorAndContributor' });
    const aggregateCredits = createAggregateAchievementSetCredits({
      achievementsAuthors: [sharedUser], // !! same user is an author
    });

    render(
      <MobileCreditDialogTrigger
        achievementSetClaims={[]}
        aggregateCredits={aggregateCredits}
        artCreditUsers={[sharedUser]} // !! and also an art contributor
        codingCreditUsers={[]}
        designCreditUsers={[]}
      />,
    );

    // ASSERT
    expect(screen.getByText(/1 author/i)).toBeVisible();
    expect(screen.queryByText(/contributor/i)).not.toBeInTheDocument(); // !! should not be counted as contributor
  });

  it('given a claim is In Review, shows a lock icon instead of a wrench', () => {
    // ARRANGE
    const achievementSetClaims = [
      createAchievementSetClaim({
        user: createUser({ displayName: 'Alice' }),
        status: ClaimStatus.InReview, // !!
      }),
    ];

    render(
      <MobileCreditDialogTrigger
        achievementSetClaims={achievementSetClaims}
        aggregateCredits={createAggregateAchievementSetCredits()}
        artCreditUsers={[]}
        codingCreditUsers={[]}
        designCreditUsers={[]}
      />,
    );

    // ASSERT
    expect(screen.getByTestId('lock-icon')).toBeVisible();
    expect(screen.queryByTestId('wrench-icon')).not.toBeInTheDocument();
  });

  it('given no claims are In Review, shows a wrench icon', () => {
    // ARRANGE
    const achievementSetClaims = [
      createAchievementSetClaim({
        user: createUser({ displayName: 'Alice' }),
        status: ClaimStatus.Active, // !! not In Review
      }),
    ];

    render(
      <MobileCreditDialogTrigger
        achievementSetClaims={achievementSetClaims}
        aggregateCredits={createAggregateAchievementSetCredits()}
        artCreditUsers={[]}
        codingCreditUsers={[]}
        designCreditUsers={[]}
      />,
    );

    // ASSERT
    expect(screen.getByTestId('wrench-icon')).toBeVisible();
    expect(screen.queryByTestId('lock-icon')).not.toBeInTheDocument();
  });

  it('given a claim is In Review, shows "In Review" text in the dialog instead of the expiry date', async () => {
    // ARRANGE
    const achievementSetClaims = [
      createAchievementSetClaim({
        user: createUser({ displayName: 'Alice' }),
        status: ClaimStatus.InReview, // !!
        finishedAt: dayjs().add(1, 'month').toISOString(),
      }),
    ];

    render(
      <MobileCreditDialogTrigger
        achievementSetClaims={achievementSetClaims}
        aggregateCredits={createAggregateAchievementSetCredits()}
        artCreditUsers={[]}
        codingCreditUsers={[]}
        designCreditUsers={[]}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button'));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/in review/i)).toBeVisible();
    });
    expect(screen.queryByText(/expires/i)).not.toBeInTheDocument();
  });
});
