import userEvent from '@testing-library/user-event';

import { render, screen, waitFor } from '@/test';
import { createGame, createGameHash } from '@/test/factories';

import { HashCheckerSection } from './HashCheckerSection';

const computeHashMock = vi.hoisted(() => vi.fn());

vi.mock('@/common/hooks/useRcheevos', () => ({
  useRcheevos: () => ({
    current: {
      computeHash: computeHashMock,
    },
  }),
}));

describe('Component: HashCheckerSection', () => {
  beforeEach(() => {
    computeHashMock.mockReset();
    computeHashMock.mockReturnValue('abc123');
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.GameHashesPageProps>(
      <HashCheckerSection systemID={5} />,
      {
        pageProps: {
          can: { manageGameHashes: false },
          game: createGame(),
          hashes: [createGameHash({ md5: 'abc123' })],
        },
      },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('computes the hash for an uploaded file and shows a success indicator when it matches', async () => {
    // ARRANGE
    render<App.Platform.Data.GameHashesPageProps>(<HashCheckerSection systemID={5} />, {
      pageProps: {
        can: { manageGameHashes: false },
        game: createGame(),
        hashes: [createGameHash({ md5: 'abc123' })],
      },
    });

    const fileInput = screen.getByTestId('hash-file-input') as HTMLInputElement;
    const file = new File(['dummy-content'], 'rom.bin', {
      type: 'application/octet-stream',
    });

    // ACT
    await userEvent.upload(fileInput, file);

    // ASSERT
    await waitFor(() => {
      expect(computeHashMock).toHaveBeenCalledTimes(1);
    });

    expect(computeHashMock).toHaveBeenCalledWith(
      5,
      new TextEncoder().encode('dummy-content').buffer,
    );
    expect(screen.getByText(/got hash:/i)).toBeVisible();
    expect(screen.getByText('abc123')).toBeVisible();
    expect(screen.getByText('✅')).toBeVisible();
  });

  it('shows a failure indicator when the computed hash is not recognized', async () => {
    // ARRANGE
    computeHashMock.mockReturnValue('deadbeef');

    render<App.Platform.Data.GameHashesPageProps>(<HashCheckerSection systemID={3} />, {
      pageProps: {
        can: { manageGameHashes: false },
        game: createGame(),
        hashes: [createGameHash({ md5: 'abc123' })],
      },
    });

    const fileInput = screen.getByTestId('hash-file-input') as HTMLInputElement;
    const file = new File(['other-content'], 'rom2.bin', {
      type: 'application/octet-stream',
    });

    // ACT
    await userEvent.upload(fileInput, file);

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText('deadbeef')).toBeVisible();
    });

    expect(computeHashMock).toHaveBeenCalledWith(
      3,
      new TextEncoder().encode('other-content').buffer,
    );
    expect(screen.getByText('❌')).toBeVisible();
  });
});
