import userEvent from '@testing-library/user-event';

import { selectedSystemIdAtom } from '@/features/downloads/state/downloads.atoms';
import { render, screen } from '@/test';
import { createSystem } from '@/test/factories';

import { AllSystemsDialog } from '../../AllSystemsDialog';
import { BrowseRemainingSystems } from './BrowseRemainingSystems';

describe('Component: BrowseRemainingSystems', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<BrowseRemainingSystems visibleSystemIds={[]} />, {
      pageProps: { allSystems: [] },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there are no systems at all, does not crash', () => {
    // ARRANGE
    const { container } = render(<BrowseRemainingSystems visibleSystemIds={[]} />, {
      pageProps: {},
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there is a selected system, displays that system', () => {
    // ARRANGE
    const system = createSystem({ id: 1, nameShort: 'MD' });

    render(<BrowseRemainingSystems visibleSystemIds={[]} />, {
      pageProps: { allSystems: [system] },
      jotaiAtoms: [
        [selectedSystemIdAtom, system.id],
        //
      ],
    });

    // ASSERT
    expect(screen.getByText('MD')).toBeVisible();
  });

  it('given the user clicks the button, opens the all systems dialog', async () => {
    // ARRANGE
    const system = createSystem({ id: 1, nameShort: 'MD' });

    render(
      <>
        <BrowseRemainingSystems visibleSystemIds={[]} />
        <AllSystemsDialog />
      </>,
      {
        pageProps: { allSystems: [system] },
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /browse all/i }));

    // ASSERT
    expect(screen.getByRole('dialog', { name: /select a gaming system/i })).toBeVisible();
  });
});
