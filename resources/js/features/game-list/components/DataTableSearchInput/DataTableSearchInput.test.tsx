import { getCoreRowModel, useReactTable } from '@tanstack/react-table';
import userEvent from '@testing-library/user-event';
import type { FC } from 'react';

import { render, screen } from '@/test';
import { createZiggyProps } from '@/test/factories';

import { DataTableSearchInput } from './DataTableSearchInput';

interface TestHarnessProps {
  hasClearButton?: boolean;
  hasHotkey?: boolean;
}

const TestHarness: FC<TestHarnessProps> = ({ hasClearButton, hasHotkey }) => {
  const table = useReactTable({
    columns: [{ id: 'title' }],
    data: [],
    getCoreRowModel: getCoreRowModel(),
  });

  return (
    <DataTableSearchInput table={table} hasClearButton={hasClearButton} hasHotkey={hasHotkey} />
  );
};

describe('Component: DataTableSearchInput', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<TestHarness />, {
      pageProps: { ziggy: createZiggyProps({ device: 'mobile' }) },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('is accessible', () => {
    // ARRANGE
    render(<TestHarness />, {
      pageProps: { ziggy: createZiggyProps({ device: 'desktop' }) },
    });

    // ASSERT
    expect(screen.getByLabelText(/search games/i)).toBeVisible();
  });

  it('displays placeholder text', () => {
    // ARRANGE
    render(<TestHarness />, {
      pageProps: { ziggy: createZiggyProps({ device: 'desktop' }) },
    });

    // ASSERT
    expect(screen.getByPlaceholderText(/search games.../i)).toBeVisible();
  });

  it('given the global hotkey is not enabled, does not display the hotkey badge', () => {
    // ARRANGE
    render(<TestHarness hasHotkey={false} />, {
      pageProps: { ziggy: createZiggyProps({ device: 'desktop' }) },
    });

    // ASSERT
    expect(screen.queryByText('/')).not.toBeInTheDocument();
  });

  it('given the global hotkey is enabled, displays the hotkey badge', () => {
    // ARRANGE
    render(<TestHarness hasHotkey={true} />, {
      pageProps: { ziggy: createZiggyProps({ device: 'desktop' }) },
    });

    // ASSERT
    expect(screen.getByText('/')).toBeVisible();
  });

  it('given the clear button is enabled and there is user input, displays the clear button', async () => {
    // ARRANGE
    render(<TestHarness hasClearButton={true} />, {
      pageProps: { ziggy: createZiggyProps({ device: 'desktop' }) },
    });

    // ACT
    await userEvent.type(screen.getByPlaceholderText(/search games/i), 'test');

    // ASSERT
    expect(screen.getByLabelText(/clear/i)).toBeVisible();
  });

  it('given the clear button is clicked, clears user input', async () => {
    // ARRANGE
    render(<TestHarness hasClearButton={true} />, {
      pageProps: { ziggy: createZiggyProps({ device: 'desktop' }) },
    });

    // ACT
    await userEvent.type(screen.getByPlaceholderText(/search games/i), 'test');

    await userEvent.click(screen.getByLabelText(/clear/i));

    // ASSERT
    expect(screen.getByPlaceholderText(/search games.../i)).toHaveValue('');
  });

  it('given the "/" key is pressed, focuses the input', async () => {
    // ARRANGE
    render(<TestHarness hasHotkey={true} />, {
      pageProps: { ziggy: createZiggyProps({ device: 'desktop' }) },
    });

    // ACT
    await userEvent.keyboard('/');

    // ASSERT
    const inputEl = screen.getByRole('textbox', { name: /search games/i });

    expect(inputEl).toHaveFocus();
  });
});
