import {
  persistedAchievementsAtom,
  persistedGamesAtom,
  persistedTicketsAtom,
  persistedUsersAtom,
} from '@/features/forums/state/forum.atoms';
import { render, screen } from '@/test';
import {
  createAchievement,
  createGame,
  createSystem,
  createTicket,
  createUser,
} from '@/test/factories';

import { ShortcodeRenderer } from './ShortcodeRenderer';

describe('Component: ShortcodeRenderer', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<ShortcodeRenderer body="" />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a body with a URL tag, renders the URL shortcode component', () => {
    // ARRANGE
    const body = '[url]https://example.com[/url]';

    render(<ShortcodeRenderer body={body} />);

    // ASSERT
    expect(screen.getByTestId('url-embed-https://example.com/')).toBeVisible();
  });

  it('given a body with a self-closing URL tag, renders the URL shortcode component', () => {
    // ARRANGE
    const body = '[url=https://example.com]';

    render(<ShortcodeRenderer body={body} />);

    // ASSERT
    expect(screen.getByTestId('url-embed-https://example.com/')).toBeVisible();
  });

  it('given a body with a code tag, renders the code shortcode component', () => {
    // ARRANGE
    const body = '[code]this is some stuff inside code tags[/code]';

    render(<ShortcodeRenderer body={body} />);

    // ASSERT
    const textEl = screen.getByText(/this is some stuff/i);

    expect(textEl).toBeVisible();
    expect(textEl.nodeName).toEqual('SPAN');
    expect(textEl).toHaveClass('codetags');
  });

  it('given a body with a quote tag, renders the quote shortcode component', () => {
    // ARRANGE
    const body = '[quote]this is some stuff inside quote tags[/quote]';

    render(<ShortcodeRenderer body={body} />);

    // ASSERT
    const textEl = screen.getByText(/this is some stuff/i);

    expect(textEl).toBeVisible();
    expect(textEl.nodeName).toEqual('SPAN');
    expect(textEl).toHaveClass('quotedtext');
  });

  it('given a body with a spoiler tag, renders the spoiler shortcode component', () => {
    // ARRANGE
    const body = '[spoiler]this is a spoiler![/spoiler]';

    render(<ShortcodeRenderer body={body} />);

    // ASSERT
    expect(screen.getByRole('button', { name: /spoiler/i })).toBeVisible();
  });

  it('given a body with an img tag, renders the img shortcode component', () => {
    // ARRANGE
    const body = '[img]https://i.imgur.com/ov30jeD.jpg[/img]';

    render(<ShortcodeRenderer body={body} />);

    // ASSERT
    const imgEl = screen.getByRole('img');
    expect(imgEl).toBeVisible();
    expect(imgEl).toHaveAttribute('src', 'https://i.imgur.com/ov30jeD.jpg');
  });

  it('given a body with a user tag and an unknown user, does not render a user embed', () => {
    // ARRANGE
    const body = '[user=Scott]';

    render(<ShortcodeRenderer body={body} />, {
      jotaiAtoms: [[persistedUsersAtom, []]],
    });

    // ASSERT
    expect(screen.queryByTestId('user-embed')).not.toBeInTheDocument();
  });

  it('given a body with a user tag and a found persisted user, renders the user shortcode component', () => {
    // ARRANGE
    const body = '[user=Scott]';

    render(<ShortcodeRenderer body={body} />, {
      jotaiAtoms: [[persistedUsersAtom, [createUser({ displayName: 'Scott' })]]],
    });

    // ASSERT
    expect(screen.getByRole('link', { name: /scott/i })).toBeVisible();
  });

  it('given a body with a game tag and a found persisted game, renders the game shortcode component', () => {
    // ARRANGE
    const system = createSystem({ name: 'Sega Genesis', nameShort: 'MD' });
    const game = createGame({ system, id: 1, title: 'Sonic the Hedgehog' });

    const body = '[game=1]';

    render(<ShortcodeRenderer body={body} />, {
      jotaiAtoms: [[persistedGamesAtom, [game]]],
    });

    // ASSERT
    expect(screen.getByRole('link')).toBeVisible();
    expect(screen.getByRole('img')).toBeVisible();
    expect(screen.getByText('Sonic the Hedgehog (Sega Genesis)')).toBeVisible();
  });

  it('given a body with an achievement tag and a found persisted achievement, renders the ach shortcode component', () => {
    // ARRANGE
    const achievement = createAchievement({ title: 'That Was Easy!', points: 5, id: 9 });

    const body = '[ach=9]';

    render(<ShortcodeRenderer body={body} />, {
      jotaiAtoms: [[persistedAchievementsAtom, [achievement]]],
    });

    // ASSERT
    expect(screen.getByRole('link')).toBeVisible();
    expect(screen.getByRole('img')).toBeVisible();
    expect(screen.getByText('That Was Easy! (5)')).toBeVisible();
  });

  it('given a body with a ticket tag and a found persisted ticket, renders the ticket shortcode component', () => {
    // ARRANGE
    const ticket = createTicket({
      id: 12345,
      ticketable: createAchievement({ title: 'That Was Easy!' }),
    });

    const body = '[ticket=12345]';

    render(<ShortcodeRenderer body={body} />, {
      jotaiAtoms: [[persistedTicketsAtom, [ticket]]],
    });

    // ASSERT
    expect(screen.getByRole('link')).toBeVisible();
    expect(screen.getByRole('img')).toBeVisible();
    expect(screen.getByText('Ticket #12345')).toBeVisible();
  });

  it('given a body with a video tag, displays the video', () => {
    // ARRANGE
    const body = '[video]https://www.youtube.com/watch?v=7oiNxHvNJk0[/video]';

    render(<ShortcodeRenderer body={body} />);

    // ASSERT
    expect(screen.getByTestId('video-embed')).toBeVisible();
  });
});
