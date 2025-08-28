import type { FC, ReactNode } from 'react';
import { FormProvider, useForm } from 'react-hook-form';

import i18n from '@/i18n-client';
import { render, screen } from '@/test';

import { NotificationsSmallRow } from './NotificationsSmallRow';

const Wrapper: FC<{ children: ReactNode }> = ({ children }) => {
  const form = useForm();

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
});
