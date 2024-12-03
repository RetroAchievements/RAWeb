import { useMutation } from '@tanstack/react-query';
import userEvent from '@testing-library/user-event';
import type { FC } from 'react';
import { useForm } from 'react-hook-form';

import i18n from '@/i18n-client';
import { render, screen } from '@/test';

import { SectionFormCard, type SectionFormCardProps } from './SectionFormCard';

// We need to instantiate props with a hook, so a test harness is required.
const TestHarness: FC<Omit<SectionFormCardProps, 'formMethods' | 'isSubmitting'>> = (props) => {
  const formMethods = useForm();
  const mutation = useMutation({});

  return <SectionFormCard formMethods={formMethods} isSubmitting={mutation.isPending} {...props} />;
};

describe('Component: SectionFormCard', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <TestHarness t_headingLabel={i18n.t('Profile')} onSubmit={vi.fn()}>
        children
      </TestHarness>,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('renders an accessible heading label', () => {
    // ARRANGE
    render(
      <TestHarness t_headingLabel={i18n.t('Profile')} onSubmit={vi.fn()}>
        children
      </TestHarness>,
    );

    // ASSERT
    expect(screen.getByRole('heading', { name: /profile/i })).toBeVisible();
  });

  it('renders an interactive form', async () => {
    // ARRANGE
    const mockOnSubmit = vi.fn();

    render(
      <TestHarness t_headingLabel={i18n.t('Profile')} onSubmit={mockOnSubmit}>
        children
      </TestHarness>,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /update/i }));

    // ASSERT
    expect(mockOnSubmit).toHaveBeenCalledOnce();
  });

  it('can change its submit button props', () => {
    // ARRANGE
    render(
      <TestHarness
        t_headingLabel={i18n.t('Profile')}
        onSubmit={vi.fn()}
        buttonProps={{ children: 'some different label' }}
      >
        children
      </TestHarness>,
    );

    // ASSERT
    expect(screen.getByRole('button', { name: /some different label/i })).toBeVisible();
  });
});
