import {
  selectedPlatformIdAtom,
  selectedSystemIdAtom,
} from '@/features/downloads/state/downloads.atoms';
import { render, screen } from '@/test';

import { AvailableEmulatorsEmptyState } from './AvailableEmulatorsEmptyState';

describe('Component: AvailableEmulatorsEmptyState', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<AvailableEmulatorsEmptyState />, {
      pageProps: {
        allPlatforms: [],
        allSystems: [],
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

  it('given neither platform nor system is selected, shows the generic empty state message', () => {
    // ARRANGE
    render(<AvailableEmulatorsEmptyState />, {
      pageProps: {
        allPlatforms: [],
        allSystems: [],
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

  it('given only platform is selected, shows platform-specific empty state message', () => {
    // ARRANGE
    const platformId = 1;

    render(<AvailableEmulatorsEmptyState />, {
      pageProps: {
        allPlatforms: [{ id: platformId, name: 'Windows' }],
        allSystems: [],
      },
      jotaiAtoms: [
        [selectedPlatformIdAtom, platformId],
        [selectedSystemIdAtom, null],
        //
      ],
    });

    // ASSERT
    expect(screen.getByText(/there aren't any emulators available for windows yet/i)).toBeVisible();
  });

  it('given only system is selected, shows system-specific empty state message', () => {
    // ARRANGE
    const systemId = 2;

    render(<AvailableEmulatorsEmptyState />, {
      pageProps: {
        allPlatforms: [],
        allSystems: [{ id: systemId, name: 'Nintendo 64' }],
      },
      jotaiAtoms: [
        [selectedPlatformIdAtom, null],
        [selectedSystemIdAtom, systemId],
        //
      ],
    });

    // ASSERT
    expect(screen.getByText(/there aren't any nintendo 64 emulators available yet/i)).toBeVisible();
  });

  it('given both platform and system are selected, shows the combined empty state message', () => {
    // ARRANGE
    const platformId = 1;
    const systemId = 2;

    render(<AvailableEmulatorsEmptyState />, {
      pageProps: {
        allPlatforms: [{ id: platformId, name: 'Windows' }],
        allSystems: [{ id: systemId, name: 'Nintendo 64' }],
      },
      jotaiAtoms: [
        [selectedPlatformIdAtom, platformId],
        [selectedSystemIdAtom, systemId],
        //
      ],
    });

    // ASSERT
    expect(
      screen.getByText(/there aren't any nintendo 64 emulators available for windows yet/i),
    ).toBeVisible();
  });

  it('given selected platform is not found in allPlatforms, falls back to appropriate message', () => {
    // ARRANGE
    const platformId = 999; // !! non-existent ID
    const systemId = 2;

    render(<AvailableEmulatorsEmptyState />, {
      pageProps: {
        allPlatforms: [{ id: 1, name: 'Windows' }],
        allSystems: [{ id: systemId, name: 'Nintendo 64' }],
      },
      jotaiAtoms: [
        [selectedPlatformIdAtom, platformId],
        [selectedSystemIdAtom, systemId],
        //
      ],
    });

    // ASSERT
    expect(screen.getByText(/there aren't any nintendo 64 emulators available yet/i)).toBeVisible();
  });

  it('given selected system is not found in allSystems, falls back to appropriate message', () => {
    // ARRANGE
    const platformId = 1;
    const systemId = 999; // !! non-existent ID

    render(<AvailableEmulatorsEmptyState />, {
      pageProps: {
        allPlatforms: [{ id: platformId, name: 'Windows' }],
        allSystems: [{ id: 2, name: 'Nintendo 64' }],
      },
      jotaiAtoms: [
        [selectedPlatformIdAtom, platformId],
        [selectedSystemIdAtom, systemId],
        //
      ],
    });

    // ASSERT
    expect(screen.getByText(/there aren't any emulators available for windows yet/i)).toBeVisible();
  });
});
