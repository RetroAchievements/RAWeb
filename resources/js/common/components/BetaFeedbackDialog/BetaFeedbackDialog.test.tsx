import userEvent from '@testing-library/user-event';
import axios from 'axios';

import { render, screen, waitFor } from '@/test';

import { BetaFeedbackDialog } from './BetaFeedbackDialog';

describe('Component: BetaFeedbackDialog', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <BetaFeedbackDialog betaName="test-beta">
        <button>Open Feedback</button>
      </BetaFeedbackDialog>,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('renders the trigger children', () => {
    // ARRANGE
    render(
      <BetaFeedbackDialog betaName="test-beta">
        <button>Give Feedback</button>
      </BetaFeedbackDialog>,
    );

    // ASSERT
    expect(screen.getByRole('button', { name: /give feedback/i })).toBeVisible();
  });

  it('given the trigger is clicked, opens the dialog', async () => {
    // ARRANGE
    render(
      <BetaFeedbackDialog betaName="test-beta">
        <button>Open Feedback</button>
      </BetaFeedbackDialog>,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /open feedback/i }));

    // ASSERT
    expect(screen.getByRole('dialog')).toBeVisible();
  });

  it('given the dialog is open, displays the feedback form', async () => {
    // ARRANGE
    render(
      <BetaFeedbackDialog betaName="test-beta">
        <button>Open Feedback</button>
      </BetaFeedbackDialog>,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /open feedback/i }));

    // ASSERT
    expect(screen.getByText(/how satisfied are you/i)).toBeVisible();
  });

  it('given the dialog is open and the user clicks nevermind, closes the dialog', async () => {
    // ARRANGE
    render(
      <BetaFeedbackDialog betaName="test-beta">
        <button>Open Feedback</button>
      </BetaFeedbackDialog>,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /open feedback/i }));
    await userEvent.click(screen.getByRole('button', { name: /maybe later/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });
  });

  it('given the form is submitted successfully, closes the dialog', async () => {
    // ARRANGE
    vi.spyOn(axios, 'post').mockResolvedValueOnce({ data: { success: true } });

    render(
      <BetaFeedbackDialog betaName="test-beta">
        <button>Open Feedback</button>
      </BetaFeedbackDialog>,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /open feedback/i }));
    await userEvent.click(screen.getByLabelText(/neutral/i));
    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });
  });

  it('given the form submission fails, keeps the dialog open', async () => {
    // ARRANGE
    vi.spyOn(axios, 'post').mockRejectedValueOnce(new Error('Network error'));

    render(
      <BetaFeedbackDialog betaName="test-beta">
        <button>Open Feedback</button>
      </BetaFeedbackDialog>,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /open feedback/i }));
    await userEvent.click(screen.getByLabelText(/neutral/i));
    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/something went wrong/i)).toBeVisible();
    });
    expect(screen.getByRole('dialog')).toBeVisible();
  });
});
