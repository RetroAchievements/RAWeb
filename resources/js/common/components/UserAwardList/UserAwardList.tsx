import type { FC } from 'react';
import React from 'react';

type UserAwardListProps = {
  headingLabel: string;
  headingCountSlot: React.ReactNode;
  awards: React.ReactNode[];
};

export const UserAwardList: FC<UserAwardListProps> = ({
  headingLabel,
  headingCountSlot,
  awards,
}) => {
  return (
    <div id={`user-award-${headingLabel.toLowerCase().replaceAll(' ', '')}`}>
      <h3 className="flex justify-between gap-2">
        <span className="grow">{headingLabel}</span>
        {headingCountSlot}
      </h3>
      <div className="component grid w-full grid-cols-[repeat(auto-fill,minmax(52px,52px))] place-content-center gap-2 bg-embed xl:rounded xl:py-2">
        {awards.map((award) => award)}
      </div>
    </div>
  );
};
