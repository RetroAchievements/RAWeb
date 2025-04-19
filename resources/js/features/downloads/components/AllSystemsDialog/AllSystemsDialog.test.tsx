import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';
import { createSystem } from '@/test/factories';

import { isAllSystemsDialogOpenAtom, selectedSystemIdAtom } from '../../state/downloads.atoms';
import { AllSystemsDialog } from './AllSystemsDialog';

describe('Component: AllSystemsDialog', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<AllSystemsDialog />, {
      pageProps: {
        allSystems: [createSystem()],
      },
      jotaiAtoms: [
        [isAllSystemsDialogOpenAtom, true],
        //
      ],
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the dialog is closed, does not display its content', () => {
    // ARRANGE
    render(<AllSystemsDialog />, {
      pageProps: {
        allSystems: [createSystem()],
      },
      jotaiAtoms: [
        [isAllSystemsDialogOpenAtom, false],
        //
      ],
    });

    // ASSERT
    expect(screen.queryByText(/select a gaming system/i)).not.toBeInTheDocument();
  });

  it('given the dialog is open, displays the title and search input', () => {
    // ARRANGE
    render(<AllSystemsDialog />, {
      pageProps: {
        allSystems: [createSystem()],
      },
      jotaiAtoms: [
        [isAllSystemsDialogOpenAtom, true],
        //
      ],
    });

    // ASSERT
    expect(screen.getAllByText(/select a gaming system/i)[0]).toBeVisible();
    expect(screen.getByPlaceholderText(/search for a gaming system/i)).toBeVisible();
  });

  it('given some systems are available, renders them in a grid', () => {
    // ARRANGE
    const systems = [
      createSystem({ id: 1, name: 'Nintendo 64', nameShort: 'N64', iconUrl: '/icon1.png' }),
      createSystem({ id: 2, name: 'PlayStation 2', nameShort: 'PS2', iconUrl: '/icon2.png' }),
    ];

    render(<AllSystemsDialog />, {
      pageProps: {
        allSystems: systems,
      },
      jotaiAtoms: [
        [isAllSystemsDialogOpenAtom, true],
        //
      ],
    });

    // ASSERT
    expect(screen.getByText('Nintendo 64')).toBeVisible();
    expect(screen.getByText('PlayStation 2')).toBeVisible();

    const images = screen.getAllByRole('img');
    expect(images).toHaveLength(2);
    expect(images[0]).toHaveAttribute('src', '/icon1.png');
    expect(images[1]).toHaveAttribute('src', '/icon2.png');
  });

  it('given a system name is too long, renders the short name instead', () => {
    // ARRANGE
    const systems = [
      createSystem({
        id: 1,
        name: 'Super Nintendo Entertainment System',
        nameShort: 'SNES',
        iconUrl: '/icon1.png',
      }),
    ];

    render(<AllSystemsDialog />, {
      pageProps: {
        allSystems: systems,
      },
      jotaiAtoms: [
        [isAllSystemsDialogOpenAtom, true],
        //
      ],
    });

    // ASSERT
    expect(screen.getByText('SNES')).toBeVisible();
    expect(screen.queryByText(/Super Nintendo Entertainment System/i)).not.toBeInTheDocument();
  });

  it('given user enters a search query, filters the systems accordingly', async () => {
    // ARRANGE
    const systems = [
      createSystem({ id: 1, name: 'Nintendo 64', nameShort: 'N64', iconUrl: '/icon1.png' }),
      createSystem({ id: 2, name: 'PlayStation 2', nameShort: 'PS2', iconUrl: '/icon2.png' }),
      createSystem({ id: 3, name: 'Nintendo GameCube', nameShort: 'GC', iconUrl: '/icon3.png' }),
    ];

    render(<AllSystemsDialog />, {
      pageProps: {
        allSystems: systems,
      },
      jotaiAtoms: [
        [isAllSystemsDialogOpenAtom, true],
        //
      ],
    });

    // ACT
    await userEvent.type(screen.getByPlaceholderText(/search for a gaming system/i), 'nintendo');

    // ASSERT
    expect(screen.getByText('Nintendo 64')).toBeVisible();
    expect(screen.getByText('Nintendo GameCube')).toBeVisible();
    expect(screen.queryByText('PlayStation 2')).not.toBeInTheDocument();
  });

  it('given user searches by system ID, filters correctly', async () => {
    // ARRANGE
    const systems = [
      createSystem({ id: 1, name: 'Nintendo 64', nameShort: 'N64', iconUrl: '/icon1.png' }),
      createSystem({ id: 2, name: 'PlayStation 2', nameShort: 'PS2', iconUrl: '/icon2.png' }),
    ];

    render(<AllSystemsDialog />, {
      pageProps: {
        allSystems: systems,
      },
      jotaiAtoms: [
        [isAllSystemsDialogOpenAtom, true],
        //
      ],
    });

    // ACT
    await userEvent.type(screen.getByPlaceholderText(/search for a gaming system/i), '2');

    // ASSERT
    expect(screen.queryByText('Nintendo 64')).not.toBeInTheDocument();
    expect(screen.getByText('PlayStation 2')).toBeVisible();
  });

  it('given user searches by short name, filters correctly', async () => {
    // ARRANGE
    const systems = [
      createSystem({ id: 1, name: 'Nintendo 64', nameShort: 'N64', iconUrl: '/icon1.png' }),
      createSystem({ id: 2, name: 'PlayStation 2', nameShort: 'PS2', iconUrl: '/icon2.png' }),
    ];

    render(<AllSystemsDialog />, {
      pageProps: {
        allSystems: systems,
      },
      jotaiAtoms: [
        [isAllSystemsDialogOpenAtom, true],
        //
      ],
    });

    // ACT
    await userEvent.type(screen.getByPlaceholderText(/search for a gaming system/i), 'ps2');

    // ASSERT
    expect(screen.queryByText('Nintendo 64')).not.toBeInTheDocument();
    expect(screen.getByText('PlayStation 2')).toBeVisible();
  });

  it('given user selects a system, closes the dialog and updates the selected system ID', async () => {
    // ARRANGE
    const systems = [
      createSystem({ id: 1, name: 'Nintendo 64', nameShort: 'N64', iconUrl: '/icon1.png' }),
      createSystem({ id: 2, name: 'PlayStation 2', nameShort: 'PS2', iconUrl: '/icon2.png' }),
    ];

    render(<AllSystemsDialog />, {
      pageProps: {
        allSystems: systems,
      },
      jotaiAtoms: [
        [isAllSystemsDialogOpenAtom, true],
        [selectedSystemIdAtom, null],
        //
      ],
    });

    // ACT
    await userEvent.click(screen.getByText('PlayStation 2'));

    // ASSERT
    expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
  });

  it('given systems are available, sorts them alphabetically by name', () => {
    // ARRANGE
    const systems = [
      createSystem({ id: 2, name: 'PlayStation 2', nameShort: 'PS2', iconUrl: '/icon2.png' }),
      createSystem({ id: 1, name: 'Nintendo 64', nameShort: 'N64', iconUrl: '/icon1.png' }),
      createSystem({ id: 3, name: 'Atari 2600', nameShort: 'A2600', iconUrl: '/icon3.png' }),
    ];

    render(<AllSystemsDialog />, {
      pageProps: {
        allSystems: systems,
      },
      jotaiAtoms: [
        [isAllSystemsDialogOpenAtom, true],
        //
      ],
    });

    // ASSERT
    const buttons = screen
      .getAllByRole('button')
      .filter((button) => button.textContent !== 'select a gaming system');

    expect(buttons[0]).toHaveTextContent('Atari 2600');
    expect(buttons[1]).toHaveTextContent('Nintendo 64');
    expect(buttons[2]).toHaveTextContent('PlayStation 2');
  });
});
