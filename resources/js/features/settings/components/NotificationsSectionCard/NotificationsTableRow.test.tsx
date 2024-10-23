import type { FC, ReactNode } from 'react';
import { FormProvider, useForm } from 'react-hook-form';

import { render, screen } from '@/test';

import { NotificationsTableRow } from './NotificationsTableRow';

const Wrapper: FC<{ children: ReactNode }> = ({ children }) => {
  const form = useForm();

  return (
    <FormProvider {...form}>
      <table>
        <tbody>{children}</tbody>
      </table>
    </FormProvider>
  );
};

describe('Component: NotificationsTableRow', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <Wrapper>
        <NotificationsTableRow t_label="t_label" emailFieldName="0" siteFieldName="1" />
      </Wrapper>,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there is no email field name, does not render an email me checkbox', () => {
    // ARRANGE
    render(
      <Wrapper>
        <NotificationsTableRow t_label="t_label" siteFieldName="1" />
      </Wrapper>,
    );

    // ASSERT
    expect(screen.getByText(/notify me on the site/i)).toBeVisible();
    expect(screen.queryByText(/email me/i)).not.toBeInTheDocument();
  });

  it('given there is no site field name, does not render a notify me on the site checkbox', () => {
    // ARRANGE
    render(
      <Wrapper>
        <NotificationsTableRow t_label="t_label" emailFieldName="1" />
      </Wrapper>,
    );

    // ASSERT
    expect(screen.getByText(/email me/i)).toBeVisible();
    expect(screen.queryByText(/notify me on the site/i)).not.toBeInTheDocument();
  });
});
