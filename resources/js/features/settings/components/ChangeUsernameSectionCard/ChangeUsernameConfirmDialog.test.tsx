import userEvent from '@testing-library/user-event';

import { render, screen, waitFor } from '@/test';

import { ChangeUsernameConfirmDialog } from './ChangeUsernameConfirmDialog';

describe('Component: ChangeUsernameConfirmDialog', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <ChangeUsernameConfirmDialog
        isOpen={false}
        isSubmitting={false}
        requestedUsername="NewName"
        onConfirm={vi.fn()}
        onOpenChange={vi.fn()}
      />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the dialog is closed, does not show dialog content', () => {
    // ARRANGE
    render(
      <ChangeUsernameConfirmDialog
        isOpen={false}
        isSubmitting={false}
        requestedUsername="NewName"
        onConfirm={vi.fn()}
        onOpenChange={vi.fn()}
      />,
    );

    // ASSERT
    expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
  });

  it('given the dialog is open, shows the requested username verbatim', () => {
    // ARRANGE
    render(
      <ChangeUsernameConfirmDialog
        isOpen={true}
        isSubmitting={false}
        requestedUsername="NewName"
        onConfirm={vi.fn()}
        onOpenChange={vi.fn()}
      />,
    );

    // ASSERT
    expect(screen.getByText('NewName')).toBeVisible();
  });

  it('given the dialog is open, shows the 30-day cooldown warning', () => {
    // ARRANGE
    render(
      <ChangeUsernameConfirmDialog
        isOpen={true}
        isSubmitting={false}
        requestedUsername="NewName"
        onConfirm={vi.fn()}
        onOpenChange={vi.fn()}
      />,
    );

    // ASSERT
    expect(screen.getByText(/can't ask again for 30 days/i)).toBeVisible();
  });

  it('given the dialog just opened, disables the confirm button and leaves the checkbox unchecked', () => {
    // ARRANGE
    render(
      <ChangeUsernameConfirmDialog
        isOpen={true}
        isSubmitting={false}
        requestedUsername="NewName"
        onConfirm={vi.fn()}
        onOpenChange={vi.fn()}
      />,
    );

    // ASSERT
    expect(screen.getByRole('checkbox')).not.toBeChecked();
    expect(screen.getByRole('button', { name: /change name/i })).toBeDisabled();
  });

  it('given the user checks the acknowledgement, enables the confirm button', async () => {
    // ARRANGE
    render(
      <ChangeUsernameConfirmDialog
        isOpen={true}
        isSubmitting={false}
        requestedUsername="NewName"
        onConfirm={vi.fn()}
        onOpenChange={vi.fn()}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('checkbox'));

    // ASSERT
    expect(screen.getByRole('button', { name: /change name/i })).toBeEnabled();
  });

  it('given the acknowledgement is checked and the user confirms, calls onConfirm exactly once', async () => {
    // ARRANGE
    const onConfirm = vi.fn();

    render(
      <ChangeUsernameConfirmDialog
        isOpen={true}
        isSubmitting={false}
        requestedUsername="NewName"
        onConfirm={onConfirm}
        onOpenChange={vi.fn()}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('checkbox'));
    await userEvent.click(screen.getByRole('button', { name: /change name/i }));

    // ASSERT
    expect(onConfirm).toHaveBeenCalledTimes(1);
  });

  it('given a request is in flight, keeps the confirm button disabled even when the acknowledgement is checked', async () => {
    // ARRANGE
    const { rerender } = render(
      <ChangeUsernameConfirmDialog
        isOpen={true}
        isSubmitting={false}
        requestedUsername="NewName"
        onConfirm={vi.fn()}
        onOpenChange={vi.fn()}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('checkbox'));
    rerender(
      <ChangeUsernameConfirmDialog
        isOpen={true}
        isSubmitting={true}
        requestedUsername="NewName"
        onConfirm={vi.fn()}
        onOpenChange={vi.fn()}
      />,
    );

    // ASSERT
    expect(screen.getByRole('checkbox')).toBeChecked();
    expect(screen.getByRole('button', { name: /change name/i })).toBeDisabled();
  });

  it('given the user cancels, reports the close and never calls onConfirm', async () => {
    // ARRANGE
    const onConfirm = vi.fn();
    const onOpenChange = vi.fn();

    render(
      <ChangeUsernameConfirmDialog
        isOpen={true}
        isSubmitting={false}
        requestedUsername="NewName"
        onConfirm={onConfirm}
        onOpenChange={onOpenChange}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /cancel/i }));

    // ASSERT
    await waitFor(() => {
      expect(onOpenChange).toHaveBeenCalledWith(false);
    });
    expect(onConfirm).not.toHaveBeenCalled();
  });

  it('given the dialog is reopened after the acknowledgement was checked, re-arms the gate', async () => {
    // ARRANGE
    const { rerender } = render(
      <ChangeUsernameConfirmDialog
        isOpen={true}
        isSubmitting={false}
        requestedUsername="NewName"
        onConfirm={vi.fn()}
        onOpenChange={vi.fn()}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('checkbox'));

    rerender(
      <ChangeUsernameConfirmDialog
        isOpen={false}
        isSubmitting={false}
        requestedUsername="NewName"
        onConfirm={vi.fn()}
        onOpenChange={vi.fn()}
      />,
    );
    rerender(
      <ChangeUsernameConfirmDialog
        isOpen={true}
        isSubmitting={false}
        requestedUsername="NewName"
        onConfirm={vi.fn()}
        onOpenChange={vi.fn()}
      />,
    );

    // ASSERT
    expect(screen.getByRole('checkbox')).not.toBeChecked();
    expect(screen.getByRole('button', { name: /change name/i })).toBeDisabled();
  });
});
