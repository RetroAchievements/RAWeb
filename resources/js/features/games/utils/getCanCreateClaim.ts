export function getCanCreateClaim(claimData: App.Platform.Data.GamePageClaimData | null): boolean {
  const hasPrimarySlotAvailable = (claimData?.numClaimsRemaining ?? 0) > 0;
  const canCreateWithoutPrimarySlot = claimData?.isSoleAuthor || claimData?.wouldBeCollaboration;

  return hasPrimarySlotAvailable || !!canCreateWithoutPrimarySlot;
}
