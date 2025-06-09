<?php

namespace App\Factory;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @extends PersistentProxyObjectFactory<User>
 */
final class UserFactory extends PersistentProxyObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     * 
     */

    private static ?string $hashedPassword = null;

    /**
     * @param UserPasswordHasherInterface $passwordHasher
     */
    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();

        // Hash the password once and store it for reuse
        if (self::$hashedPassword === null) {
            self::$hashedPassword = $passwordHasher->hashPassword(new User(), 'password');
        }
    }

    public static function class(): string
    {
        return User::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {

        $frenchCities = [
            'Paris',
            'Marseille',
            'Lyon',
            'Toulouse',
            'Nice',
            'Nantes',
            'Strasbourg',
            'Montpellier',
            'Bordeaux',
            'Lille',
            'Rennes',
            'Reims',
            'Le Havre',
            'Saint-Étienne',
            'Toulon',
            'Grenoble',
            'Dijon',
            'Angers',
            'Nîmes',
            'Villeurbanne'
        ];

        $firstName = self::faker()->firstName();
        $lastName = self::faker()->lastName();
        $domain = self::faker()->freeEmailDomain(); // gives domains like gmail.com, yahoo.fr, etc.

        return [
            'city' => $frenchCities[array_rand($frenchCities)],
            'email' => strtolower($firstName) . '.' . strtolower($lastName) . '@' . $domain,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'password' => self::$hashedPassword,
            'roles' => ['ROLE_USER'],
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(User $user): void {})
        ;
    }

}
