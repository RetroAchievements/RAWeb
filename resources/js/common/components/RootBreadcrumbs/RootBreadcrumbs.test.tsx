import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';
import { createSystem } from '@/test/factories';

import { RootBreadcrumbs } from './RootBreadcrumbs';

describe('Component: RootBreadcrumbs', () => {
  it('given it is not collapsed, links the All Games list and the system games list as crumbs', () => {
    // ARRANGE
    render(
      <RootBreadcrumbs isCollapsed={false} system={createSystem({ id: 2, name: 'Nintendo 64' })} />,
    );

    // ASSERT
    expect(screen.getByRole('link', { name: 'All Games' })).toHaveAttribute('href', 'game.index');
    expect(screen.getByRole('link', { name: 'Nintendo 64' })).toHaveAttribute(
      'href',
      'system.game.index,2',
    );
    expect(screen.queryByRole('button', { name: 'More' })).not.toBeInTheDocument();
  });

  it('given it is collapsed, hides the root crumbs until the menu is opened', () => {
    // ARRANGE
    render(<RootBreadcrumbs isCollapsed={true} system={createSystem({ name: 'Nintendo 64' })} />);

    // ASSERT
    expect(screen.getByRole('button', { name: 'More' })).toBeVisible();
    expect(screen.queryByRole('link', { name: 'All Games' })).not.toBeInTheDocument();
    expect(screen.queryByRole('link', { name: 'Nintendo 64' })).not.toBeInTheDocument();
  });

  it('given it is collapsed with a system, the menu links to the All Games list and the system games list', async () => {
    // ARRANGE
    render(
      <RootBreadcrumbs isCollapsed={true} system={createSystem({ id: 2, name: 'Nintendo 64' })} />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'More' }));

    // ASSERT
    expect(screen.getByRole('menuitem', { name: 'All Games' })).toHaveAttribute(
      'href',
      'game.index',
    );
    expect(screen.getByRole('menuitem', { name: 'Nintendo 64' })).toHaveAttribute(
      'href',
      'system.game.index,2',
    );
  });

  it('given it is collapsed with no system, the menu only links to the All Games list', async () => {
    // ARRANGE
    render(<RootBreadcrumbs isCollapsed={true} />);

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'More' }));

    // ASSERT
    expect(screen.getAllByRole('menuitem')).toHaveLength(1);
    expect(screen.getByRole('menuitem', { name: 'All Games' })).toBeVisible();
  });
});
