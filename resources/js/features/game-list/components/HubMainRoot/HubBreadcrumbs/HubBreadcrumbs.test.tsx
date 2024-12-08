import { render, screen } from '@/test';
import { createGameSet } from '@/test/factories';

import { HubBreadcrumbs } from './HubBreadcrumbs';

describe('Component: HubBreadcrumbs', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<HubBreadcrumbs breadcrumbs={[]} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given no breadcrumbs, renders an empty navigation', () => {
    // ARRANGE
    render(<HubBreadcrumbs breadcrumbs={[]} />);

    // ASSERT
    expect(screen.getByRole('navigation')).toBeVisible();
  });

  it('given a breadcrumb, has correct link attributes', () => {
    // ARRANGE
    const breadcrumb = createGameSet({ id: 123, title: '[Central]' });

    render(<HubBreadcrumbs breadcrumbs={[breadcrumb]} />);

    // ASSERT
    const linkEl = screen.getByRole('link', { name: /all hubs/i });

    expect(linkEl).toBeVisible();
  });

  it('given multiple breadcrumbs, renders them in correct order', () => {
    // ARRANGE
    const breadcrumbs = [
      createGameSet({ id: 1, title: '[Central]' }),
      createGameSet({ id: 2, title: '[Central - Series]' }),
      createGameSet({ id: 3, title: '[Series - Sonic the Hedgehog]' }),
    ];

    render(<HubBreadcrumbs breadcrumbs={breadcrumbs} />);

    // ASSERT
    const firstLink = screen.getByRole('link', { name: 'All Hubs' });
    expect(firstLink).toBeVisible();

    const secondLink = screen.getByRole('link', { name: 'Series' });
    expect(secondLink).toBeVisible();

    expect(screen.getByText('Sonic the Hedgehog')).toBeVisible();
  });

  it('given a hub with brackets in title, stylizes them appropriately', () => {
    // ARRANGE
    const breadcrumb = createGameSet({ title: '[Test] Hub Title' });

    render(<HubBreadcrumbs breadcrumbs={[breadcrumb]} />);

    // ASSERT
    expect(screen.queryByText('[')).not.toBeInTheDocument();
    expect(screen.queryByText(']')).not.toBeInTheDocument();

    expect(screen.getByText(/hub title/i)).toBeVisible();
  });

  it('given breadcrumbs with organizational prefixes, maintains proper linking while stripping prefixes', () => {
    // ARRANGE
    const breadcrumbs = [
      createGameSet({ id: 0, title: '[Central - Series]' }),
      createGameSet({ id: 1, title: '[Series - Mega Man]' }),
      createGameSet({ id: 2, title: '[Subseries - Mega Man (Classic)]' }),
    ];

    render(<HubBreadcrumbs breadcrumbs={breadcrumbs} />);

    // ASSERT
    expect(screen.getByText('Series')).toBeVisible();
    expect(screen.getByText('Mega Man')).toBeVisible();
    expect(screen.getByText('Mega Man (Classic)')).toBeVisible();
  });

  it('given breadcrumbs with meta prefixes, maintains proper linking while stripping prefixes', () => {
    // ARRANGE
    const breadcrumbs = [
      createGameSet({ id: 1, title: 'Meta - Testing' }),
      createGameSet({ id: 2, title: 'Meta|DevComp - Projects' }),
    ];

    render(<HubBreadcrumbs breadcrumbs={breadcrumbs} />);

    // ASSERT
    expect(screen.getByText('Testing')).toBeVisible();
    expect(screen.getByText('Projects')).toBeVisible();
  });

  it('handles seen unknown prefixes correctly', () => {
    // ARRANGE
    const breadcrumbs = [
      createGameSet({ id: 1, title: '[Word - Foo - Bar]' }),
      createGameSet({ id: 2, title: '[Word - Foo - Baz]' }),
    ];

    render(<HubBreadcrumbs breadcrumbs={breadcrumbs} />);

    // ASSERT
    expect(screen.getByText('Word - Foo - Bar')).toBeVisible();
    expect(screen.getByText('Foo - Baz')).toBeVisible();
  });
});
