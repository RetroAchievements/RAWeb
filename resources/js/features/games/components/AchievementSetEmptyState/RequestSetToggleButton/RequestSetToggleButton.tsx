import * as motion from 'motion/react-m';
import { type FC, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { LuCheck, LuPlus } from 'react-icons/lu';
import { route } from 'ziggy-js';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { BaseToggle } from '@/common/components/+vendor/BaseToggle';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';
import { useDestroySetRequestMutation } from '@/features/games/hooks/mutations/useDestroySetRequestMutation';
import { useStoreSetRequestMutation } from '@/features/games/hooks/mutations/useStoreSetRequestMutation';

export const RequestSetToggleButton: FC = () => {
  const { auth, backingGame, setRequestData } = usePageProps<App.Platform.Data.GameShowPageProps>();
  const { t } = useTranslation();

  const [isPressed, setIsPressed] = useState(!!setRequestData?.hasUserRequestedSet);
  const [isDebounced, setIsDebounced] = useState(false);
  const debounceTimerRef = useRef<NodeJS.Timeout | null>(null);

  const storeMutation = useStoreSetRequestMutation();
  const destroyMutation = useDestroySetRequestMutation();

  if (!setRequestData) {
    return null;
  }

  const mutation = setRequestData.hasUserRequestedSet ? destroyMutation : storeMutation;

  const handlePressedChange = async () => {
    if (!auth?.user) {
      window.location.assign(route('login'));

      return;
    }

    // Start the debounce period.
    setIsDebounced(true);

    // Clear any existing timer.
    if (debounceTimerRef.current) {
      clearTimeout(debounceTimerRef.current);
    }

    // Set a new timer to re-enable after 2 seconds.
    debounceTimerRef.current = setTimeout(() => {
      setIsDebounced(false);
    }, 2000);

    const originalIsPressed = isPressed;
    setIsPressed((prev) => !prev);

    const { hasUserRequestedSet } = setRequestData;

    const strings = {
      loading: hasUserRequestedSet ? t('Removing request...') : t('Requesting...'),
      success: hasUserRequestedSet ? t('Removed request!') : t('Requested!'),
      error: t('Something went wrong.'),
    };

    try {
      await toastMessage.promise(mutation.mutateAsync({ gameId: backingGame.id }), {
        ...strings,
      });
    } catch {
      setIsPressed(originalIsPressed);
    }
  };

  return (
    <BaseToggle
      className={cn(
        'relative gap-1.5 border border-embed-highlight hover:border-neutral-700 light:hover:border-neutral-200',
        'data-[state=on]:hover:border-embed-highlight',
        'disabled:pointer-events-auto disabled:opacity-100',
      )}
      // could be negative in some bizarre scenarios
      disabled={mutation.isPending || setRequestData.userRequestsRemaining < 1 || isDebounced}
      pressed={isPressed}
      onPressedChange={handlePressedChange}
    >
      {/* Icon with rotation animation */}
      <div className="relative size-4">
        <motion.div
          className="absolute inset-0 flex items-center justify-center"
          initial={false}
          animate={{
            opacity: isPressed ? 1 : 0,
            scale: isPressed ? 1 : 0.8,
            rotate: isPressed ? 0 : -90,
          }}
          transition={{ duration: 0.2, ease: 'easeOut' }}
        >
          <LuCheck className="size-4" />
        </motion.div>
        <motion.div
          className="absolute inset-0 flex items-center justify-center"
          initial={false}
          animate={{
            opacity: isPressed ? 0 : 1,
            scale: isPressed ? 0.8 : 1,
            rotate: isPressed ? 90 : 0,
          }}
          transition={{ duration: 0.2, ease: 'easeOut' }}
        >
          <LuPlus className="size-4" />
        </motion.div>
      </div>

      <span className="relative">{isPressed ? t('Requested') : t('Request Set')}</span>
    </BaseToggle>
  );
};
