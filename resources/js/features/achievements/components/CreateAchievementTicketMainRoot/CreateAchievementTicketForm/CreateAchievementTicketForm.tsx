import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import {
  BaseAlert,
  BaseAlertDescription,
  BaseAlertTitle,
} from '@/common/components/+vendor/BaseAlert';
import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { BaseFormProvider } from '@/common/components/+vendor/BaseForm';
import { usePageProps } from '@/common/hooks/usePageProps';

import { DescriptionField } from './DescriptionField';
import { EmulatorCoreField } from './EmulatorCoreField';
import { EmulatorSelectField } from './EmulatorSelectField';
import { EmulatorVersionField } from './EmulatorVersionField';
import { GameHashSelectField } from './GameHashSelectField';
import { IssueSelectField } from './IssueSelectField';
import { SessionModeToggleGroup } from './SessionModeToggleGroup';
import { useCreateAchievementTicketForm } from './useCreateAchievementTicketForm';

export const CreateAchievementTicketForm: FC = () => {
  const {
    auth,
    emulatorCore,
    emulators,
    emulatorVersion,
    gameHashes,
    selectedEmulator,
    selectedGameHashId,
    selectedMode,
    ziggy: { query },
  } = usePageProps<App.Platform.Data.CreateAchievementTicketPageProps>();

  const { t } = useTranslation();

  const { form, mutation, onSubmit } = useCreateAchievementTicketForm({
    emulatorVersion: emulatorVersion ?? '',
    emulator: getDefaultSelectedEmulator(selectedEmulator, emulators),
    hash: getDefaultGameHashId(selectedGameHashId, gameHashes),
    issue: getDefaultIssueValueFromTypeParameter(query.type as string | undefined),
    mode: getDefaultSelectedMode(selectedMode, {
      hardcore: auth!.user.points,
      softcore: auth!.user.pointsSoftcore,
    }),
    core: emulatorCore ?? '',
    description: '',
  });

  const [issue] = form.watch(['issue']);

  const descriptionFieldState = form.getFieldState('description');

  return (
    <BaseFormProvider {...form}>
      {auth?.user.locale && !auth.user.locale.startsWith('en_') ? (
        <div className="mb-5">
          <BaseAlert variant="destructive">
            <BaseAlertTitle className="font-bold">{t('Important')}</BaseAlertTitle>
            <BaseAlertDescription>
              {t(
                'Please write your ticket description in English. This helps ensure it can be resolved as quickly as possible.',
              )}
            </BaseAlertDescription>
          </BaseAlert>
        </div>
      ) : null}

      <form onSubmit={form.handleSubmit(onSubmit)}>
        <div className="flex flex-col gap-7 md:gap-4">
          <IssueSelectField />
          <EmulatorSelectField />
          <EmulatorVersionField />
          <EmulatorCoreField />
          <SessionModeToggleGroup />
          <GameHashSelectField />
          <DescriptionField />
        </div>

        <div className="mt-4 flex w-full justify-end">
          <BaseButton
            type="submit"
            disabled={
              !descriptionFieldState.isDirty ||
              issue === 'NetworkIssue' ||
              mutation.isPending ||
              mutation.isSuccess
            }
          >
            {t('Submit')}
          </BaseButton>
        </div>
      </form>
    </BaseFormProvider>
  );
};

/**
 * The selected emulator is parsed from the session user agent string.
 * We need to do a search for it in the list of available emulators that we
 * can use to open this ticket. If it's not in the list, then we still need
 * the user to pick from the available list of emulators. This edge case
 * can occur if the user was playing on an unsupported emulator.
 */
function getDefaultSelectedEmulator(
  selectedEmulator: string | null,
  allAvailableEmulators: App.Platform.Data.Emulator[],
): string | undefined {
  if (!selectedEmulator) {
    return undefined;
  }

  const foundEmulator = allAvailableEmulators.find(
    (e) => e.name.toLowerCase() === selectedEmulator.toLowerCase(),
  );

  if (!foundEmulator) {
    return undefined;
  }

  return foundEmulator.name;
}

function getDefaultGameHashId(
  selectedGameHashId: number | null,
  allAvailableGameHashes: App.Platform.Data.GameHash[],
): string | undefined {
  // If there's only one hash, just default to it.
  if (allAvailableGameHashes.length === 1) {
    return String(allAvailableGameHashes[0].id);
  }

  if (!selectedGameHashId) {
    return;
  }

  const foundSelectedGameHash = allAvailableGameHashes.find((h) => h.id === selectedGameHashId);
  if (foundSelectedGameHash) {
    return String(foundSelectedGameHash.id);
  }
}

function getDefaultSelectedMode(
  selectedMode: number | null,
  currentUserPoints: { hardcore: number; softcore: number },
): 'hardcore' | 'softcore' | undefined {
  if (selectedMode === 1) return 'hardcore';
  if (selectedMode === 0) return 'softcore';

  const hasNoPoints = !currentUserPoints.hardcore && !currentUserPoints.softcore;
  const hasEqualPoints = currentUserPoints.hardcore === currentUserPoints.softcore;
  if (hasNoPoints || hasEqualPoints) {
    return;
  }

  return currentUserPoints.hardcore >= currentUserPoints.softcore ? 'hardcore' : 'softcore';
}

function getDefaultIssueValueFromTypeParameter(
  type: string | undefined,
): 'TriggeredAtWrongTime' | 'DidNotTrigger' | undefined {
  switch (type) {
    case '1':
      return 'TriggeredAtWrongTime';

    case '2':
      return 'DidNotTrigger';

    default:
      return undefined;
  }
}
