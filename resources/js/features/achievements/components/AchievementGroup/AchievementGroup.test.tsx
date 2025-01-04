import { render, screen } from '@/test';
import { createAchievement, createAchievementGroup, createGame } from '@/test/factories';

import { AchievementGroup } from './AchievementGroup';

describe('Component: AchievementGroup', () => {
    it('renders without crashing', () => {
        // ARRANGE
        const group = createAchievementGroup();
        const { container } = render(<AchievementGroup group={group} />);

        // ASSERT
        expect(container).toBeTruthy();
    })

    it('displays the header', () => {
        // ARRANGE
        const group = createAchievementGroup({
            header: 'Creative Name',
        });

        render(<AchievementGroup group={group} />);

        // ASSERT
        expect(screen.getByText(/Creative Name/)).toBeVisible();
    });

    it('displays the achievements without games', () => {
        // ARRANGE
        const group = createAchievementGroup({
            achievements: [
                createAchievement({
                    'title': 'First Achievement',
                    'description': 'Do the first thing',
                    'game': createGame({ 'title': 'First Game' }),
                }),
                createAchievement({ 'title': 'Second Achievement', 'description': 'Do the second thing' }),
                createAchievement({ 'title': 'Third Achievement', 'description': 'Do the third thing' }),
            ]
        });

        render(<AchievementGroup group={group} />);

        // ASSERT
        expect(screen.getByText(/First Achievement/)).toBeVisible();
        expect(screen.queryByText(/First Game/)).toBeNull();
        expect(screen.getByText(/Do the first thing/)).toBeVisible();
        expect(screen.getByText(/Second Achievement/)).toBeVisible();
        expect(screen.getByText(/Do the second thing/)).toBeVisible();
        expect(screen.getByText(/Third Achievement/)).toBeVisible();
        expect(screen.getByText(/Do the third thing/)).toBeVisible();
    });

    it('displays the achievements with games', () => {
        // ARRANGE
        const group = createAchievementGroup({
            achievements: [
                createAchievement({
                    'title': 'First Achievement',
                    'description': 'Do the first thing',
                    'game': createGame({ 'title': 'First Game' }),
                }),
                createAchievement({ 'title': 'Second Achievement', 'description': 'Do the second thing' }),
                createAchievement({ 'title': 'Third Achievement', 'description': 'Do the third thing' }),
            ]
        });

        render(<AchievementGroup group={group} showGame={true} />);

        // ASSERT
        expect(screen.getByText(/First Achievement/)).toBeVisible();
        expect(screen.getByText(/First Game/)).toBeVisible();
        expect(screen.getByText(/Do the first thing/)).toBeVisible();
        expect(screen.getByText(/Second Achievement/)).toBeVisible();
        expect(screen.getByText(/Do the second thing/)).toBeVisible();
        expect(screen.getByText(/Third Achievement/)).toBeVisible();
        expect(screen.getByText(/Do the third thing/)).toBeVisible();
    });
});

