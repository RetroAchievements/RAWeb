import { render, screen } from '@/test';

import { MarkdownRenderer } from './MarkdownRenderer';

describe('Component: MarkdownRenderer', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<MarkdownRenderer />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given null children, does not crash', () => {
    // ARRANGE
    const { container } = render(<MarkdownRenderer>{null}</MarkdownRenderer>);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given plain text, renders the text', () => {
    // ARRANGE
    render(<MarkdownRenderer>Hello world</MarkdownRenderer>);

    // ASSERT
    expect(screen.getByText(/hello world/i)).toBeVisible();
  });

  it('given markdown with bold text, renders formatted text', () => {
    // ARRANGE
    render(<MarkdownRenderer>**Bold text**</MarkdownRenderer>);

    // ASSERT
    expect(screen.getByText(/bold text/i)).toBeVisible();
    expect(screen.getByText(/bold text/i).tagName).toEqual('STRONG');
  });

  it('given markdown with italic text, renders formatted text', () => {
    // ARRANGE
    render(<MarkdownRenderer>*Italic text*</MarkdownRenderer>);

    // ASSERT
    expect(screen.getByText(/italic text/i)).toBeVisible();
    expect(screen.getByText(/italic text/i).tagName).toEqual('EM');
  });

  it('given markdown with a link, renders a clickable link', () => {
    // ARRANGE
    render(<MarkdownRenderer>[Click here](https://example.com)</MarkdownRenderer>);

    // ASSERT
    const link = screen.getByRole('link', { name: /click here/i });
    expect(link).toBeVisible();
    expect(link).toHaveAttribute('href', 'https://example.com');
  });

  it('given markdown with a header, renders the header', () => {
    // ARRANGE
    render(<MarkdownRenderer># Main Heading</MarkdownRenderer>);

    // ASSERT
    expect(screen.getByRole('heading', { name: /main heading/i })).toBeVisible();
  });

  it('given markdown with inline code, renders formatted code', () => {
    // ARRANGE
    render(<MarkdownRenderer>Use the `code` function</MarkdownRenderer>);

    // ASSERT
    expect(screen.getByText(/code/i)).toBeVisible();
    expect(screen.getByText(/code/i).tagName).toEqual('CODE');
  });

  it('given markdown with strikethrough text, renders formatted text', () => {
    // ARRANGE
    render(<MarkdownRenderer>~~Strikethrough text~~</MarkdownRenderer>);

    // ASSERT
    expect(screen.getByText(/strikethrough text/i)).toBeVisible();
    expect(screen.getByText(/strikethrough text/i).tagName).toEqual('DEL');
  });
});
