import { render, screen } from '@/test';
import { createEmulator } from '@/test/factories';

import { MetadataLinks } from './MetadataLinks';

describe('Component: MetadataLinks', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const emulator = createEmulator({
      websiteUrl: 'https://example.com',
      documentationUrl: 'https://docs.example.com',
      sourceUrl: 'https://github.com/example/example',
    });
    const { container } = render(<MetadataLinks emulator={emulator} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the emulator has no URLs, renders nothing', () => {
    // ARRANGE
    const emulator = createEmulator({
      websiteUrl: null,
      documentationUrl: null,
      sourceUrl: null,
    });
    render(<MetadataLinks emulator={emulator} />);

    // ASSERT
    expect(screen.queryByTestId('metadata')).not.toBeInTheDocument();
  });

  it('given the emulator has only a website URL, renders only the website link', () => {
    // ARRANGE
    const emulator = createEmulator({
      websiteUrl: 'https://example.com',
      documentationUrl: null,
      sourceUrl: null,
    });
    render(<MetadataLinks emulator={emulator} />);

    // ASSERT
    expect(screen.getByText(/website/i)).toBeVisible();
    expect(screen.queryByText(/docs/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/source code/i)).not.toBeInTheDocument();
  });

  it('given the emulator has only a documentation URL, renders only the documentation link', () => {
    // ARRANGE
    const emulator = createEmulator({
      websiteUrl: null,
      documentationUrl: 'https://docs.example.com',
      sourceUrl: null,
    });
    render(<MetadataLinks emulator={emulator} />);

    // ASSERT
    expect(screen.queryByText(/website/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/docs/i)).toBeVisible();
    expect(screen.queryByText(/source/i)).not.toBeInTheDocument();
  });

  it('given the emulator has only a source URL, renders only the source code link', () => {
    // ARRANGE
    const emulator = createEmulator({
      websiteUrl: null,
      documentationUrl: null,
      sourceUrl: 'https://github.com/example/example',
    });
    render(<MetadataLinks emulator={emulator} />);

    // ASSERT
    expect(screen.queryByText(/website/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/docs/i)).not.toBeInTheDocument();
    expect(screen.getByText(/source/i)).toBeVisible();
  });

  it('given the emulator has all URLs, renders all links', () => {
    // ARRANGE
    const emulator = createEmulator({
      websiteUrl: 'https://example.com',
      documentationUrl: 'https://docs.example.com',
      sourceUrl: 'https://github.com/example/example',
    });
    render(<MetadataLinks emulator={emulator} />);

    // ASSERT
    expect(screen.getByText(/website/i)).toBeVisible();
    expect(screen.getByText(/docs/i)).toBeVisible();
    expect(screen.getByText(/source/i)).toBeVisible();
  });
});
