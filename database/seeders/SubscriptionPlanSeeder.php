<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use App\Models\SubscriptionPlanLimit;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run()
    {
        // Define default plans
        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'Basic access to view courses and tests',
                'price' => 0.00,
                'currency' => 'USD',
                'priority' => 1,
                'is_active' => true,
                'features' => [
                    'courses.view',
                    'tests.view',
                ],
                'limits' => [
                    'courses' => 0,
                    'sessions' => 0,
                    'groups' => 0,
                    'packs' => 0,
                    'participants_per_session' => 0,
                ],
            ],
            [
                'name' => 'Basic',
                'slug' => 'basic',
                'description' => 'Perfect for getting started with course creation',
                'price' => 9.99,
                'currency' => 'USD',
                'priority' => 2,
                'is_active' => true,
                'features' => [
                    'courses.view',
                    'courses.create',
                    'courses.edit',
                    'tests.view',
                    'sessions.view',
                    'groups.view',
                    'chapters.manage',
                    'lessons.manage',
                ],
                'limits' => [
                    'courses' => 3,
                    'sessions' => 5,
                    'groups' => 2,
                    'packs' => 1,
                    'participants_per_session' => 20,
                ],
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => 'For professional instructors who need more power',
                'price' => 29.99,
                'currency' => 'USD',
                'priority' => 3,
                'is_active' => true,
                'features' => [
                    'courses.view',
                    'courses.create',
                    'courses.edit',
                    'courses.publish',
                    'tests.view',
                    'tests.create',
                    'tests.edit',
                    'sessions.view',
                    'sessions.manage',
                    'groups.view',
                    'groups.manage',
                    'packs.view',
                    'packs.create',
                    'packs.edit',
                    'chapters.manage',
                    'lessons.manage',
                    'resources.manage',
                    'meeting-requests.view',
                    'meeting-requests.manage',
                ],
                'limits' => [
                    'courses' => 15,
                    'sessions' => 30,
                    'groups' => 10,
                    'packs' => 5,
                    'participants_per_session' => 100,
                ],
            ],
            [
                'name' => 'Ultimate',
                'slug' => 'ultimate',
                'description' => 'Unlimited access to all features',
                'price' => 99.99,
                'currency' => 'USD',
                'priority' => 4,
                'is_active' => true,
                'features' => [
                    'courses.view',
                    'courses.create',
                    'courses.edit',
                    'courses.publish',
                    'courses.delete',
                    'tests.view',
                    'tests.create',
                    'tests.edit',
                    'tests.delete',
                    'tests.evaluate',
                    'sessions.view',
                    'sessions.manage',
                    'groups.view',
                    'groups.manage',
                    'packs.view',
                    'packs.create',
                    'packs.edit',
                    'packs.publish',
                    'packs.delete',
                    'chapters.manage',
                    'lessons.manage',
                    'resources.manage',
                    'analytics.view',
                    'payments.view',
                    'meeting-requests.view',
                    'meeting-requests.manage',
                ],
                'limits' => [
                    'courses' => -1, // Unlimited
                    'sessions' => -1,
                    'groups' => -1,
                    'packs' => -1,
                    'participants_per_session' => -1,
                ],
            ],
        ];

        foreach ($plans as $planData) {
            $limits = $planData['limits'];
            unset($planData['limits']);

            $plan = SubscriptionPlan::create($planData);

            // Create limits
            foreach ($limits as $resourceType => $limitValue) {
                SubscriptionPlanLimit::create([
                    'subscription_plan_id' => $plan->id,
                    'resource_type' => $resourceType,
                    'limit_value' => $limitValue,
                ]);
            }
        }

        // Assign Free plan to all existing users without a subscription
        $freePlan = SubscriptionPlan::where('slug', 'free')->first();
        
        if ($freePlan) {
            $usersWithoutSubscription = User::whereNull('current_subscription_id')->get();

            foreach ($usersWithoutSubscription as $user) {
                $subscription = UserSubscription::create([
                    'user_id' => $user->id,
                    'subscription_plan_id' => $freePlan->id,
                    'status' => 'active',
                    'started_at' => now(),
                ]);

                $user->update([
                    'current_subscription_id' => $subscription->id,
                ]);
            }

            $this->command->info("Assigned Free plan to {$usersWithoutSubscription->count()} existing users.");
        }

        $this->command->info('Subscription plans seeded successfully!');
    }
}
