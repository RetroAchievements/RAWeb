import { buildUserAvatarUrl } from './buildUserAvatarUrl';

describe('Util: buildUserAvatarUrl', () => {
  it('is defined', () => {
    // ASSERT
    expect(buildUserAvatarUrl).toBeDefined();
  });

  it('formats the URL correctly', () => {
    // ACT
    const result = buildUserAvatarUrl('Scott.png');

    // ASSERT
    expect(result).toContain('/UserPic/Scott.png');
  });
});
