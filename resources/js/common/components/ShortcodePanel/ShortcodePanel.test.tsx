import userEvent from '@testing-library/user-event';
import { FormProvider, useForm } from 'react-hook-form';

import { render, screen } from '@/test';

import { BaseFormControl, BaseFormField, BaseFormItem } from '../+vendor/BaseForm';
import { ShortcodePanel } from './ShortcodePanel';

const TestWrapper = () => {
  const form = useForm({
    defaultValues: {
      body: 'Hello world.',
    },
  });

  return (
    <FormProvider {...form}>
      <form>
        <ShortcodePanel />
        <BaseFormField
          control={form.control}
          name="body"
          render={({ field }) => (
            <BaseFormItem>
              <BaseFormControl>
                <textarea {...field} />
              </BaseFormControl>
            </BaseFormItem>
          )}
        />
      </form>
    </FormProvider>
  );
};

describe('Component: ShortcodePanel', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<TestWrapper />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user clicks the bold button, wraps the selected text with the bold shortcode', async () => {
    // ARRANGE
    render(<TestWrapper />);

    const textarea = screen.getByRole('textbox') as HTMLTextAreaElement;
    textarea.focus();
    textarea.selectionStart = 6;
    textarea.selectionEnd = 11;

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /bold/i }));

    // ASSERT
    expect(textarea.value).toEqual('Hello [b]world[/b].');
  });

  it('given there is no selected text, injects an empty shortcode at cursor position', async () => {
    // ARRANGE
    render(<TestWrapper />);

    const textarea = screen.getByRole('textbox') as HTMLTextAreaElement;
    textarea.focus();
    textarea.selectionStart = 5;
    textarea.selectionEnd = 5;

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /bold/i }));

    // ASSERT
    expect(textarea.value).toEqual('Hello[b][/b] world.');
  });

  it('given the user clicks the link button, wraps the selected text with the url shortcode', async () => {
    // ARRANGE
    render(<TestWrapper />);

    const textarea = screen.getByRole('textbox') as HTMLTextAreaElement;
    textarea.focus();
    textarea.selectionStart = 6;
    textarea.selectionEnd = 11;

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /link/i }));

    // ASSERT
    expect(textarea.value).toEqual('Hello [url=world].');
  });

  it('given the user clicks the image button, wraps the selected text with the img shortcode', async () => {
    // ARRANGE
    render(<TestWrapper />);

    const textarea = screen.getByRole('textbox') as HTMLTextAreaElement;
    textarea.focus();
    textarea.selectionStart = 6;
    textarea.selectionEnd = 11;

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /image/i }));

    // ASSERT
    expect(textarea.value).toEqual('Hello [img=world].');
  });

  it('maintains focus on the textarea after injecting a shortcode', async () => {
    // ARRANGE
    render(<TestWrapper />);

    const textarea = screen.getByRole('textbox') as HTMLTextAreaElement;
    textarea.focus();
    textarea.selectionStart = 6;
    textarea.selectionEnd = 11;

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /bold/i }));

    // ASSERT
    expect(textarea).toHaveFocus();
  });
});
