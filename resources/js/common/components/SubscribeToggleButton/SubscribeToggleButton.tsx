import { type FC, type ReactNode, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { LuBell, LuBellOff } from 'react-icons/lu';

import { useToggleSubscriptionMutation } from '@/common/hooks/mutations/useToggleSubscriptionMutation';
import { cn } from '@/common/utils/cn';
import type { TranslatedString } from '@/types/i18next';

import { BaseButton } from '../+vendor/BaseButton';
import { toastMessage } from '../+vendor/BaseToaster';

/**
 * An `existingSubscription` might contain duplicative
 * `subjectId` and `subjectType` values, but we can't rely
 * on an existing subscription being present. Without these
 * values lifted out of the `Subscription` model, we won't have
 * anything to go by for users who have never subscribed to the
 * entity.
 */

interface SubscribeToggleButtonProps {
  hasExistingSubscription: boolean;
  subjectId: number;
  subjectType: App.Community.Enums.SubscriptionSubjectType;

  className?: string;
  extraIconSlot?: ReactNode;
  label?: TranslatedString;
}

export const SubscribeToggleButton: FC<SubscribeToggleButtonProps> = ({
  className,
  extraIconSlot,
  hasExistingSubscription,
  label,
  subjectId,
  subjectType,
}) => {
  const { t } = useTranslation();

  const mutation = useToggleSubscriptionMutation();

  const [isSubscribed, setIsSubscribed] = useState(hasExistingSubscription);

  const finalLabel = label ?? (isSubscribed ? t('Unsubscribe') : t('Subscribe'));

  const handleClick = () => {
    const newState = !isSubscribed;

    toastMessage.promise(
      mutation.mutateAsync({ subjectId, subjectType, newState: !isSubscribed }),
      {
        loading: newState === true ? t('Subscribing...') : t('Unsubscribing...'),
        success: () => {
          setIsSubscribed(newState);

          return newState === true ? t('Subscribed!') : t('Unsubscribed!');
        },
        error: t('Something went wrong.'),
      },
    );
  };

  return (
    <BaseButton
      size="sm"
      onClick={handleClick}
      className={cn('gap-1.5', className)}
      aria-label={finalLabel}
    >
      <span className="flex items-center gap-0.5">
        {isSubscribed ? (
          <LuBellOff className="size-4" aria-label="click to unsubscribe" />
        ) : (
          <LuBell className="size-4" aria-label="click to subscribe" />
        )}

        {extraIconSlot}
      </span>

      <span className="hidden sm:block">{finalLabel}</span>
    </BaseButton>
  );
};
