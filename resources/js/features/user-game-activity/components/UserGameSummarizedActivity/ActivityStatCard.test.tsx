import userEvent from '@testing-library/user-event';

import i18n from '@/i18n-client';
import { render, screen, waitFor } from '@/test';
import type { TranslatedString } from '@/types/i18next';

import { ActivityStatCard } from './ActivityStatCard';

describe('Component: ActivityStatCard', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <ActivityStatCard t_label={i18n.t('1 session')}>Test content</ActivityStatCard>,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a label is provided, shows it in the header', () => {
    // ARRANGE
    render(
      <ActivityStatCard t_label={'Test Label' as TranslatedString}>Test content</ActivityStatCard>,
    );

    // ASSERT
    expect(screen.getByText(/test label/i)).toBeVisible();
  });

  it('given content is provided, shows it in the card body', () => {
    // ARRANGE
    render(
      <ActivityStatCard t_label={'Test Label' as TranslatedString}>Test content</ActivityStatCard>,
    );

    // ASSERT
    expect(screen.getByText(/test content/i)).toBeVisible();
  });

  it('given a tooltip is provided, shows an info icon that reveals the tooltip on hover', async () => {
    // ARRANGE
    render(
      <ActivityStatCard
        t_label={'Test Label' as TranslatedString}
        t_tooltip={'Test tooltip' as TranslatedString}
      >
        Test content
      </ActivityStatCard>,
    );

    // ACT
    await userEvent.hover(screen.getByRole('button', { name: /see more info/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getAllByText(/test tooltip/i)[0]).toBeVisible();
    });
  });

  it('given no tooltip is provided, does not show an info icon', () => {
    // ARRANGE
    render(
      <ActivityStatCard t_label={'Test Label' as TranslatedString}>Test content</ActivityStatCard>,
    );

    // ASSERT
    expect(screen.queryByRole('button')).not.toBeInTheDocument();
  });
});
