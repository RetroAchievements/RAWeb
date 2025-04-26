import { FaGamepad } from 'react-icons/fa';

import { render, screen } from '@/test';
import { createGameSet } from '@/test/factories';
import type { TranslatedString } from '@/types/i18next';

import { PrimaryMetadataChip } from './PrimaryMetadataChip';

describe('Component: PrimaryMetadataChip', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <PrimaryMetadataChip
        Icon={FaGamepad}
        hubAltLabels={['Alt1', 'Alt2']}
        hubLabel="Test"
        hubs={[]}
        visibleLabel={'Test Label' as TranslatedString}
      />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there is no metadata value and no matching hubs, renders nothing', () => {
    // ARRANGE
    render(
      <PrimaryMetadataChip
        Icon={FaGamepad}
        hubAltLabels={['Alt1', 'Alt2']}
        hubLabel="Test"
        hubs={[]}
        visibleLabel={'Test Label' as TranslatedString}
      />,
    );

    // ASSERT
    expect(screen.queryByTestId('game-meta')).not.toBeInTheDocument();
  });

  it('given there is a metadata value, renders it correctly', () => {
    // ARRANGE
    render(
      <PrimaryMetadataChip
        Icon={FaGamepad}
        hubAltLabels={['Alt1', 'Alt2']}
        hubLabel="Test"
        hubs={[]}
        visibleLabel={'Test Label' as TranslatedString}
        metadataValue="Value 1, Value 2" // !!
      />,
    );

    // ASSERT
    expect(screen.getByText(/value 1/i)).toBeVisible();
    expect(screen.getByText(/value 2/i)).toBeVisible();
  });

  it('given there are matching hubs, renders them as links', () => {
    // ARRANGE
    const hub = createGameSet({
      id: 123,
      title: '[Test - Test Hub]',
    });

    render(
      <PrimaryMetadataChip
        Icon={FaGamepad}
        hubAltLabels={['Alt1', 'Alt2']}
        hubLabel="Test"
        visibleLabel={'Test Label' as TranslatedString}
        hubs={[hub]}
      />,
    );

    // ASSERT
    const link = screen.getByRole('link', { name: /test hub/i });
    expect(link).toBeVisible();
    expect(link).toHaveAttribute('href', expect.stringContaining('hub.show'));
  });

  it('given there are matching alt label hubs, renders them as links', () => {
    // ARRANGE
    const hub = createGameSet({
      id: 123,
      title: '[Alt1 - Alt Hub]',
    });

    render(
      <PrimaryMetadataChip
        Icon={FaGamepad}
        hubAltLabels={['Alt1', 'Alt2']}
        hubLabel="Test"
        visibleLabel={'Test Label' as TranslatedString}
        hubs={[hub]}
      />,
    );

    // ASSERT
    const link = screen.getByRole('link', { name: /alt hub/i });
    expect(link).toBeVisible();
    expect(link).toHaveAttribute('href', expect.stringContaining('hub.show'));
  });

  it('given both metadata values and hubs exist, renders them together with commas', () => {
    // ARRANGE
    const hub = createGameSet({
      id: 123,
      title: '[Test - Test Hub]',
    });

    render(
      <PrimaryMetadataChip
        Icon={FaGamepad}
        hubAltLabels={['Alt1', 'Alt2']}
        hubLabel="Test"
        visibleLabel={'Test Label' as TranslatedString}
        hubs={[hub]}
        metadataValue="Metadata Value"
      />,
    );

    // ASSERT
    expect(screen.getByText(/metadata value/i)).toBeVisible();
    expect(screen.getByText(/,/)).toBeVisible();
    expect(screen.getByRole('link', { name: /test hub/i })).toBeVisible();
  });

  it('given duplicate values between metadata and hubs, prefers the hub version', () => {
    // ARRANGE
    const hub = createGameSet({
      id: 123,
      title: '[Test - Duplicate Value]',
    });

    render(
      <PrimaryMetadataChip
        Icon={FaGamepad}
        hubAltLabels={['Alt1', 'Alt2']}
        hubLabel="Test"
        visibleLabel={'Test Label' as TranslatedString}
        metadataValue="Duplicate Value"
        hubs={[hub]}
      />,
    );

    // ASSERT
    const links = screen.getAllByRole('link', { name: /duplicate value/i });
    expect(links).toHaveLength(1);
    expect(links[0]).toHaveAttribute('href', expect.stringContaining('hub.show'));
  });

  it('given a hack hub, formats the label correctly', () => {
    // ARRANGE
    const hub = createGameSet({
      id: 123,
      title: '[Hack - Test Hacks - Something]',
    });

    render(
      <PrimaryMetadataChip
        Icon={FaGamepad}
        hubAltLabels={['Alt1', 'Alt2']}
        visibleLabel={'Test Label' as TranslatedString}
        hubLabel="Hack"
        hubs={[hub]}
      />,
    );

    // ASSERT
    expect(screen.getByRole('link', { name: /test hack - something/i })).toBeVisible();
  });
});
