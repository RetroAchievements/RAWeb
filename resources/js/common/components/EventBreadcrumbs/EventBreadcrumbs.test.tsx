import { render, screen } from '@/test';
import { createGame, createGameSet, createRaEvent } from '@/test/factories';
import type { TranslatedString } from '@/types/i18next';

import { EventBreadcrumbs } from './EventBreadcrumbs';

describe('Component: EventBreadcrumbs', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<EventBreadcrumbs event={createRaEvent()} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given no breadcrumbs, falls back to simple All Events link', () => {
    // ARRANGE
    render(<EventBreadcrumbs event={createRaEvent()} />);

    // ASSERT
    const allEventsLinkEl = screen.getByRole('link', { name: /all events/i });
    expect(allEventsLinkEl).toBeVisible();
  });

  it('given an event without breadcrumbs, renders the title label', () => {
    // ARRANGE
    render(
      <EventBreadcrumbs
        event={createRaEvent({ legacyGame: createGame({ title: 'Achievement of the Week 2025' }) })}
      />,
    );

    // ASSERT
    expect(screen.getByText(/achievement of the week 2025/i)).toBeVisible();
  });

  it('given hub breadcrumbs, renders them with the event title at the end', () => {
    // ARRANGE
    const breadcrumbs = [
      createGameSet({ id: 1, title: '[Central]' }),
      createGameSet({ id: 2, title: '[Central - Community Events]' }),
      createGameSet({ id: 3, title: '[Events - RA Roulette (RAWR)]' }),
    ];

    render(
      <EventBreadcrumbs
        breadcrumbs={breadcrumbs}
        event={createRaEvent({ legacyGame: createGame({ title: 'RA Roulette 2025' }) })}
      />,
    );

    // ASSERT
    expect(screen.getByRole('link', { name: 'All Hubs' })).toBeVisible();
    expect(screen.getByRole('link', { name: /community events/i })).toBeVisible();
    expect(screen.getByRole('link', { name: 'RA Roulette (RAWR)' })).toBeVisible();
    expect(screen.getByText('RA Roulette 2025')).toBeVisible();
  });

  it('given hub breadcrumbs, does not render All Events link', () => {
    // ARRANGE
    const breadcrumbs = [
      createGameSet({ id: 1, title: '[Central]' }),
      createGameSet({ id: 2, title: '[Central - Community Events]' }),
    ];

    render(
      <EventBreadcrumbs
        breadcrumbs={breadcrumbs}
        event={createRaEvent({ legacyGame: createGame({ title: 'Test Event' }) })}
      />,
    );

    // ASSERT
    expect(screen.queryByText('All Events')).not.toBeInTheDocument();
  });

  it('strips known organizational prefixes from hub breadcrumbs', () => {
    // ARRANGE
    const breadcrumbs = [
      createGameSet({ id: 1, title: '[Central]' }),
      createGameSet({ id: 2, title: '[Events - RA Roulette (RAWR)]' }),
    ];

    render(
      <EventBreadcrumbs
        breadcrumbs={breadcrumbs}
        event={createRaEvent({ legacyGame: createGame({ title: 'RA Roulette 2025' }) })}
      />,
    );

    // ASSERT
    expect(screen.getByRole('link', { name: 'RA Roulette (RAWR)' })).toBeVisible();
    expect(screen.queryByText(/^Events -/)).not.toBeInTheDocument();
  });

  it('given a t_currentPageLabel, renders event as link with current page at the end', () => {
    // ARRANGE
    const breadcrumbs = [
      createGameSet({ id: 1, title: '[Central]' }),
      createGameSet({ id: 2, title: '[Central - Community Events]' }),
    ];

    render(
      <EventBreadcrumbs
        breadcrumbs={breadcrumbs}
        event={createRaEvent({ id: 123, legacyGame: createGame({ title: 'Test Event' }) })}
        t_currentPageLabel={'Edit' as TranslatedString}
      />,
    );

    // ASSERT
    const eventLink = screen.getByRole('link', { name: /test event/i });
    expect(eventLink).toBeVisible();

    expect(screen.getByText('Edit')).toBeVisible();
  });

  it('given no hub breadcrumbs and t_currentPageLabel, renders fallback breadcrumbs with current page', () => {
    // ARRANGE
    render(
      <EventBreadcrumbs
        event={createRaEvent({ id: 123, legacyGame: createGame({ title: 'Test Event' }) })}
        t_currentPageLabel={'Settings' as TranslatedString}
      />,
    );

    // ASSERT
    expect(screen.getByRole('link', { name: /all events/i })).toBeVisible();
    const eventLink = screen.getByRole('link', { name: /test event/i });
    expect(eventLink).toBeVisible();
    expect(screen.getByText('Settings')).toBeVisible();
  });

  it('cleans hub titles with brackets', () => {
    // ARRANGE
    const breadcrumbs = [createGameSet({ title: '[Test Hub]' })];

    render(
      <EventBreadcrumbs
        breadcrumbs={breadcrumbs}
        event={createRaEvent({ legacyGame: createGame({ title: 'My Event' }) })}
      />,
    );

    // ASSERT
    expect(screen.queryByText('[')).not.toBeInTheDocument();
    expect(screen.queryByText(']')).not.toBeInTheDocument();
    expect(screen.getByText('Test Hub')).toBeVisible();
  });
});
