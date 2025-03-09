<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Create admin user
        $admin = new User();
        $admin->setEmail('admin@wonderpayes.com');
        $admin->setUsername('admin');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setNom('Admin');
        $admin->setPrenom('Super');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'Admin123!'));

        $manager->persist($admin);

        // Create demo user for notifications
        $demo = new User();
        $demo->setEmail('demo@wonderpayes.com');
        $demo->setUsername('demo');
        $demo->setRoles(['ROLE_USER']);
        $demo->setNom('Demo');
        $demo->setPrenom('User');
        $demo->setPassword($this->passwordHasher->hashPassword($demo, 'Demo123!'));
        
        $manager->persist($demo);

        // Create multiple client users
        $clients = [
            [
                'email' => 'jean.dupont@example.com',
                'username' => 'jean_dupont',
                'nom' => 'Dupont',
                'prenom' => 'Jean',
                'password' => 'Client123!'
            ],
            [
                'email' => 'marie.martin@example.com',
                'username' => 'marie_martin',
                'nom' => 'Martin',
                'prenom' => 'Marie',
                'password' => 'Client123!'
            ],
            [
                'email' => 'pierre.bernard@example.com',
                'username' => 'pierre_bernard',
                'nom' => 'Bernard',
                'prenom' => 'Pierre',
                'password' => 'Client123!'
            ],
            [
                'email' => 'sophie.petit@example.com',
                'username' => 'sophie_petit',
                'nom' => 'Petit',
                'prenom' => 'Sophie',
                'password' => 'Client123!'
            ],
            [
                'email' => 'lucas.moreau@example.com',
                'username' => 'lucas_moreau',
                'nom' => 'Moreau',
                'prenom' => 'Lucas',
                'password' => 'Client123!'
            ]
        ];

        foreach ($clients as $clientData) {
            $client = new User();
            $client->setEmail($clientData['email']);
            $client->setUsername($clientData['username']);
            $client->setRoles(['ROLE_USER']);
            $client->setNom($clientData['nom']);
            $client->setPrenom($clientData['prenom']);
            $client->setPassword($this->passwordHasher->hashPassword($client, $clientData['password']));
            $manager->persist($client);
        }
        $manager->flush();
    }
}