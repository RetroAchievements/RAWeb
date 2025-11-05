import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';
import axios from 'axios';
import { route } from 'ziggy-js';

import { ClaimStatus, ClaimType } from '@/common/utils/generatedAppConstants';
import { render, screen, waitFor } from '@/test';
import { createAchievementSetClaim, createGame } from '@/test/factories';

import { SidebarToggleInReviewButton } from './SidebarToggleInReviewButton';

describe('Component: SidebarToggleInReviewButton', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<SidebarToggleInReviewButton />, {
      pageProps: {
        achievementSetClaims: [
          createAchievementSetClaim({
            claimType: ClaimType.Primary,
            status: ClaimStatus.Active,
          }),
        ],
        backingGame: createGame(),
        can: { reviewAchievementSetClaims: true },
        game: createGame(),
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user cannot review achievement set claims, does not render anything', () => {
    // ARRANGE
    render(<SidebarToggleInReviewButton />, {
      pageProps: {
        achievementSetClaims: [
          createAchievementSetClaim({
            claimType: ClaimType.Primary,
            status: ClaimStatus.Active,
          }),
        ],
        backingGame: createGame(),
        can: { reviewAchievementSetClaims: false }, // !!
        game: createGame(),
      },
    });

    // ASSERT
    expect(screen.queryByRole('button')).not.toBeInTheDocument();
  });

  it('given there is no primary claim, does not render anything', () => {
    // ARRANGE
    render(<SidebarToggleInReviewButton />, {
      pageProps: {
        achievementSetClaims: [
          createAchievementSetClaim({
            claimType: ClaimType.Collaboration,
            status: ClaimStatus.Active,
          }),
        ],
        backingGame: createGame(),
        can: { reviewAchievementSetClaims: true }, // !!
        game: createGame(),
      },
    });

    // ASSERT
    expect(screen.queryByRole('button')).not.toBeInTheDocument();
  });

  it('given the primary claim is neither active nor in review, does not render anything', () => {
    // ARRANGE
    render(<SidebarToggleInReviewButton />, {
      pageProps: {
        achievementSetClaims: [
          createAchievementSetClaim({
            claimType: ClaimType.Primary,
            status: ClaimStatus.Complete,
          }),
        ],
        backingGame: createGame(),
        can: { reviewAchievementSetClaims: true },
        game: createGame(),
      },
    });

    // ASSERT
    expect(screen.queryByRole('button')).not.toBeInTheDocument();
  });

  it('given the primary claim is active, shows the mark claim for review button', () => {
    // ARRANGE
    render(<SidebarToggleInReviewButton />, {
      pageProps: {
        achievementSetClaims: [
          createAchievementSetClaim({
            claimType: ClaimType.Primary,
            status: ClaimStatus.Active,
          }),
        ],
        backingGame: createGame(),
        can: { reviewAchievementSetClaims: true },
        game: createGame(),
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /mark claim for review/i })).toBeVisible();
  });

  it('given the primary claim is in review, shows the complete claim review button', () => {
    // ARRANGE
    render(<SidebarToggleInReviewButton />, {
      pageProps: {
        achievementSetClaims: [
          createAchievementSetClaim({
            claimType: ClaimType.Primary,
            status: ClaimStatus.InReview,
          }),
        ],
        backingGame: createGame(),
        can: { reviewAchievementSetClaims: true },
        game: createGame(),
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /complete claim review/i })).toBeVisible();
  });

  it('given the game is a subset, shows the subset indicator', () => {
    // ARRANGE
    render(<SidebarToggleInReviewButton />, {
      pageProps: {
        achievementSetClaims: [
          createAchievementSetClaim({
            claimType: ClaimType.Primary,
            status: ClaimStatus.Active,
          }),
        ],
        backingGame: createGame({ id: 999 }),
        can: { reviewAchievementSetClaims: true },
        game: createGame({ id: 1 }),
      },
    });

    // ASSERT
    expect(screen.getByRole('img', { name: /subset/i })).toBeVisible();
  });

  it('given the button is clicked when claim is active, opens the dialog with correct content', async () => {
    // ARRANGE
    render(<SidebarToggleInReviewButton />, {
      pageProps: {
        achievementSetClaims: [
          createAchievementSetClaim({
            claimType: ClaimType.Primary,
            status: ClaimStatus.Active,
          }),
        ],
        backingGame: createGame(),
        can: { reviewAchievementSetClaims: true },
        game: createGame({ title: 'Test Game' }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /mark claim for review/i }));

    // ASSERT
    expect(screen.getByText(/are you sure\?/i)).toBeVisible();
    expect(screen.getByText(/this will mark the active claim/i)).toBeVisible();
    expect(screen.getByText('Test Game')).toBeVisible();
    expect(screen.getByRole('button', { name: /yes, begin the review/i })).toBeVisible();
  });

  it('given the button is clicked when claim is in review, opens the dialog with correct content', async () => {
    // ARRANGE
    render(<SidebarToggleInReviewButton />, {
      pageProps: {
        achievementSetClaims: [
          createAchievementSetClaim({
            claimType: ClaimType.Primary,
            status: ClaimStatus.InReview,
          }),
        ],
        backingGame: createGame(),
        can: { reviewAchievementSetClaims: true },
        game: createGame({ title: 'Test Game' }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /complete claim review/i }));

    // ASSERT
    expect(screen.getByText(/are you sure\?/i)).toBeVisible();
    expect(screen.getByText(/this will complete the review/i)).toBeVisible();
    expect(screen.getByText('Test Game')).toBeVisible();
    expect(screen.getByRole('button', { name: /yes, complete the review/i })).toBeVisible();
  });

  it('given the nevermind button is clicked, closes the dialog', async () => {
    // ARRANGE
    render(<SidebarToggleInReviewButton />, {
      pageProps: {
        achievementSetClaims: [
          createAchievementSetClaim({
            claimType: ClaimType.Primary,
            status: ClaimStatus.Active,
          }),
        ],
        backingGame: createGame(),
        can: { reviewAchievementSetClaims: true },
        game: createGame(),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /mark claim for review/i }));
    await userEvent.click(screen.getByRole('button', { name: /nevermind/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.queryByText(/are you sure\?/i)).not.toBeInTheDocument();
    });
  });

  it('given the confirm button is clicked to mark for review, makes the correct API call and closes the dialog', async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ data: { success: true } });
    vi.spyOn(router, 'reload').mockImplementationOnce(vi.fn());
    const primaryClaim = createAchievementSetClaim({
      id: 123,
      claimType: ClaimType.Primary,
      status: ClaimStatus.Active,
    });

    render(<SidebarToggleInReviewButton />, {
      pageProps: {
        achievementSetClaims: [primaryClaim],
        backingGame: createGame(),
        can: { reviewAchievementSetClaims: true },
        game: createGame(),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /mark claim for review/i }));
    await userEvent.click(screen.getByRole('button', { name: /yes, begin the review/i }));

    // ASSERT
    await waitFor(() => {
      expect(postSpy).toHaveBeenCalledOnce();
    });

    const [url, formData] = postSpy.mock.calls[0];
    expect(url).toEqual(route('achievement-set-claim.update', { claim: 123 }));
    expect(formData).toBeInstanceOf(FormData);
    expect((formData as any).get('status')).toEqual(ClaimStatus.InReview.toString());

    await waitFor(() => {
      expect(screen.queryByText(/are you sure\?/i)).not.toBeInTheDocument();
    });
  });

  it('given the confirm button is clicked to complete review, makes the correct API call and closes the dialog', async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ data: { success: true } });
    vi.spyOn(router, 'reload').mockImplementationOnce(vi.fn());
    const primaryClaim = createAchievementSetClaim({
      id: 456,
      claimType: ClaimType.Primary,
      status: ClaimStatus.InReview,
    });

    render(<SidebarToggleInReviewButton />, {
      pageProps: {
        achievementSetClaims: [primaryClaim],
        backingGame: createGame(),
        can: { reviewAchievementSetClaims: true },
        game: createGame(),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /complete claim review/i }));
    await userEvent.click(screen.getByRole('button', { name: /yes, complete the review/i }));

    // ASSERT
    await waitFor(() => {
      expect(postSpy).toHaveBeenCalledOnce();
    });

    const [url, formData] = postSpy.mock.calls[0];
    expect(url).toEqual(route('achievement-set-claim.update', { claim: 456 }));
    expect(formData).toBeInstanceOf(FormData);
    expect((formData as any).get('status')).toEqual(ClaimStatus.Active.toString());

    await waitFor(() => {
      expect(screen.queryByText(/are you sure\?/i)).not.toBeInTheDocument();
    });
  });
});
