import { faker } from '@faker-js/faker';

import { render, screen } from '@/test';
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
    expect(screen.getByText(hash.name ?? '')).toBeVisible();
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
});
