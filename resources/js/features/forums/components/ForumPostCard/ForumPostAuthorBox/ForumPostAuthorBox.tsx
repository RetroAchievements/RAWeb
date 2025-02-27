import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { UserAvatar } from '@/common/components/UserAvatar';
import { formatDate } from '@/common/utils/l10n/formatDate';
import type { TranslationKey } from '@/types/i18next';

interface ForumPostAuthorBoxProps {
  comment?: App.Data.ForumTopicComment;
}

export const ForumPostAuthorBox: FC<ForumPostAuthorBoxProps> = ({ comment }) => {
  const { t } = useTranslation();

  const author = comment?.user as App.Data.User;

  return (
    <div className="border-b border-neutral-700 px-0.5 pb-2 lg:border-b-0 lg:border-r lg:py-2">
      {author ? (
        <div className="flex w-full items-center lg:w-44 lg:flex-col lg:text-center">
          <UserAvatar {...author} showLabel={false} size={72} />

          <div className="ml-2 items-center lg:ml-0 lg:flex lg:flex-col">
            <div className="mb-0.5 lg:mt-1">
              <UserAvatar {...author} showImage={false} />
            </div>

            {author.visibleRole ? (
              <p data-testid="visible-role" className="smalltext !text-xs !leading-4 lg:!text-2xs">
                {t(author.visibleRole.name as TranslationKey)}
              </p>
            ) : null}

            {author.createdAt && !author.deletedAt ? (
              <p className="smalltext !text-xs !leading-4 lg:!text-2xs">
                {t('Joined {{joinDate}}', { joinDate: formatDate(author.createdAt, 'll') })}
              </p>
            ) : null}
          </div>
        </div>
      ) : (
        <div
          data-testid="no-author"
          className="flex w-full items-center lg:w-44 lg:flex-col lg:text-center"
        />
      )}
    </div>
  );
};
