/**
 * Multiple credit categories can contain the same user. This deduplicates
 * a combined list so each user only appears once in the avatar stack.
 */
export function deduplicateUserCredits(
  users: App.Platform.Data.UserCredits[],
): App.Platform.Data.UserCredits[] {
  const seen = new Set<string>();

  return users.filter((user) => {
    if (seen.has(user.displayName)) {
      return false;
    }

    seen.add(user.displayName);

    return true;
  });
}
