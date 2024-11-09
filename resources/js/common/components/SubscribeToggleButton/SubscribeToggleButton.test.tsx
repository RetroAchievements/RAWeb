import userEvent from '@testing-library/user-event';
import axios from 'axios';

import { render, screen } from '@/test';

import { SubscribeToggleButton } from './SubscribeToggleButton';

describe('Component: SubscribeToggleButton', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <SubscribeToggleButton hasExistingSubscription={true} subjectId={1} subjectType="GameWall" />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user is not currently subscribed, shows a "Subscribe" button', () => {
    // ARRANGE
    render(
      <SubscribeToggleButton
        hasExistingSubscription={false}
        subjectId={1}
        subjectType="GameWall"
      />,
    );

    // ASSERT
    expect(screen.getByRole('button', { name: 'Subscribe' })).toBeVisible();
  });

  it('given the user is currently subscribed, shows an "Unsubscribe" button', () => {
    // ARRANGE
    render(
      <SubscribeToggleButton hasExistingSubscription={true} subjectId={1} subjectType="GameWall" />,
    );

    // ASSERT
    expect(screen.getByRole('button', { name: /unsubscribe/i })).toBeVisible();
  });

  it('given the user is not currently subscribed and clicks the button, subscribes them to the entity', async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ success: true });

    render(
      <SubscribeToggleButton
        hasExistingSubscription={false}
        subjectId={1}
        subjectType="GameWall"
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Subscribe' }));

    // ASSERT
    expect(postSpy).toHaveBeenCalledOnce();
    expect(postSpy).toHaveBeenCalledWith([
      'api.subscription.store',
      { subjectId: 1, subjectType: 'GameWall' },
    ]);
  });

  it('given the user is already subscribed and clicks the button, unsubscribes them from the entity', async () => {
    // ARRANGE
    const deleteSpy = vi.spyOn(axios, 'delete').mockResolvedValueOnce({ success: true });

    render(
      <SubscribeToggleButton hasExistingSubscription={true} subjectId={1} subjectType="GameWall" />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /unsubscribe/i }));

    // ASSERT
    expect(deleteSpy).toHaveBeenCalledOnce();
    expect(deleteSpy).toHaveBeenCalledWith([
      'api.subscription.destroy',
      { subjectId: 1, subjectType: 'GameWall' },
    ]);
  });

  it('given the user is not currently subscribed and clicks the button, changes the button label after a successful subscribe API call', async () => {
    // ARRANGE
    vi.spyOn(axios, 'post').mockResolvedValueOnce({ success: true });

    render(
      <SubscribeToggleButton
        hasExistingSubscription={false}
        subjectId={1}
        subjectType="GameWall"
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Subscribe' }));

    // ASSERT
    expect(await screen.findByRole('button', { name: /unsubscribe/i })).toBeVisible();
  });
});
