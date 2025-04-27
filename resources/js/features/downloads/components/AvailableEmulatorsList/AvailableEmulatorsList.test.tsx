import { render, screen } from '@/test';
import { createEmulator, createPlatform, createSystem } from '@/test/factories';

import { useVisibleEmulators } from '../../hooks/useVisibleEmulators';
import { selectedPlatformIdAtom, selectedSystemIdAtom } from '../../state/downloads.atoms';
import { AvailableEmulatorsList } from './AvailableEmulatorsList';

vi.mock('../../hooks/useVisibleEmulators', () => ({
  useVisibleEmulators: vi.fn(),
}));

describe('Component: AvailableEmulatorsList', () => {
  it('renders without crashing', () => {
    // ARRANGE
    vi.mocked(useVisibleEmulators).mockReturnValue({
      visibleEmulators: [],
    });

    const { container } = render(<AvailableEmulatorsList />, {
      pageProps: {
        allPlatforms: [createPlatform()],
        allSystems: [createSystem()],
      },
      jotaiAtoms: [
        [selectedPlatformIdAtom, null],
        [selectedSystemIdAtom, null],
        //
      ],
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given no visible emulators are available, renders the empty state', () => {
    // ARRANGE
    vi.mocked(useVisibleEmulators).mockReturnValue({
      visibleEmulators: [],
    });

    render(<AvailableEmulatorsList />, {
      pageProps: {
        allPlatforms: [createPlatform()],
        allSystems: [createSystem()],
      },
      jotaiAtoms: [
        [selectedPlatformIdAtom, null],
        [selectedSystemIdAtom, null],
        //
      ],
    });

    // ASSERT
    expect(screen.getByText(/there aren't any emulators available yet/i)).toBeVisible();
  });

  it('given visible emulators are available, renders a card for each emulator', () => {
    // ARRANGE
    const system = createSystem();
    const platform = createPlatform();

    const mockEmulators = [
      createEmulator({ id: 1, name: 'Project64', systems: [system], platforms: [platform] }),
      createEmulator({ id: 2, name: 'Dolphin', systems: [system], platforms: [platform] }),
      createEmulator({ id: 3, name: 'PCSX2', systems: [system], platforms: [platform] }),
    ];

    vi.mocked(useVisibleEmulators).mockReturnValue({
      visibleEmulators: mockEmulators,
    });

    render(<AvailableEmulatorsList />, {
      pageProps: {
        allPlatforms: [platform],
        allSystems: [system],
      },
    });

    // ASSERT
    expect(screen.getByText('Project64')).toBeVisible();
    expect(screen.getByText('Dolphin')).toBeVisible();
    expect(screen.getByText('PCSX2')).toBeVisible();
  });

  it('given visible emulators are available, does not render the empty state', () => {
    // ARRANGE
    const mockEmulators = [createEmulator({ id: 1, name: 'Project64' })];

    vi.mocked(useVisibleEmulators).mockReturnValue({
      visibleEmulators: mockEmulators,
    });

    render(<AvailableEmulatorsList />, {
      pageProps: {
        allPlatforms: [createPlatform()],
        allSystems: [createSystem()],
      },
    });

    // ASSERT
    expect(screen.queryByText(/there aren't any emulators available yet/i)).not.toBeInTheDocument();
  });
});
