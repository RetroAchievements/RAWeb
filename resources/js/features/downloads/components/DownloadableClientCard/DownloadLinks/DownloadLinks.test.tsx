import { selectedPlatformIdAtom } from '@/features/downloads/state/downloads.atoms';
import { render, screen } from '@/test';
import { createEmulator, createPlatform } from '@/test/factories';

import { DownloadLinks } from './DownloadLinks';

describe('Component: DownloadLinks', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const emulator = createEmulator({ downloadUrl: 'https://example.com/download' });
    const platforms = [createPlatform({ name: 'Windows' })];

    const { container } = render(<DownloadLinks emulator={emulator} />, {
      pageProps: { allPlatforms: platforms },
      jotaiAtoms: [
        [selectedPlatformIdAtom, platforms[0].id],
        //
      ],
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given an emulator with no download URLs, renders nothing', () => {
    // ARRANGE
    const emulator = createEmulator({ downloadUrl: '', downloadX64Url: '', downloads: [] });
    const platforms = [createPlatform({ name: 'Windows' })];

    render(<DownloadLinks emulator={emulator} />, {
      pageProps: { allPlatforms: platforms },
      jotaiAtoms: [
        [selectedPlatformIdAtom, platforms[0].id],
        //
      ],
    });

    // ASSERT
    expect(screen.queryByRole('link')).not.toBeInTheDocument();
  });

  it('given an emulator with a Windows x64 download URL, renders both download buttons', () => {
    // ARRANGE
    const emulator = createEmulator({
      downloadUrl: 'https://example.com/download',
      downloadX64Url: 'https://example.com/download-x64',
    });
    const platforms = [createPlatform({ name: 'Windows' })];

    render(<DownloadLinks emulator={emulator} />, {
      pageProps: { allPlatforms: platforms },
      jotaiAtoms: [
        [selectedPlatformIdAtom, platforms[0].id],
        //
      ],
    });

    // ASSERT
    expect(screen.getByText(/download \(x64\)/i)).toBeVisible();
    expect(screen.getByText(/^download$/i)).toBeVisible();
  });

  it('given an emulator with a platform-specific download, renders the platform-specific URL', () => {
    // ARRANGE
    const macPlatform = createPlatform({ id: 2, name: 'macOS' });
    const emulator = createEmulator({
      downloadUrl: 'https://example.com/download',
      downloads: [
        { id: 0, label: null, platformId: macPlatform.id, url: 'https://example.com/mac-download' },
      ],
    });
    const platforms = [createPlatform({ name: 'Windows' }), macPlatform];

    render(<DownloadLinks emulator={emulator} />, {
      pageProps: { allPlatforms: platforms },
      jotaiAtoms: [
        [selectedPlatformIdAtom, macPlatform.id],
        //
      ],
    });

    // ASSERT
    const downloadLink = screen.getByText(/download for macos/i);
    expect(downloadLink).toBeVisible();
    expect(downloadLink.closest('a')).toHaveAttribute('href', 'https://example.com/mac-download');
  });

  it('given no platform is selected, defaults to Windows', () => {
    // ARRANGE
    const emulator = createEmulator({
      downloadUrl: 'https://example.com/download',
    });
    const platforms = [createPlatform({ name: 'Windows' })];

    render(<DownloadLinks emulator={emulator} />, {
      pageProps: { allPlatforms: platforms },
      jotaiAtoms: [
        [selectedPlatformIdAtom, null],
        //
      ],
    });

    // ASSERT
    expect(screen.getByText(/^download$/i)).toBeVisible();
  });
});
