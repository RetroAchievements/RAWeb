import { render, screen } from '@/test';
import { createUser } from '@/test/factories';

import { persistedUsersAtom } from '../../../state/shortcode.atoms';
import { ShortcodeUser } from './ShortcodeUser';

describe('Component: ShortcodeUser', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<ShortcodeUser displayName="test-user" />, {
      jotaiAtoms: [[persistedUsersAtom, []]],
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the display name matches a persisted user, renders the user avatar', () => {
    // ARRANGE
    const testUser = createUser({ displayName: 'test-user' });

    render(<ShortcodeUser displayName="test-user" />, {
      jotaiAtoms: [
        [persistedUsersAtom, [testUser]],
        //
      ],
    });

    // ASSERT
    expect(screen.getByRole('link', { name: /test-user/i })).toBeVisible();
  });

  it('given the display name does not match any persisted users, renders nothing', () => {
    // ARRANGE
    const testUser = createUser({ displayName: 'test-user' });

    render(<ShortcodeUser displayName="non-existent-user" />, {
      jotaiAtoms: [
        [persistedUsersAtom, [testUser]],
        //
      ],
    });

    // ASSERT
    expect(screen.queryByRole('link')).not.toBeInTheDocument();
  });
});
