import { render, screen } from '@/test';

import { MessagePreviewContent } from './MessagePreviewContent';

describe('Component: MessagePreviewContent', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<MessagePreviewContent previewContent="" />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given preview content is provided, renders it in the preview section', () => {
    // ARRANGE
    const previewContent = 'Some test preview content';

    render(<MessagePreviewContent previewContent={previewContent} />);

    // ASSERT
    expect(screen.getByText(/some test preview content/i)).toBeVisible();
  });
});
