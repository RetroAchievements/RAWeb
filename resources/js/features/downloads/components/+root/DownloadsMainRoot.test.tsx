import { render, screen } from '@/test';
import { createEmulator, createPlatform, createSystem } from '@/test/factories';

import { useVisibleEmulators } from '../../hooks/useVisibleEmulators';
import { DownloadsMainRoot } from './DownloadsMainRoot';

vi.mock('../../hooks/useVisibleEmulators', () => ({
  useVisibleEmulators: vi.fn(),
}));

describe('Component: DownloadsMainRoot', () => {
  it('renders without crashing', () => {
    // ARRANGE
    vi.mocked(useVisibleEmulators).mockReturnValue({
      visibleEmulators: [],
    });

    const { container } = render(<DownloadsMainRoot />, {
      pageProps: {
        allPlatforms: [createPlatform()],
        allSystems: [createSystem()],
        topSystemIds: [],
        can: { manageEmulators: false },
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the page loads, displays the Downloads title', () => {
    // ARRANGE
    vi.mocked(useVisibleEmulators).mockReturnValue({
      visibleEmulators: [],
    });

    render(<DownloadsMainRoot />, {
      pageProps: {
        allPlatforms: [createPlatform()],
        allSystems: [createSystem()],
        topSystemIds: [],
        can: { manageEmulators: false },
      },
    });

    // ASSERT
    expect(screen.getByText(/downloads/i)).toBeVisible();
  });

  it('given the user has permission to manage emulators, shows the manage button', () => {
    // ARRANGE
    vi.mocked(useVisibleEmulators).mockReturnValue({
      visibleEmulators: [],
    });

    render(<DownloadsMainRoot />, {
      pageProps: {
        allPlatforms: [createPlatform()],
        allSystems: [createSystem()],
        topSystemIds: [],
        can: { manageEmulators: true }, // !!
      },
    });

    // ASSERT
    expect(screen.getByRole('link', { name: /manage/i })).toBeVisible();
    expect(screen.getByRole('link', { name: /manage/i })).toHaveAttribute(
      'href',
      '/manage/emulators',
    );
  });

  it('given the user does not have permission to manage emulators, hides the manage button', () => {
    // ARRANGE
    vi.mocked(useVisibleEmulators).mockReturnValue({
      visibleEmulators: [],
    });

    render(<DownloadsMainRoot />, {
      pageProps: {
        allPlatforms: [createPlatform()],
        allSystems: [createSystem()],
        topSystemIds: [],
        can: { manageEmulators: false }, // !!
      },
    });

    // ASSERT
    expect(screen.queryByRole('link', { name: /manage/i })).not.toBeInTheDocument();
  });

  it('given there are visible emulators, shows the count of found emulators', () => {
    // ARRANGE
    const mockEmulators = [
      createEmulator({ id: 1 }),
      createEmulator({ id: 2 }),
      createEmulator({ id: 3 }),
    ];

    vi.mocked(useVisibleEmulators).mockReturnValue({
      visibleEmulators: mockEmulators,
    });

    render(<DownloadsMainRoot />, {
      pageProps: {
        allPlatforms: [createPlatform()],
        allSystems: [createSystem()],
        topSystemIds: [],
        can: { manageEmulators: false },
      },
    });

    // ASSERT
    expect(screen.getByText(/\(3 found\)/i)).toBeVisible();
  });

  it('given there are no visible emulators, shows zero count of found emulators', () => {
    // ARRANGE
    vi.mocked(useVisibleEmulators).mockReturnValue({
      visibleEmulators: [],
    });

    render(<DownloadsMainRoot />, {
      pageProps: {
        allPlatforms: [createPlatform()],
        allSystems: [createSystem()],
        topSystemIds: [],
        can: { manageEmulators: false },
      },
    });

    // ASSERT
    expect(screen.getByText(/\(0 found\)/i)).toBeVisible();
  });

  it('given the page is rendered, shows the Available Emulators heading', () => {
    // ARRANGE
    vi.mocked(useVisibleEmulators).mockReturnValue({
      visibleEmulators: [],
    });

    render(<DownloadsMainRoot />, {
      pageProps: {
        allPlatforms: [createPlatform()],
        allSystems: [createSystem()],
        topSystemIds: [],
        can: { manageEmulators: false },
      },
    });

    // ASSERT
    expect(screen.getByRole('heading', { name: /available emulators/i })).toBeVisible();
  });
});
