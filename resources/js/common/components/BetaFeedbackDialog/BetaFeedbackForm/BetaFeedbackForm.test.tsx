import userEvent from '@testing-library/user-event';
import axios from 'axios';
import { route } from 'ziggy-js';

import { render, screen, waitFor } from '@/test';

import { BaseDialog } from '../../+vendor/BaseDialog';
import { BetaFeedbackForm } from './BetaFeedbackForm';

describe('Component: BetaFeedbackForm', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <BaseDialog open={true}>
        <BetaFeedbackForm betaName="test-beta" onSubmitSuccess={vi.fn()} />
      </BaseDialog>,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays all five rating options with emoji icons', () => {
    // ARRANGE
    render(
      <BaseDialog open={true}>
        <BetaFeedbackForm betaName="test-beta" onSubmitSuccess={vi.fn()} />
      </BaseDialog>,
    );

    // ASSERT
    expect(screen.getByLabelText(/strongly dislike/i)).toBeVisible();
    expect(screen.getByLabelText(/^dislike$/i)).toBeVisible();
    expect(screen.getByLabelText(/neutral/i)).toBeVisible();
    expect(screen.getByLabelText(/^like$/i)).toBeVisible();
    expect(screen.getByLabelText(/strongly like/i)).toBeVisible();
  });

  it('displays both feedback textarea fields', () => {
    // ARRANGE
    render(
      <BaseDialog open={true}>
        <BetaFeedbackForm betaName="test-beta" onSubmitSuccess={vi.fn()} />
      </BaseDialog>,
    );

    // ASSERT
    expect(screen.getByLabelText(/what's better than before/i)).toBeVisible();
    expect(screen.getByLabelText(/what still needs work/i)).toBeVisible();
  });

  it('displays submit and cancel buttons', () => {
    // ARRANGE
    render(
      <BaseDialog open={true}>
        <BetaFeedbackForm betaName="test-beta" onSubmitSuccess={vi.fn()} />
      </BaseDialog>,
    );

    // ASSERT
    expect(screen.getByRole('button', { name: /submit/i })).toBeVisible();
    expect(screen.getByRole('button', { name: /maybe later/i })).toBeVisible();
  });

  it('given the user selects a rating, updates the form state', async () => {
    // ARRANGE
    render(
      <BaseDialog open={true}>
        <BetaFeedbackForm betaName="test-beta" onSubmitSuccess={vi.fn()} />
      </BaseDialog>,
    );

    // ACT
    await userEvent.click(screen.getByLabelText(/strongly like/i));

    // ASSERT
    expect(screen.getByLabelText(/strongly like/i)).toHaveAttribute('data-state', 'on');
  });

  it('given the user enters feedback text, updates the form fields', async () => {
    // ARRANGE
    render(
      <BaseDialog open={true}>
        <BetaFeedbackForm betaName="test-beta" onSubmitSuccess={vi.fn()} />
      </BaseDialog>,
    );

    // ACT
    const positiveTextarea = screen.getByLabelText(/what's better than before/i);
    const negativeTextarea = screen.getByLabelText(/what still needs work/i);

    await userEvent.type(positiveTextarea, 'Great new design!');
    await userEvent.type(negativeTextarea, 'Loading is slow');

    // ASSERT
    expect(positiveTextarea).toHaveValue('Great new design!');
    expect(negativeTextarea).toHaveValue('Loading is slow');
  });

  it('given the user submits valid feedback, calls the API with correct data', async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ data: { success: true } });
    const onSubmitSuccess = vi.fn();

    render(
      <BaseDialog open={true}>
        <BetaFeedbackForm betaName="new-game-page" onSubmitSuccess={onSubmitSuccess} />
      </BaseDialog>,
    );

    // ACT
    await userEvent.click(screen.getByLabelText(/^like$/i));
    await userEvent.type(screen.getByLabelText(/what's better than before/i), 'Clean interface');
    await userEvent.type(screen.getByLabelText(/what still needs work/i), 'Missing filters');
    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    await waitFor(() => {
      expect(postSpy).toHaveBeenCalledWith(route('api.beta-feedback.store'), {
        betaName: 'new-game-page',
        negativeFeedback: 'Missing filters',
        positiveFeedback: 'Clean interface',
        rating: 4,
      });
    });
  });

  it('given the user submits feedback successfully, calls the onSubmitSuccess callback', async () => {
    // ARRANGE
    vi.spyOn(axios, 'post').mockResolvedValueOnce({ data: { success: true } });
    const onSubmitSuccess = vi.fn();

    render(
      <BaseDialog open={true}>
        <BetaFeedbackForm betaName="test-beta" onSubmitSuccess={onSubmitSuccess} />
      </BaseDialog>,
    );

    // ACT
    await userEvent.click(screen.getByLabelText(/neutral/i));
    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    await waitFor(() => {
      expect(onSubmitSuccess).toHaveBeenCalledTimes(1);
    });
  });

  it('given the submission is in progress, disables the submit button', async () => {
    // ARRANGE
    vi.spyOn(axios, 'post').mockImplementation(
      () => new Promise((resolve) => setTimeout(resolve, 100)),
    );

    render(
      <BaseDialog open={true}>
        <BetaFeedbackForm betaName="test-beta" onSubmitSuccess={vi.fn()} />
      </BaseDialog>,
    );

    // ACT
    await userEvent.click(screen.getByLabelText(/neutral/i));
    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    expect(screen.getByRole('button', { name: /submit/i })).toBeDisabled();
  });

  it('given the submission succeeds, shows a success toast', async () => {
    // ARRANGE
    vi.spyOn(axios, 'post').mockResolvedValueOnce({ data: { success: true } });

    render(
      <BaseDialog open={true}>
        <BetaFeedbackForm betaName="test-beta" onSubmitSuccess={vi.fn()} />
      </BaseDialog>,
    );

    // ACT
    await userEvent.click(screen.getByLabelText(/neutral/i));
    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getAllByText(/submitted!/i)[0]).toBeVisible();
    });
  });

  it('given the submission fails, shows an error toast', async () => {
    // ARRANGE
    vi.spyOn(axios, 'post').mockRejectedValueOnce(new Error('Network error'));

    render(
      <BaseDialog open={true}>
        <BetaFeedbackForm betaName="test-beta" onSubmitSuccess={vi.fn()} />
      </BaseDialog>,
    );

    // ACT
    await userEvent.click(screen.getByLabelText(/neutral/i));
    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/something went wrong/i)).toBeVisible();
    });
  });

  it('given the submission fails, does not call onSubmitSuccess', async () => {
    // ARRANGE
    vi.spyOn(axios, 'post').mockRejectedValueOnce(new Error('Network error'));
    const onSubmitSuccess = vi.fn();

    render(
      <BaseDialog open={true}>
        <BetaFeedbackForm betaName="test-beta" onSubmitSuccess={onSubmitSuccess} />
      </BaseDialog>,
    );

    // ACT
    await userEvent.click(screen.getByLabelText(/neutral/i));
    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/something went wrong/i)).toBeVisible();
    });
    expect(onSubmitSuccess).not.toHaveBeenCalled();
  });
});
