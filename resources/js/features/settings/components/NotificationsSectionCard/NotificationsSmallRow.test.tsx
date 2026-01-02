import userEvent from '@testing-library/user-event';
import type { FC, ReactNode } from 'react';
import { FormProvider, useForm } from 'react-hook-form';
import { vi } from 'vitest';

import i18n from '@/i18n-client';
import { render, screen } from '@/test';

import { NotificationsSmallRow } from './NotificationsSmallRow';

interface WrapperProps {
  children: ReactNode;
  defaultValues?: Record<string, boolean>;
}

const Wrapper: FC<WrapperProps> = ({ children, defaultValues }) => {
  const form = useForm({ defaultValues });

  return <FormProvider {...form}>{children}</FormProvider>;
};

describe('Component: NotificationsSmallRow', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <Wrapper>
        <NotificationsSmallRow t_label={i18n.t('Achievements')} emailFieldName="0" />
      </Wrapper>,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there is no email field name, does not render an email me checkbox', () => {
    // ARRANGE
    render(
      <Wrapper>
        <NotificationsSmallRow t_label={i18n.t('Achievements')} />
      </Wrapper>,
    );

    // ASSERT
    expect(screen.queryByText(/email me/i)).not.toBeInTheDocument();
  });

  it('given there is an email field name, renders an email me checkbox', () => {
    // ARRANGE
    render(
      <Wrapper>
        <NotificationsSmallRow t_label={i18n.t('Achievements')} emailFieldName="1" />
      </Wrapper>,
    );

    // ASSERT
    expect(screen.getByText(/email me/i)).toBeVisible();
  });

  it('given isInverted is true and the form value is true, displays the checkbox as unchecked', () => {
    // ARRANGE
    render(
      <Wrapper defaultValues={{ testField: true }}>
        <NotificationsSmallRow
          t_label={i18n.t('Achievements')}
          emailFieldName={'testField' as any}
          isInverted={true} // !!
        />
      </Wrapper>,
    );

    // ASSERT
    expect(screen.getByRole('checkbox')).not.toBeChecked();
  });

  it('given isInverted is true and the form value is false, displays the checkbox as checked', () => {
    // ARRANGE
    render(
      <Wrapper defaultValues={{ testField: false }}>
        <NotificationsSmallRow
          t_label={i18n.t('Achievements')}
          emailFieldName={'testField' as any}
          isInverted={true} // !!
        />
      </Wrapper>,
    );

    // ASSERT
    expect(screen.getByRole('checkbox')).toBeChecked();
  });

  it('given isInverted is true and the user checks the checkbox, stores false in the form', async () => {
    // ARRANGE
    const onSubmit = vi.fn();

    const TestWrapper: FC = () => {
      const form = useForm({ defaultValues: { testField: true } });

      return (
        <FormProvider {...form}>
          <form onSubmit={form.handleSubmit(onSubmit)}>
            <NotificationsSmallRow
              t_label={i18n.t('Achievements')}
              emailFieldName={'testField' as any}
              isInverted
            />
            <button type="submit">Submit</button>
          </form>
        </FormProvider>
      );
    };

    render(<TestWrapper />);

    // ACT
    await userEvent.click(screen.getByRole('checkbox'));
    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    expect(onSubmit).toHaveBeenCalledWith(
      expect.objectContaining({ testField: false }),
      expect.anything(),
    );
  });

  it('given isInverted is true and the user unchecks the checkbox, stores true in the form', async () => {
    // ARRANGE
    const onSubmit = vi.fn();

    const TestWrapper: FC = () => {
      const form = useForm({ defaultValues: { testField: false } });

      return (
        <FormProvider {...form}>
          <form onSubmit={form.handleSubmit(onSubmit)}>
            <NotificationsSmallRow
              t_label={i18n.t('Achievements')}
              emailFieldName={'testField' as any}
              isInverted
            />
            <button type="submit">Submit</button>
          </form>
        </FormProvider>
      );
    };

    render(<TestWrapper />);

    // ACT
    await userEvent.click(screen.getByRole('checkbox'));
    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    expect(onSubmit).toHaveBeenCalledWith(
      expect.objectContaining({ testField: true }),
      expect.anything(),
    );
  });
});
