import userEvent from '@testing-library/user-event';
import { vi } from 'vitest';

import type { AchievementSortOrder } from '@/common/models';
import { render, screen } from '@/test';

import { AchievementSortButton } from './AchievementSortButton';

const defaultSortOrders: AchievementSortOrder[] = [
  'active',
  '-normal',
  'normal',
  '-displayOrder',
  'displayOrder',
  'points',
  '-points',
  'title',
  '-title',
  'type',
  '-type',
  '-wonBy',
  'wonBy',
];

describe('Component: AchievementSortButton', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <AchievementSortButton
        value="displayOrder"
        onChange={vi.fn()}
        availableSortOrders={defaultSortOrders}
      />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given display order sort, shows the correct icon and text', () => {
    // ARRANGE
    const onChange = vi.fn();

    render(
      <AchievementSortButton
        value="displayOrder"
        onChange={onChange}
        availableSortOrders={defaultSortOrders}
      />,
    );

    // ASSERT
    expect(screen.getByText(/display order/i)).toBeVisible();
    expect(screen.getByTestId('sort-ascending-icon')).toBeVisible();
  });

  it('given reverse display order sort, shows the correct icon and text', () => {
    // ARRANGE
    const onChange = vi.fn();

    render(
      <AchievementSortButton
        value="-displayOrder"
        onChange={onChange}
        availableSortOrders={defaultSortOrders}
      />,
    );

    // ASSERT
    expect(screen.getByText(/display order/i)).toBeVisible();
    expect(screen.getByTestId('sort-descending-icon')).toBeVisible();
  });

  it('given won by sort, shows the correct icon and text', () => {
    // ARRANGE
    const onChange = vi.fn();

    render(
      <AchievementSortButton
        value="wonBy"
        onChange={onChange}
        availableSortOrders={defaultSortOrders}
      />,
    );

    // ASSERT
    expect(screen.getByText(/won by/i)).toBeVisible();
    expect(screen.getByTestId('sort-ascending-icon')).toBeVisible();
  });

  it('given reverse won by sort, shows the correct icon and text', () => {
    // ARRANGE
    const onChange = vi.fn();

    render(
      <AchievementSortButton
        value="-wonBy"
        onChange={onChange}
        availableSortOrders={defaultSortOrders}
      />,
    );

    // ASSERT
    expect(screen.getByText(/won by/i)).toBeVisible();
    expect(screen.getByTestId('sort-descending-icon')).toBeVisible();
  });

  it('given active sort, shows the correct icon and text', () => {
    // ARRANGE
    const onChange = vi.fn();

    render(
      <AchievementSortButton
        value="active"
        onChange={onChange}
        availableSortOrders={defaultSortOrders}
      />,
    );

    // ASSERT
    expect(screen.getByText(/status/i)).toBeVisible();
    expect(screen.getByTestId('sort-ascending-icon')).toBeVisible();
  });

  it('given active is not included in the available sort orders, does not show the status option in dropdown', async () => {
    // ARRANGE
    const onChange = vi.fn();

    render(
      <AchievementSortButton
        value="displayOrder"
        onChange={onChange}
        availableSortOrders={['displayOrder']}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button'));

    // ASSERT
    expect(screen.queryByRole('menuitemcheckbox', { name: /status/i })).not.toBeInTheDocument();
  });

  it('when clicking the status sort option, calls onChange with active', async () => {
    // ARRANGE
    const onChange = vi.fn();

    render(
      <AchievementSortButton
        value="displayOrder"
        onChange={onChange}
        availableSortOrders={defaultSortOrders}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button'));
    await userEvent.click(screen.getByRole('menuitemcheckbox', { name: /^status$/i }));

    // ASSERT
    expect(onChange).toHaveBeenCalledWith('active');
  });

  it('when clicking display order (first) option, calls onChange with displayOrder', async () => {
    // ARRANGE
    const onChange = vi.fn();

    render(
      <AchievementSortButton
        value="wonBy"
        onChange={onChange}
        availableSortOrders={['displayOrder', '-displayOrder']}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button'));
    await userEvent.click(
      screen.getByRole('menuitemcheckbox', { name: /display order \(first\)/i }),
    );

    // ASSERT
    expect(onChange).toHaveBeenCalledWith('displayOrder');
  });

  it('when clicking display order (last) option, calls onChange with -displayOrder', async () => {
    // ARRANGE
    const onChange = vi.fn();

    render(
      <AchievementSortButton
        value="wonBy"
        onChange={onChange}
        availableSortOrders={['displayOrder', '-displayOrder']}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button'));
    await userEvent.click(
      screen.getByRole('menuitemcheckbox', { name: /display order \(last\)/i }),
    );

    // ASSERT
    expect(onChange).toHaveBeenCalledWith('-displayOrder');
  });

  it('when clicking won by (most) option, calls onChange with wonBy', async () => {
    // ARRANGE
    const onChange = vi.fn();

    render(
      <AchievementSortButton
        value="displayOrder"
        onChange={onChange}
        availableSortOrders={['wonBy', '-wonBy']}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button'));
    await userEvent.click(screen.getByRole('menuitemcheckbox', { name: /won by \(most\)/i }));

    // ASSERT
    expect(onChange).toHaveBeenCalledWith('wonBy');
  });

  it('when clicking won by (least) option, calls onChange with -wonBy', async () => {
    // ARRANGE
    const onChange = vi.fn();

    render(
      <AchievementSortButton
        value="displayOrder"
        onChange={onChange}
        availableSortOrders={['wonBy', '-wonBy']}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button'));
    await userEvent.click(screen.getByRole('menuitemcheckbox', { name: /won by \(least\)/i }));

    // ASSERT
    expect(onChange).toHaveBeenCalledWith('-wonBy');
  });
});
