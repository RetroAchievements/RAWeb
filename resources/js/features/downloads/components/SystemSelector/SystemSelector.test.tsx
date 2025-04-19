import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';
import { createDownloadsPageProps } from '@/test/factories';

import { selectedSystemIdAtom } from '../../state/downloads.atoms';
import { SystemSelector } from './SystemSelector';
import * as SystemQueryParamHook from './useSyncSystemQueryParam';

describe('Component: SystemSelector', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<SystemSelector />, {
      pageProps: createDownloadsPageProps(),
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the page loads, displays the All Systems option and top system choices', () => {
    // ARRANGE
    const pageProps = createDownloadsPageProps({
      allSystems: [
        { id: 1, name: 'Nintendo Switch', nameShort: 'Switch', iconUrl: '/path/to/switch.png' },
        { id: 2, name: 'PlayStation 5', nameShort: 'PS5', iconUrl: '/path/to/ps5.png' },
        { id: 3, name: 'Xbox Series X', nameShort: 'Xbox', iconUrl: '/path/to/xbox.png' },
      ],
      topSystemIds: [1, 2, 3],
    });

    render(<SystemSelector />, { pageProps });

    // ASSERT
    expect(screen.getAllByText(/all systems/i)[0]).toBeVisible();
    expect(screen.getAllByText(/switch/i)[0]).toBeVisible();
    expect(screen.getAllByText(/ps5/i)[0]).toBeVisible();
    expect(screen.getAllByText(/xbox/i)[0]).toBeVisible();
  });

  it('given the user selects a system, updates the selected system atom', async () => {
    // ARRANGE
    const pageProps = createDownloadsPageProps({
      allSystems: [
        { id: 1, name: 'Nintendo Switch', nameShort: 'Switch', iconUrl: '/path/to/switch.png' },
      ],
      topSystemIds: [1],
    });

    const syncSystemQueryParamSpy = vi.spyOn(SystemQueryParamHook, 'useSyncSystemQueryParam');

    render(<SystemSelector />, {
      pageProps,
      jotaiAtoms: [
        [selectedSystemIdAtom, undefined],
        //
      ],
    });

    // ACT
    await userEvent.click(screen.getAllByText(/switch/i)[0]);

    // ASSERT
    expect(syncSystemQueryParamSpy).toHaveBeenCalledWith(1);
  });

  it('given the user clicks All Systems after selecting another system, clears the selected system', async () => {
    // ARRANGE
    const pageProps = createDownloadsPageProps({
      allSystems: [
        { id: 1, name: 'Nintendo Switch', nameShort: 'Switch', iconUrl: '/path/to/switch.png' },
      ],
      topSystemIds: [1],
    });

    const syncSystemQueryParamSpy = vi.spyOn(SystemQueryParamHook, 'useSyncSystemQueryParam');

    render(<SystemSelector />, {
      pageProps,
      jotaiAtoms: [
        [selectedSystemIdAtom, 1],
        //
      ],
    });

    // ACT
    await userEvent.click(screen.getAllByText(/all systems/i)[0]);

    // ASSERT
    expect(syncSystemQueryParamSpy).toHaveBeenCalledWith(undefined);
  });

  it('given a desktop viewport, displays the remaining systems section', () => {
    // ARRANGE
    const pageProps = createDownloadsPageProps({
      allSystems: [
        { id: 1, name: 'Nintendo Switch', nameShort: 'Switch', iconUrl: '/path/to/switch.png' },
        { id: 2, name: 'PlayStation 5', nameShort: 'PS5', iconUrl: '/path/to/ps5.png' },
        { id: 3, name: 'Xbox Series X', nameShort: 'Xbox', iconUrl: '/path/to/xbox.png' },
        { id: 4, name: 'PC', nameShort: 'PC', iconUrl: '/path/to/pc.png' },
        { id: 5, name: 'Sega Genesis', nameShort: 'Genesis', iconUrl: '/path/to/genesis.png' },
      ],
      topSystemIds: [1, 2, 3, 4, 5],
    });

    render(<SystemSelector />, { pageProps });

    // ASSERT
    expect(screen.getAllByText(/switch/i)[0]).toBeVisible();
    expect(screen.getAllByText(/ps5/i)[0]).toBeVisible();
    expect(screen.getAllByText(/xbox/i)[0]).toBeVisible();

    expect(screen.getAllByText(/genesis/i)[0]).toBeVisible();
    expect(screen.getAllByText(/pc/i)[0]).toBeVisible();
  });
});
