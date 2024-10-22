import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { FC } from 'react';

import { HomeHeading } from '../../HomeHeading';
import { SeeMoreLink } from '../../SeeMoreLink';

// TODO track click and position when clicked

export const FrontPageNews: FC = () => {
  const { t } = useLaravelReactI18n();

  return (
    <div className="flex flex-col gap-2.5">
      <HomeHeading>{t('News')}</HomeHeading>

      <div className="grid grid-cols-2 gap-4 sm:flex sm:flex-col sm:gap-1">
        <NewsCard
          authorDisplayName="SporyTike"
          href="#"
          imageSrc="https://media.retroachievements.org/Images/103356.jpg"
          postedAt="2d ago"
          title="New Guide: Dragon Warrior II"
          lead="A new achievement guide has been published for Dragon Warrior II, written by Nepiki! Continue the Quest and fulfill the legend."
          tagLabel="Guide"
        />

        <NewsCard
          authorDisplayName="Scott"
          href="#"
          imageSrc="https://media.retroachievements.org/Images/049881.jpg"
          postedAt="4d ago"
          title="Sets of the Month Voting - September 2024"
          lead="September 2024 is over and 130 sets, 3 subsets, and 8 revisions were made. Now it's your turn to vote for the best sets in multiple categories."
          tagLabel="Vote"
        />

        <div className="hidden sm:block">
          <NewsCard
            authorDisplayName="StingX2"
            href="#"
            imageSrc="https://s3-eu-west-1.amazonaws.com/i.retroachievements.org/Images/036134.jpg"
            postedAt="5d ago"
            title="September 2024 RANews"
            lead="September's issue of RANews is out! Check it out here, and please post any feedback in the forum topic."
            tagLabel="RANews"
          />
        </div>
      </div>

      <SeeMoreLink href="#" asClientSideRoute={true} />
    </div>
  );
};

interface NewsCardProps {
  authorDisplayName: string;
  href: string;
  imageSrc: string;
  lead: string;
  postedAt: string;
  title: string;

  tagLabel?: string;
}

const NewsCard: FC<NewsCardProps> = ({
  authorDisplayName,
  href,
  imageSrc,
  lead,
  postedAt,
  title,
}) => {
  const { t } = useLaravelReactI18n();

  return (
    <a
      href={href}
      className="group -mx-2 cursor-pointer gap-6 rounded-xl px-2 py-2 hover:bg-neutral-950/30 hover:light:bg-neutral-100 sm:flex"
    >
      <div className="relative h-28 w-full sm:w-[197px]">
        <div className="overflow-hidden rounded">
          <div
            className="h-28 w-full rounded object-cover sm:w-[197px]"
            style={{
              backgroundSize: 'cover',
              backgroundImage: `linear-gradient(297.68deg, rgba(0, 0, 0, 0.77) 3.95%, rgba(0, 0, 0, 0) 48.13%), url(${imageSrc})`,
            }}
          />
        </div>

        {/* {tagLabel ? (
          <div className="absolute bottom-2 right-2">
            <div className="flex h-[22px] select-none items-center justify-center rounded-full bg-neutral-50 px-2 font-bold text-zinc-900">
              {tagLabel}
            </div>
          </div>
        ) : null} */}
      </div>

      <div>
        <p className="mb-1 hidden text-2xs uppercase text-neutral-400/90 sm:block">
          {postedAt}{' '}
          <span className="normal-case italic">
            {'·'} {t('by :authorDisplayName', { authorDisplayName })}
          </span>
        </p>

        <p className="mb-2 mt-2 text-base sm:mt-0">{title}</p>
        <p className="line-clamp-3 text-text">{lead}</p>
      </div>
    </a>
  );
};
