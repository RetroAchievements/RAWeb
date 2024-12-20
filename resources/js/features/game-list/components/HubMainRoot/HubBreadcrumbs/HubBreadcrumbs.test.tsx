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

  it('strips duplicate prefixes between parent and child', () => {
    // ARRANGE
    const breadcrumbs = [
      createGameSet({ id: 1, title: 'Central - Credits' }),
      createGameSet({ id: 2, title: 'Credits - Hideo Kojima' }),
    ];

    render(<HubBreadcrumbs breadcrumbs={breadcrumbs} />);

    // ASSERT
    expect(screen.getByText(/credits/i)).toBeVisible();
    expect(screen.getByText(/hideo kojima/i)).toBeVisible();

    expect(screen.queryByText('Credits - Hideo Kojima')).not.toBeInTheDocument();
  });

  it('handles multiple levels of parent-child relationships correctly', () => {
    // ARRANGE
    const breadcrumbs = [
      createGameSet({ id: 1, title: 'Central - Games' }),
      createGameSet({ id: 2, title: 'Games - RPG' }),
      createGameSet({ id: 3, title: 'RPG - Final Fantasy' }),
    ];

    render(<HubBreadcrumbs breadcrumbs={breadcrumbs} />);

    // ASSERT
    expect(screen.getByText(/games/i)).toBeVisible();

    expect(screen.getByText('RPG')).toBeVisible();
    expect(screen.getByText('Final Fantasy')).toBeVisible();
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

  it('handles multiple dash separators correctly', () => {
    // ARRANGE
    const breadcrumbs = [
      createGameSet({ id: 1, title: 'Central - Series' }),
      createGameSet({ id: 2, title: 'Central - Series - Nintendo' }),
      createGameSet({ id: 3, title: 'Nintendo - Mario - Main Series' }),
    ];

    render(<HubBreadcrumbs breadcrumbs={breadcrumbs} />);

    // ASSERT
    expect(screen.getByText('Series - Nintendo')).toBeVisible();
    expect(screen.getByText('Mario - Main Series')).toBeVisible();
  });

  it('strips known organizational prefixes', () => {
    // ARRANGE
    const breadcrumbs = [
      createGameSet({ id: 1, title: 'ASB - Testing' }),
      createGameSet({ id: 2, title: 'Meta|QA - Projects' }),
      createGameSet({ id: 3, title: 'Subgenre - Action' }),
    ];

    render(<HubBreadcrumbs breadcrumbs={breadcrumbs} />);

    // ASSERT
    expect(screen.getByText('Testing')).toBeVisible();
    expect(screen.getByText('Projects')).toBeVisible();
    expect(screen.getByText('Action')).toBeVisible();

    expect(screen.queryByText(/asb/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/meta\|qa/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/subgenre/i)).not.toBeInTheDocument();
  });

  it('formats DevQuest Sets titles correctly', () => {
    // ARRANGE
    const breadcrumbs = [
      createGameSet({ title: '[Central]' }),
      createGameSet({ title: '[Central - Developer Events]' }),
      createGameSet({ title: '[Dev Events - DevQuest]' }),
      createGameSet({ title: '[DevQuest 021 Sets] Homebrew Heaven' }),
    ];

    render(<HubBreadcrumbs breadcrumbs={breadcrumbs} />);

    // ASSERT
    expect(screen.getByText(/all hubs/i)).toBeVisible();
    expect(screen.getByText(/developer events/i)).toBeVisible();
    expect(screen.getByText(/devquest/i)).toBeVisible();
    expect(screen.getByText('21: Homebrew Heaven')).toBeVisible();

    expect(screen.queryByText(/devquest sets/i)).not.toBeInTheDocument();
  });

  it('given a malformed DevQuest title, handles it gracefully', () => {
    // ARRANGE
    const breadcrumbs = [
      createGameSet({ title: '[DevQuest Sets]' }),
      createGameSet({ title: '[DevQuest foo Sets] After Bracket' }),
    ];

    render(<HubBreadcrumbs breadcrumbs={breadcrumbs} />);

    // ASSERT
    expect(screen.getByText('DevQuest Sets')).toBeVisible();
    expect(screen.getByText('After Bracket')).toBeVisible();
  });
});
