export function getOtherParticipant(
  messageThread: App.Community.Data.MessageThread,
  senderUserDisplayName?: string,
): App.Data.User | undefined {
  return (
    messageThread.participants?.find(
      (participant) => participant.displayName !== senderUserDisplayName,
    ) ?? messageThread.participants?.[0]
  );
}
