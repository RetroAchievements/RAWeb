import userEvent from '@testing-library/user-event';
import * as ReactUseModule from 'react-use';

import { render, screen } from '@/test';

import { OAuthCredentialField } from './OAuthCredentialField';

describe('Component: OAuthCredentialField', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <OAuthCredentialField credentialName="client ID" label="Client ID" value="client-123" />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a long credential, truncates it', () => {
    // ARRANGE
    render(
      <OAuthCredentialField
        credentialName="client secret"
        label="Client secret"
        value="2iCu04BcTGySf07b7wzbLnXDhzc4vbsJyh"
      />,
    );

    // ACT
    const truncatedValue = screen.getByText('2iCu04...vbsJyh');

    // ASSERT
    expect(truncatedValue).toBeVisible();
    expect(screen.queryByText('2iCu04BcTGySf07b7wzbLnXDhzc4vbsJyh')).not.toBeInTheDocument();
  });

  it('given the user clicks the field, copies the full credential to the clipboard', async () => {
    // ARRANGE
    const copyToClipboardSpy = vi.fn();
    vi.spyOn(ReactUseModule, 'useCopyToClipboard').mockReturnValue([
      null as any,
      copyToClipboardSpy,
    ]);

    render(
      <OAuthCredentialField
        credentialName="client secret"
        label="Client secret"
        value="2iCu04BcTGySf07b7wzbLnXDhzc4vbsJyh"
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Copy client secret' }));

    // ASSERT
    expect(copyToClipboardSpy).toHaveBeenCalledWith('2iCu04BcTGySf07b7wzbLnXDhzc4vbsJyh');
    expect(await screen.findByText('Copied!')).toBeVisible();
  });
});
