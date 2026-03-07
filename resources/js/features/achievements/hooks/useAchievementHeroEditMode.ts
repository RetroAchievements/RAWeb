import { useAtomValue, useSetAtom } from 'jotai';
import { useEffect, useRef } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';

import {
  isEditModeAtom,
  isSavingAtom,
  quickEditSaveHandlerAtom,
} from '../state/achievements.atoms';
import { useAchievementQuickEditForm } from './useAchievementQuickEditForm';

export function useAchievementHeroEditMode() {
  const { achievement, can, gameAchievementSet } =
    usePageProps<App.Platform.Data.AchievementShowPageProps>();

  const isEditMode = useAtomValue(isEditModeAtom);
  const setQuickEditSaveHandler = useSetAtom(quickEditSaveHandlerAtom);
  const setIsSaving = useSetAtom(isSavingAtom);

  const canEditTitle = isEditMode && !!can?.updateAchievementTitle;
  const canEditDescription = isEditMode && !!can?.updateAchievementDescription;
  const canEditPoints = isEditMode && !!can?.updateAchievementPoints;
  const canEditType = isEditMode && !!can?.updateAchievementType;

  const { form, mutation, onSubmit } = useAchievementQuickEditForm({
    description: achievement.description!,
    points: achievement.points!,
    title: achievement.title,
    type: achievement.type ?? 'none',
  });

  // Keep a ref to the latest onSubmit so the effect below doesn't
  // depend on it. onSubmit is recreated every render because
  // useMutation returns a new object each time.
  const onSubmitRef = useRef(onSubmit);
  onSubmitRef.current = onSubmit;

  // Sync the mutation's pending state to the atom so other components can read it.
  const isSavingRef = useRef(false);
  useEffect(() => {
    isSavingRef.current = mutation.isPending;
    setIsSaving(mutation.isPending);
  }, [mutation.isPending, setIsSaving]);

  // Register the save handler when entering edit mode, and reset the form when leaving.
  useEffect(() => {
    if (!isEditMode) {
      // Don't reset the form while a save is in progress. The inputs
      // are still visible and resetting would flash the old values
      // until router.reload() refreshes page props.
      if (!isSavingRef.current) {
        form.reset();
      }

      return;
    }

    const handler = () => {
      // Set synchronously before the async handleSubmit so the
      // edit-mode effect knows not to form.reset() when isEditMode flips.
      isSavingRef.current = true;
      form.handleSubmit((values) => onSubmitRef.current(values))();
    };

    // Wrap in an arrow so jotai stores handler as the value
    // rather than interpreting it as an updater function.
    setQuickEditSaveHandler(() => handler);

    return () => setQuickEditSaveHandler(null);
  }, [isEditMode, form, setQuickEditSaveHandler]);

  // Subset achievements can only be None or Missable.
  const isSubset = !!gameAchievementSet && gameAchievementSet.type !== 'core';

  return {
    canEditDescription,
    canEditPoints,
    canEditTitle,
    canEditType,
    form,
    isEditMode,
    isSubset,
  };
}
