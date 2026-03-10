import { useAtomValue, useSetAtom } from 'jotai';

import {
  isEditModeAtom,
  isSavingAtom,
  quickEditSaveHandlerAtom,
} from '../state/achievements.atoms';

export function useAchievementQuickEditActions() {
  const setIsEditMode = useSetAtom(isEditModeAtom);
  const quickEditSaveHandler = useAtomValue(quickEditSaveHandlerAtom);
  const isSaving = useAtomValue(isSavingAtom);

  const handleSave = () => {
    quickEditSaveHandler?.();
  };

  const handleCancel = () => {
    setIsEditMode(false);
  };

  return { handleSave, handleCancel, isSaving };
}
