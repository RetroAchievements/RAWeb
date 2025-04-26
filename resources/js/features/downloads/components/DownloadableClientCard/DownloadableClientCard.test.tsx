import { render, screen } from '@/test';
import { createEmulator, createPlatform, createSystem } from '@/test/factories';

import { DownloadableClientCard } from './DownloadableClientCard';

describe('Component: DownloadableClientCard', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const emulator = createEmulator({
      systems: [createSystem()],
      platforms: [createPlatform()],
    });

    const { container } = render(<DownloadableClientCard emulator={emulator} />, {
      pageProps: { topSystemIds: [] },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given an emulator with more than 8 systems, shows the first 8 and includes a "+X more" tooltip', () => {
    // ARRANGE
    const systems = Array.from({ length: 10 }, (_, i) =>
      createSystem({ id: i + 1, name: `System ${i + 1}`, nameShort: `S${i + 1}` }),
    );
    const emulator = createEmulator({
      systems,
      platforms: [createPlatform()],
    });

    render(<DownloadableClientCard emulator={emulator} />, {
      pageProps: { topSystemIds: [] },
    });

    // ASSERT
    for (let i = 0; i < 8; i++) {
      expect(screen.getByText(systems[i].nameShort as string)).toBeVisible();
    }

    expect(screen.getByText(/\+2 more/i)).toBeVisible();
  });

  it('given an emulator with platforms, displays them in orderColumn order', () => {
    // ARRANGE
    const platforms = [
      createPlatform({ name: 'Second', orderColumn: 1 }),
      createPlatform({ name: 'First', orderColumn: 0 }),
      createPlatform({ name: 'Hidden', orderColumn: -1 }), // !! should be filtered out.
    ];

    const emulator = createEmulator({
      systems: [createSystem()],
      platforms,
    });

    render(<DownloadableClientCard emulator={emulator} />, {
      pageProps: { topSystemIds: [] },
    });

    // ASSERT
    const platformElements = screen.getAllByText(/First|Second|Hidden/);
    expect(platformElements).toHaveLength(2);
    expect(platformElements[0]).toHaveTextContent('First');
    expect(platformElements[1]).toHaveTextContent('Second');
  });

  it('given an emulator with no external links, does not render the links section', () => {
    // ARRANGE
    const emulator = createEmulator({
      systems: [createSystem()],
      platforms: [createPlatform()],
      websiteUrl: '',
      documentationUrl: '',
      sourceUrl: '',
    });

    render(<DownloadableClientCard emulator={emulator} />, {
      pageProps: { topSystemIds: [] },
    });

    // ASSERT
    expect(
      screen.queryByRole('link', { name: /website|docs|source code/i }),
    ).not.toBeInTheDocument();
  });

  it('given topSystemIds are provided, sorts visible systems according to the IDs', () => {
    // ARRANGE
    const systems = [
      createSystem({ id: 3, nameShort: 'Third' }),
      createSystem({ id: 1, nameShort: 'First' }),
      createSystem({ id: 2, nameShort: 'Second' }),
    ];

    const emulator = createEmulator({
      systems,
      platforms: [createPlatform()],
    });

    render(<DownloadableClientCard emulator={emulator} />, {
      pageProps: { topSystemIds: [1, 2, 3] }, // this sets the order
    });

    // ASSERT
    const systemElements = screen.getAllByText(/First|Second|Third/);
    expect(systemElements[0]).toHaveTextContent('First');
    expect(systemElements[1]).toHaveTextContent('Second');
    expect(systemElements[2]).toHaveTextContent('Third');
  });

  it('given an emulator with no systems, returns null', () => {
    // ARRANGE
    const emulator = createEmulator({
      systems: null,
      platforms: [createPlatform()],
    });

    render(<DownloadableClientCard emulator={emulator} />, {
      pageProps: { topSystemIds: [] },
    });

    // ASSERT
    expect(screen.queryByTestId('downloadable-client')).not.toBeInTheDocument();
  });

  it('renders platforms correctly when executionEnvironment is missing', () => {
    // ARRANGE
    const platforms = [
      createPlatform({ name: 'With Environment', executionEnvironment: null }),
      createPlatform({ name: 'Without Environment', executionEnvironment: null }),
    ];

    const emulator = createEmulator({
      systems: [createSystem()],
      platforms,
    });

    render(<DownloadableClientCard emulator={emulator} />, {
      pageProps: { topSystemIds: [] },
    });

    // ASSERT
    expect(screen.getByText('With Environment')).toBeVisible();
    expect(screen.getByText('Without Environment')).toBeVisible();
  });
});
