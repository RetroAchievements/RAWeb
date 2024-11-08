import { faker } from '@faker-js/faker';

import { render, screen, within } from '@/test';
import { createGameHash, createGameHashLabel } from '@/test/factories';

import { HashesList, hashesListContainerTestId } from './HashesList';

describe('Component: HashesList', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.GameHashesPageProps>(<HashesList />, {
      pageProps: {
        hashes: [createGameHash()],
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there are no hashes, renders nothing', () => {
    // ARRANGE
    render<App.Platform.Data.GameHashesPageProps>(<HashesList />, {
      pageProps: {
        hashes: [],
      },
    });

    // ASSERT
    expect(screen.queryByTestId(hashesListContainerTestId)).not.toBeInTheDocument();
  });

  it('renders both named and unnamed hashes', () => {
    // ARRANGE
    const hashes = [
      // Named
      createGameHash({
        name: faker.word.words(3),
        labels: [createGameHashLabel({ label: 'foo' })],
      }),
      createGameHash({
        name: faker.word.words(3),
        labels: [createGameHashLabel({ label: 'bar' })],
      }),

      // Unnamed
      createGameHash({ name: null }),
    ];

    render<App.Platform.Data.GameHashesPageProps>(<HashesList />, {
      pageProps: { hashes },
    });

    // ASSERT
    expect(screen.getAllByRole('listitem').length).toEqual(3);
  });

  it('displays the hash name and md5', () => {
    // ARRANGE
    const hash = createGameHash({ name: faker.word.words(3) });

    render<App.Platform.Data.GameHashesPageProps>(<HashesList />, {
      pageProps: { hashes: [hash] },
    });

    // ASSERT
    expect(screen.getByText(hash.name as string)).toBeVisible();
    expect(screen.getByText(hash.md5)).toBeVisible();
  });

  it('given the hash has a patch URL, adds a link to it', () => {
    // ARRANGE
    const hash = createGameHash({ patchUrl: faker.internet.url() });

    render<App.Platform.Data.GameHashesPageProps>(<HashesList />, {
      pageProps: { hashes: [hash] },
    });

    // ASSERT
    const linkEl = screen.getByRole('link', { name: /download patch file/i });
    expect(linkEl).toBeVisible();
    expect(linkEl).toHaveAttribute('href', hash.patchUrl);
  });

  it('given the hash has no patch URL, does not display a download link', () => {
    // ARRANGE
    const hash = createGameHash({ patchUrl: null });

    render<App.Platform.Data.GameHashesPageProps>(<HashesList />, {
      pageProps: { hashes: [hash] },
    });

    // ASSERT
    expect(screen.queryByRole('link', { name: /download patch file/i })).not.toBeInTheDocument();
  });

  it('given the hash has labels with images, renders them', () => {
    // ARRANGE
    const label = createGameHashLabel({ imgSrc: faker.internet.url(), label: 'Redump' });

    const hash = createGameHash({
      labels: [label],
    });

    render<App.Platform.Data.GameHashesPageProps>(<HashesList />, {
      pageProps: { hashes: [hash] },
    });

    // ASSERT
    expect(screen.getByRole('img', { name: /redump/i })).toBeVisible();
  });

  it('given the hash has labels without images, renders them', () => {
    // ARRANGE
    const label = createGameHashLabel({ imgSrc: null, label: 'Redump' });

    const hash = createGameHash({
      labels: [label],
    });

    render<App.Platform.Data.GameHashesPageProps>(<HashesList />, {
      pageProps: { hashes: [hash] },
    });

    // ASSERT
    expect(screen.queryByRole('img', { name: /redump/i })).not.toBeInTheDocument();
    expect(screen.getByText(/redump/i)).toBeVisible();
  });

  it('given there are no named hashes, does not render a named hashes section', () => {
    // ARRANGE
    const namedHashes: App.Platform.Data.GameHash[] = [];
    const unnamedHashes = [createGameHash({ name: null }), createGameHash({ name: null })];

    render<App.Platform.Data.GameHashesPageProps>(<HashesList />, {
      pageProps: { hashes: [...namedHashes, ...unnamedHashes] },
    });

    // ASSERT
    expect(screen.queryByTestId('named-hashes')).not.toBeInTheDocument();
    expect(screen.getByTestId('unnamed-hashes')).toBeVisible();
  });

  it('sorts named hashes alphabetically by name', () => {
    // ARRANGE
    const hashes = [
      createGameHash({ name: 'Zelda, The (USA)' }),
      createGameHash({ name: 'A Link to the Past (USA)' }),
      createGameHash({ name: 'Breath of the Wild (USA)' }),
    ];

    render<App.Platform.Data.GameHashesPageProps>(<HashesList />, {
      pageProps: { hashes },
    });

    // ASSERT
    const namedHashesList = screen.getByTestId('named-hashes');
    const listItems = within(namedHashesList).getAllByRole('listitem');

    const renderedNames = listItems.map((item) => item.textContent);

    expect(renderedNames[0]).toContain('A Link to the Past');
    expect(renderedNames[1]).toContain('Breath of the Wild');
    expect(renderedNames[2]).toContain('Zelda, The');
  });

  it('sorts unnamed hashes alphabetically by MD5', () => {
    // ARRANGE
    const hashes = [
      createGameHash({ name: null, md5: 'a78d58b97eddb7c70647d939e20bef4f' }),
      createGameHash({ name: null, md5: '48e2e4493149fb481852f9ca9e70315f' }),
      createGameHash({ name: null, md5: '77057d9d14b99e465ea9e29783af0ae3' }),
    ];

    render<App.Platform.Data.GameHashesPageProps>(<HashesList />, {
      pageProps: { hashes },
    });

    // ASSERT
    const unnamedHashesList = screen.getByTestId('unnamed-hashes');
    const listItems = within(unnamedHashesList).getAllByRole('listitem');

    const renderedMd5s = listItems.map((item) => item.textContent);

    expect(renderedMd5s[0]).toContain('48e2e4493149fb481852f9ca9e70315f');
    expect(renderedMd5s[1]).toContain('77057d9d14b99e465ea9e29783af0ae3');
    expect(renderedMd5s[2]).toContain('a78d58b97eddb7c70647d939e20bef4f');
  });
});
