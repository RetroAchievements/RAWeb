import { useMutation } from '@tanstack/react-query';
import userEvent from '@testing-library/user-event';
import type { FC } from 'react';
import { useForm } from 'react-hook-form';

import { render, screen } from '@/test';

import { SectionFormCard, type SectionFormCardProps } from './SectionFormCard';

const TestHarness: FC<Omit<SectionFormCardProps, 'formMethods' | 'isSubmitting'>> = (props) => {
  const formMethods = useForm();
  const mutation = useMutation({});

  return <SectionFormCard formMethods={formMethods} isSubmitting={mutation.isPending} {...props} />;
};

describe('Component: SectionFormCard', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <TestHarness headingLabel="Heading" onSubmit={vi.fn()}>
        children
      </TestHarness>,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('renders an accessible heading label', () => {
    // ARRANGE
    render(
      <TestHarness headingLabel="Hello" onSubmit={vi.fn()}>
        children
      </TestHarness>,
    );

    // ASSERT
    expect(screen.getByRole('heading', { name: /hello/i })).toBeVisible();
  });

  it('renders an interactive form', async () => {
    // ARRANGE
    const mockOnSubmit = vi.fn();

    render(
      <TestHarness headingLabel="Hello" onSubmit={mockOnSubmit}>
        children
      </TestHarness>,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /update/i }));

    // ASSERT
    expect(mockOnSubmit).toHaveBeenCalledTimes(1);
  });

  it('can change its submit button props', () => {
    // ARRANGE
    render(
      <TestHarness
        headingLabel="Hello"
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
