<?php

namespace App\DataFixtures;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use DateTimeImmutable;

class NotificationFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $demoUser = $manager->getRepository(User::class)->findOneBy(['email' => 'demo@wonderpayes.com']);

        if (!$demoUser) {
            return;
        }

        $notifications = [
            [
                'type' => 'booking',
                'title' => 'New Booking Confirmed',
                'message' => 'Your booking for Paris Tour has been confirmed',
                'description' => 'Get ready for an amazing experience in Paris! Your tour is scheduled for next week.',
                'details' => 'Tour date: 2024-03-15',
                'actionUrl' => '/bookings/details/1',
                'isRead' => false
            ],
            [
                'type' => 'system',
                'title' => 'Welcome to WonderPayes',
                'message' => 'Thank you for joining our community!',
                'description' => 'Explore our amazing tours and travel experiences.',
                'details' => 'Click to complete your profile',
                'actionUrl' => '/user/profile',
                'isRead' => true
            ],
            [
                'type' => 'promotion',
                'title' => 'Special Offer',
                'message' => 'Get 20% off on your next booking',
                'description' => 'Limited time offer for our valued customers',
                'details' => 'Use code: WONDER20',
                'actionUrl' => '/packages',
                'isRead' => false
            ],
            [
                'type' => 'booking',
                'title' => 'New Booking Confirmed',
                'message' => 'Your booking for Paris Tour has been confirmed',
                'description' => 'Get ready for an amazing experience in Paris! Your tour is scheduled for next week.',
                'details' => 'Tour date: 2024-03-15',
                'actionUrl' => '/bookings/details/1',
                'isRead' => false
            ],
            [
                'type' => 'offer',
                'title' => 'Special Discount!',
                'message' => '20% off on all European tours',
                'description' => 'Limited time offer! Book now and save on your dream European vacation.',
                'details' => 'Valid until: 2024-04-01',
                'actionUrl' => '/offers/europe',
                'isRead' => false
            ],
            [
                'type' => 'booking',
                'title' => 'Tour Guide Assigned',
                'message' => 'Meet your guide for the Rome Tour',
                'description' => 'Your tour guide Maria will meet you at the hotel lobby.',
                'details' => 'Meeting time: 09:00 AM',
                'actionUrl' => '/bookings/guide/2',
                'isRead' => true
            ],
        ];

        foreach ($notifications as $notificationData) {
            $notification = new Notification();
            $notification->setUser($demoUser);
            $notification->setType($notificationData['type']);
            $notification->setTitle($notificationData['title']);
            $notification->setMessage($notificationData['message']);
            $notification->setDescription($notificationData['description']);
            $notification->setDetails($notificationData['details']);
            $notification->setActionUrl($notificationData['actionUrl']);
            $notification->setIsRead($notificationData['isRead']);
            $notification->setCreatedAt(new DateTimeImmutable());

            $manager->persist($notification);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}