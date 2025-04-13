import userEvent from '@testing-library/user-event';
import type { FC, ReactNode } from 'react';
import { FormProvider, useForm } from 'react-hook-form';

import { render, screen } from '@/test';
import { createEmulator } from '@/test/factories';

import { EmulatorSelectField } from './EmulatorSelectField';

const Wrapper: FC<{ children: ReactNode }> = ({ children }) => {
  const form = useForm();

  return <FormProvider {...form}>{children}</FormProvider>;
};

describe('Component: EmulatorSelectField', () => {
  beforeEach(() => {
    window.HTMLElement.prototype.hasPointerCapture = vi.fn();
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <Wrapper>
        <EmulatorSelectField />
      </Wrapper>,
      { pageProps: { emulators: [] } },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('sorts emulators alphabetically with "Other" emulators at the end', async () => {
    // ARRANGE
    const emulators = [
      createEmulator({ name: 'RASNES' }),
      createEmulator({ name: 'Other (please specify)' }),
      createEmulator({ name: 'RetroArch' }),
      createEmulator({ name: 'RAlibretro' }),
      createEmulator({ name: 'Bizhawk' }),
    ];

    render(
      <Wrapper>
        <EmulatorSelectField />
      </Wrapper>,
      { pageProps: { emulators } },
    );

    // ACT
    await userEvent.click(screen.getByRole('combobox'));

    // ASSERT
    const options = screen.getAllByRole('option');
    const optionTexts = options.map((option) => option.textContent);

    expect(optionTexts).toEqual([
      'Bizhawk',
      'RAlibretro',
      'RASNES',
      'RetroArch',
      'Other (please specify)',
    ]);
  });

  it('given the user selects an emulator that does not support the toolkit, pops a warning message', async () => {
    // ARRANGE
    const emulators = [
      createEmulator({ name: 'RA2SNES', canDebugTriggers: false }), // !!
      createEmulator({ name: 'RetroArch' }),
    ];

    render(
      <Wrapper>
        <EmulatorSelectField />
      </Wrapper>,
      { pageProps: { emulators } },
    );

    // ACT
    await userEvent.click(screen.getByRole('combobox'));
    await userEvent.click(screen.getByText(/ra2snes/i));

    // ASSERT
    expect(screen.getByText(/developers may not be able to easily debug issues/i)).toBeVisible();
  });
});
