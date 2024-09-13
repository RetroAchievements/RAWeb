import { createAchievement, createGame } from '@/test/factories';

import { buildStructuredMessage } from './buildStructuredMessage';

/**
 * Discord webhooks assume certain message subject lines, so we enforce
 * all of them with this comprehensive unit test.
 *
 * @see NotifyMessageThreadParticipants.php
 */

describe('Util: buildStructuredMessage', () => {
  it('is defined', () => {
    // ASSERT
    expect(buildStructuredMessage).toBeDefined();
  });

  it.each([
    {
      templateKind: 'writing-error',
      expectedSubject: 'Writing: That Was Easy [9] (Sonic the Hedgehog)',
    },
    {
      templateKind: 'misclassification',
      expectedSubject: 'Incorrect type: That Was Easy [9] (Sonic the Hedgehog)',
    },
    {
      templateKind: 'unwelcome-concept',
      expectedSubject: 'Unwelcome Concept: That Was Easy [9] (Sonic the Hedgehog)',
    },
    {
      templateKind: 'achievement-issue',
      expectedSubject: 'Issue: That Was Easy [9] (Sonic the Hedgehog)',
    },
    {
      templateKind: 'manual-unlock',
      expectedSubject: 'Manual Unlock: That Was Easy [9] (Sonic the Hedgehog)',
    },
  ])(
    'returns a structured message for the $templateKind template',
    ({ templateKind, expectedSubject }) => {
      // ARRANGE
      const achievement = createAchievement({
        id: 9,
        title: 'That Was Easy',
        game: createGame({ title: 'Sonic the Hedgehog' }),
      });

      // ACT
      const result = buildStructuredMessage(
        achievement,
        templateKind as Parameters<typeof buildStructuredMessage>[1],
      );

      // ASSERT
      expect(result.subject).toEqual(expectedSubject);
      expect(result.message).toContain('[ach=9]');
      expect(result.templateKind).toEqual(templateKind);
    },
  );
});
