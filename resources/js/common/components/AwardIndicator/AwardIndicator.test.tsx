import { render, screen } from '@/test';
import type { TranslatedString } from '@/types/i18next';

import { AwardIndicator } from './AwardIndicator';

describe('Component: AwardIndicator', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<AwardIndicator awardKind="mastery" />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a title prop is provided, uses that title', () => {
    // ARRANGE
    render(<AwardIndicator awardKind="mastery" title={'Custom Title' as TranslatedString} />);

    // ASSERT
    expect(screen.getByTitle('Custom Title')).toBeVisible();
  });

  it('given no title prop is provided, generates a title from the awardKind', () => {
    // ARRANGE
    render(<AwardIndicator awardKind="mastery" />);

    // ASSERT
    expect(screen.getByTitle('Mastered')).toBeVisible();
  });

  it('given awardKind is "completion" and no title prop is provided, generates "Completed" title', () => {
    // ARRANGE
    render(<AwardIndicator awardKind="completion" />);

    // ASSERT
    expect(screen.getByTitle('Completed')).toBeVisible();
  });

  it('given awardKind is "beaten-hardcore" and no title prop is provided, generates "Beaten" title', () => {
    // ARRANGE
    render(<AwardIndicator awardKind="beaten-hardcore" />);

    // ASSERT
    expect(screen.getByTitle('Beaten')).toBeVisible();
  });

  it('given awardKind is "beaten-softcore" and no title prop is provided, generates "Beaten (softcore)" title', () => {
    // ARRANGE
    render(<AwardIndicator awardKind="beaten-softcore" />);

    // ASSERT
    expect(screen.getByTitle('Beaten (softcore)')).toBeVisible();
  });

  it('renders with the correct role and aria-label', () => {
    // ARRANGE
    render(<AwardIndicator awardKind="mastery" />);

    // ASSERT
    const indicatorEl = screen.getByRole('img');

    expect(indicatorEl).toHaveAttribute('aria-label', 'Mastered');
  });

  it('applies the correct className for mastery award', () => {
    // ARRANGE
    render(<AwardIndicator awardKind="mastery" />);

    // ASSERT
    const indicatorEl = screen.getByRole('img');

    expect(indicatorEl).toHaveClass('bg-[gold]');
  });

  it('applies the correct className for completion award', () => {
    // ARRANGE
    render(<AwardIndicator awardKind="completion" />);

    // ASSERT
    const indicatorEl = screen.getByRole('img');

    expect(indicatorEl).toHaveClass('border');
    expect(indicatorEl).toHaveClass('border-yellow-600');
  });

  it('applies the correct className for beaten-hardcore award', () => {
    // ARRANGE
    render(<AwardIndicator awardKind="beaten-hardcore" />);

    // ASSERT
    const indicatorEl = screen.getByRole('img');

    expect(indicatorEl).toHaveClass('bg-zinc-300');
  });

  it('applies the correct className for beaten-softcore award', () => {
    // ARRANGE
    render(<AwardIndicator awardKind="beaten-softcore" />);

    // ASSERT
    const indicatorEl = screen.getByRole('img');

    expect(indicatorEl).toHaveClass('border');
    expect(indicatorEl).toHaveClass('border-zinc-400');
  });

  it('applies a custom className when one is provided', () => {
    // ARRANGE
    render(<AwardIndicator awardKind="mastery" className="custom-class" />);

    // ASSERT
    const indicatorEl = screen.getByRole('img');

    expect(indicatorEl).toHaveClass('custom-class');
  });
});
