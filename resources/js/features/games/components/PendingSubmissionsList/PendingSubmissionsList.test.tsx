import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';
import { createGameScreenshot } from '@/test/factories';

import { PendingSubmissionsList } from './PendingSubmissionsList';

describe('Component: PendingSubmissionsList', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<PendingSubmissionsList submissions={[]} onCancel={vi.fn()} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there are no submissions, shows an empty state message', () => {
    // ARRANGE
    render(<PendingSubmissionsList submissions={[]} onCancel={vi.fn()} />);

    // ASSERT
    expect(screen.getByText(/no pending submissions/i)).toBeVisible();
  });

  it('given there are submissions, shows each with its type label, dimensions, pending badge, and thumbnail', () => {
    // ARRANGE
    const submissions = [
      createGameScreenshot({
        id: 1,
        type: 'title',
        width: 320,
        height: 240,
        smWebpUrl: 'https://example.com/thumb1.webp',
      }),
      createGameScreenshot({
        id: 2,
        type: 'ingame',
        width: 256,
        height: 224,
        smWebpUrl: 'https://example.com/thumb2.webp',
      }),
      createGameScreenshot({
        id: 3,
        type: 'completion',
        width: 640,
        height: 480,
        smWebpUrl: 'https://example.com/thumb3.webp',
      }),
    ];

    render(<PendingSubmissionsList submissions={submissions} onCancel={vi.fn()} />);

    // ASSERT
    expect(screen.getByText('Title')).toBeInTheDocument();
    expect(screen.getByText('In-game')).toBeInTheDocument();
    expect(screen.getByText('Completion')).toBeInTheDocument();

    expect(screen.getByText('320x240')).toBeInTheDocument();
    expect(screen.getByText('256x224')).toBeInTheDocument();
    expect(screen.getByText('640x480')).toBeInTheDocument();

    expect(screen.getAllByText(/pending/i)).toHaveLength(3);

    const images = screen.getAllByRole('img', { name: /your upload/i });
    expect(images).toHaveLength(3);
    expect(images[0]).toHaveAttribute('src', 'https://example.com/thumb1.webp');
    expect(images[1]).toHaveAttribute('src', 'https://example.com/thumb2.webp');
    expect(images[2]).toHaveAttribute('src', 'https://example.com/thumb3.webp');
  });

  it('given the user clicks cancel and confirms, calls onCancel with the submission id', async () => {
    // ARRANGE
    vi.spyOn(window, 'confirm').mockReturnValue(true);
    const onCancel = vi.fn();

    render(
      <PendingSubmissionsList
        submissions={[createGameScreenshot({ id: 77 })]}
        onCancel={onCancel}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /cancel submission/i }));

    // ASSERT
    expect(window.confirm).toHaveBeenCalledOnce();
    expect(onCancel).toHaveBeenCalledWith(77);
  });

  it('given the user clicks cancel but dismisses the confirmation, does not call onCancel', async () => {
    // ARRANGE
    vi.spyOn(window, 'confirm').mockReturnValue(false);
    const onCancel = vi.fn();

    render(
      <PendingSubmissionsList
        submissions={[createGameScreenshot({ id: 77 })]}
        onCancel={onCancel}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /cancel submission/i }));

    // ASSERT
    expect(window.confirm).toHaveBeenCalledOnce();
    expect(onCancel).not.toHaveBeenCalled();
  });
});
