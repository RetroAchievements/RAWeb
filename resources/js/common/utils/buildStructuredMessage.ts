interface StructuredMessage {
  subject: string;
  message: string;
  templateKind: string;
}

export function buildStructuredMessage(
  achievement: App.Platform.Data.Achievement,
  templateKind:
    | 'writing-error'
    | 'misclassification'
    | 'unwelcome-concept'
    | 'achievement-issue'
    | 'manual-unlock',
): StructuredMessage {
  const { id } = achievement;

  let subject = '';
  let message = '';

  if (templateKind === 'writing-error') {
    subject = buildStructuredSubject('Writing', achievement);
    message = `I'd like to report a spelling/grammar error in [ach=${id}]:
(Describe the issue here)`;
  }

  if (templateKind === 'misclassification') {
    subject = buildStructuredSubject('Incorrect type', achievement);
    message = `I'd like to report a misclassification error in [ach=${id}]:
(Describe the issue here)`;
  }

  if (templateKind === 'unwelcome-concept') {
    subject = buildStructuredSubject('Unwelcome Concept', achievement);
    message = `I'd like to report an unwelcome concept in [ach=${id}].
  
- Which Unwelcome Concept:
(Insert which concept from the docs here)
  
- Detailed Explanation:
(Provide as much detail as possible here. Assume the reader may not have played the game before. The more detail you provide, the better your case.)`;
  }

  if (templateKind === 'achievement-issue') {
    subject = buildStructuredSubject('Issue', achievement);
    message = `I'd like to report an issue with [ach=${id}]:
  
(Describe the issue here)`;
  }

  if (templateKind === 'manual-unlock') {
    subject = buildStructuredSubject('Manual Unlock', achievement);
    message = `I'd like a manual unlock for [ach=${id}]:
(Provide link to video/screenshot showing evidence)`;
  }

  return { subject, message, templateKind };
}

function buildStructuredSubject(label: string, achievement: App.Platform.Data.Achievement): string {
  const { id, title } = achievement;

  return `${label}: ${title} [${id}] (${achievement.game?.title})`;
}
