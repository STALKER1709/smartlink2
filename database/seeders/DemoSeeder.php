<?php

namespace Database\Seeders;

use App\Models\ClientProfile;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Plan;
use App\Models\ProviderProfile;
use App\Models\Review;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceImage;
use App\Models\ServiceRequest;
use App\Models\Subscription;
use App\Models\User;
use App\Services\QuotaService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Contenu de démonstration : de quoi ne pas ouvrir une plateforme vide.
 *
 * Trois garanties, parce que ce seeder est fait pour tourner sur la base de
 * production :
 *
 *   1. **Il n'écrit que sur son propre domaine.** Tous les comptes créés sont
 *      en `@demo.smartlink.cm` ; aucune ligne appartenant à un vrai compte
 *      n'est lue ni modifiée. `demo:clear` les retire tous d'un coup.
 *   2. **Il est rejouable.** Les comptes se retrouvent par leur adresse, les
 *      services par leur `slug` : le relancer met à jour au lieu de dupliquer.
 *   3. **Il ne crée aucun administrateur.** `DatabaseSeeder` en crée un dont
 *      le mot de passe vient de la fabrique — acceptable en développement,
 *      jamais en ligne. Ici, aucun compte privilégié n'est ouvert.
 *
 * Le mot de passe des comptes de démonstration se pose dans `DEMO_PASSWORD`.
 * Sans elle, il est tiré au sort et affiché une seule fois, à la fin : mieux
 * vaut un secret à recopier qu'un secret deviné.
 */
class DemoSeeder extends Seeder
{
    /** Le domaine qui distingue une donnée de démonstration d'une vraie. */
    public const DOMAIN = '@demo.smartlink.cm';

    /**
     * Préfixe des couvertures sur le disque de médias. Il tient à l'écart des
     * dépôts des prestataires, et donne à `demo:clear` de quoi les retrouver
     * sans risquer d'emporter une vraie photo.
     */
    public const IMAGE_PREFIX = 'services/demo/';

    /** Au moins un compte vient d'être créé : le mot de passe affiché est le sien. */
    private bool $nouveaux = false;

    /** @var array<string, string> catégorie → motif d'illustration */
    private array $motifs = [];

    public function run(): void
    {
        $donnees = require database_path('seeders/data/demo.php');

        // Un mot de passe tiré au sort à chaque exécution changerait celui de
        // comptes déjà en service, sans prévenir : il n'est posé qu'à la
        // création, ou quand DEMO_PASSWORD le demande explicitement.
        $impose = (string) (env('DEMO_PASSWORD') ?? '');
        $motDePasse = $impose !== '' ? $impose : Str::password(16, symbols: false);
        $hache = Hash::make($motDePasse);
        $ecrasePassword = $impose !== '';

        $this->motifs = $this->loadMotifs();
        $categories = ServiceCategory::query()->get()->keyBy('name');

        if ($categories->isEmpty()) {
            $this->command?->error('Aucune catégorie : lancer d\'abord db:seed --class=ServiceCategorySeeder.');

            return;
        }

        if (Plan::query()->active()->doesntExist()) {
            $this->command?->error('Aucun palier : lancer d\'abord db:seed --class=PlanSeeder.');

            return;
        }

        $prestataires = $this->seedProviders($donnees['providers'], $categories, $hache, $ecrasePassword);
        $clients = $this->seedClients($donnees['clients'], $hache, $ecrasePassword);
        $this->seedRequests($donnees['requests'], $prestataires, $clients);
        $this->recomputeRatings($prestataires);

        $quotas = app(QuotaService::class);

        foreach ($prestataires as $prestataire) {
            $quotas->refreshListing($prestataire->refresh());
        }

        $this->report($prestataires, $clients, $motDePasse, $this->nouveaux || $ecrasePassword);
    }

    /**
     * @return array<string, string>
     */
    private function loadMotifs(): array
    {
        $fichier = database_path('seeders/data/images/categories.json');

        if (! is_file($fichier)) {
            return [];
        }

        return json_decode(file_get_contents($fichier), true) ?: [];
    }

    /**
     * @param  array<int, array<string, mixed>>  $definitions
     * @return array<string, User>
     */
    private function seedProviders(array $definitions, $categories, string $hache, bool $ecrasePassword): array
    {
        $prestataires = [];

        foreach ($definitions as $definition) {
            $user = $this->upsertUser($definition, User::ROLE_PROVIDER, $hache, $ecrasePassword);

            $categorie = $categories->get($definition['category']);

            ProviderProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'category_id' => $categorie?->id,
                    'business_name' => $definition['business'],
                    'description' => $definition['description'],
                    'city' => $definition['city'],
                    'quarter' => $definition['quarter'],
                    'address' => $definition['quarter'].', '.$definition['city'],
                    'whatsapp' => $definition['phone'],
                    'service_areas' => [$definition['city']],
                    'opening_hours' => [
                        'lundi_vendredi' => '08:00 - 18:00',
                        'samedi' => '09:00 - 15:00',
                    ],
                    'contact_methods' => ['whatsapp' => $definition['phone']],
                    'is_verified' => $definition['verified'],
                ],
            );

            $this->seedSubscription($user, $definition['plan']);
            $this->seedServices($user, $categorie, $definition);

            $prestataires[$definition['email']] = $user;
        }

        return $prestataires;
    }

    /**
     * Répartit les prestataires sur tous les états d'abonnement possibles :
     * sans cela, l'essai, le palier gratuit et l'abonnement échu n'auraient
     * aucune donnée à montrer et ne se verraient jamais à l'écran.
     */
    private function seedSubscription(User $provider, string $etat): void
    {
        $palier = match ($etat) {
            'trial', 'pro' => Plan::query()->where('code', Plan::CODE_PRO)->first(),
            'free' => Plan::freePlan(),
            'expired' => Plan::query()->where('code', Plan::CODE_ESSENTIAL)->first(),
            default => Plan::query()->where('code', Plan::CODE_ESSENTIAL)->first(),
        } ?? Plan::query()->active()->orderBy('sort_order')->first();

        [$statut, $debut, $fin] = match ($etat) {
            'trial' => [Subscription::STATUS_TRIALING, now()->subDays(6), now()->addDays(24)],
            'expired' => [Subscription::STATUS_EXPIRED, now()->subDays(50), now()->subDays(9)],
            default => [Subscription::STATUS_ACTIVE, now()->subDays(11), now()->addDays(19)],
        };

        Subscription::updateOrCreate(
            ['user_id' => $provider->id],
            [
                'plan_id' => $palier?->id,
                'status' => $statut,
                'starts_at' => $debut,
                'ends_at' => $fin,
                'cancelled_at' => null,
                'last_reminder_day' => null,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function seedServices(User $provider, ?ServiceCategory $categorie, array $definition): void
    {
        foreach ($definition['services'] as $index => $service) {
            // Le slug est dérivé de l'adresse du prestataire et du rang du
            // service : stable d'une exécution à l'autre, donc rejouable, et
            // sans collision avec un service publié pour de bon.
            $slug = Str::slug($service['title']).'-demo-'
                .substr(md5($definition['email'].$index), 0, 6);

            $enregistre = Service::updateOrCreate(
                ['slug' => $slug],
                [
                    'provider_id' => $provider->id,
                    'category_id' => $categorie?->id,
                    'title' => $service['title'],
                    'description' => $service['description'],
                    'price_amount' => $service['price'],
                    'price_unit' => $service['unit'],
                    'location' => $definition['quarter'],
                    'city' => $definition['city'],
                    'quarter' => $definition['quarter'],
                    'is_available' => true,
                    'status' => Service::STATUS_ACTIVE,
                    'views_count' => 40 + (crc32($slug) % 460),
                ],
            );

            $this->attachCover($enregistre, $definition['category'], $index);
        }
    }

    /**
     * Couverture du service : une illustration du métier, téléversée sur le
     * disque de médias comme le serait une vraie photo.
     *
     * Elle passe par `media_disk()` et non par un chemin en dur : c'est ce qui
     * fait que les images atterrissent sur S3 en production et dans
     * `storage/app/public` en développement, sans que la vue ait à distinguer
     * les deux. Une illustration livrée avec le code et servie depuis
     * `public/` marcherait aussi, mais elle emprunterait un chemin que rien
     * d'autre n'emprunte — et ce chemin-là ne serait jamais éprouvé.
     */
    private function attachCover(Service $service, string $categorie, int $index): void
    {
        $motif = $this->motifs[$categorie] ?? null;

        if ($motif === null) {
            return;
        }

        $fichier = $motif.'-'.($index % 3).'.jpg';
        $source = database_path('seeders/data/images/'.$fichier);

        if (! is_file($source)) {
            return;
        }

        $chemin = self::IMAGE_PREFIX.$fichier;
        $disque = Storage::disk(media_disk());

        // Une seule copie par illustration : plusieurs services partagent le
        // même fichier, et le retéléverser à chaque exécution coûterait autant
        // d'allers-retours réseau que de services.
        if (! $disque->exists($chemin)) {
            $disque->put($chemin, file_get_contents($source));
        }

        ServiceImage::updateOrCreate(
            ['service_id' => $service->id, 'position' => 0],
            ['path' => $chemin],
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $definitions
     * @return array<string, User>
     */
    private function seedClients(array $definitions, string $hache, bool $ecrasePassword): array
    {
        $clients = [];

        foreach ($definitions as $definition) {
            $user = $this->upsertUser($definition, User::ROLE_CLIENT, $hache, $ecrasePassword);

            $morceaux = explode(' ', $definition['name'], 2);

            ClientProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'first_name' => $morceaux[0],
                    'last_name' => $morceaux[1] ?? '',
                    'city' => $definition['city'],
                ],
            );

            $clients[$definition['email']] = $user;
        }

        return $clients;
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function upsertUser(array $definition, string $role, string $hache, bool $ecrasePassword): User
    {
        $existant = User::withTrashed()->where('email', $definition['email'])->first();

        $attributs = [
            'name' => $definition['name'],
            'phone' => $definition['phone'],
            'role' => $role,
            'locale' => 'fr',
            'deleted_at' => null,
        ];

        if ($existant === null || $ecrasePassword) {
            $attributs['password'] = $hache;
        }

        if ($existant !== null) {
            $existant->forceFill($attributs)->save();

            return $existant;
        }

        $this->nouveaux = true;

        return User::create($attributs + ['email' => $definition['email']]);
    }

    /**
     * Demandes, conversations et avis. Une demande sans conversation donne
     * une messagerie vide : les deux vont ensemble, comme dans l'application.
     *
     * @param  array<int, array<string, mixed>>  $definitions
     * @param  array<string, User>  $prestataires
     * @param  array<string, User>  $clients
     */
    private function seedRequests(array $definitions, array $prestataires, array $clients): void
    {
        foreach ($definitions as $definition) {
            $provider = $prestataires[$definition['provider']] ?? null;
            $client = $clients[$definition['client']] ?? null;

            if ($provider === null || $client === null) {
                continue;
            }

            $service = $provider->services()->orderBy('id')->first();
            $creeLe = now()->subDays($definition['days_ago']);
            $repondu = in_array($definition['status'], [
                ServiceRequest::STATUS_ACCEPTED, ServiceRequest::STATUS_REFUSED,
                ServiceRequest::STATUS_IN_PROGRESS, ServiceRequest::STATUS_COMPLETED,
            ], true);

            $demande = ServiceRequest::updateOrCreate(
                [
                    'client_id' => $client->id,
                    'provider_id' => $provider->id,
                    'message' => $definition['message'],
                ],
                [
                    'service_id' => $service?->id,
                    'status' => $definition['status'],
                    'responded_at' => $repondu ? $creeLe->copy()->addHours(4) : null,
                    'completed_at' => $definition['status'] === ServiceRequest::STATUS_COMPLETED
                        ? $creeLe->copy()->addDays(2)
                        : null,
                    'created_at' => $creeLe,
                    'updated_at' => $creeLe,
                ],
            );

            $this->seedConversation($demande, $client, $provider, $definition, $creeLe);

            if (isset($definition['review']) && $definition['status'] === ServiceRequest::STATUS_COMPLETED) {
                Review::updateOrCreate(
                    ['request_id' => $demande->id],
                    [
                        'client_id' => $client->id,
                        'provider_id' => $provider->id,
                        'rating' => $definition['review']['rating'],
                        'comment' => $definition['review']['comment'],
                        'created_at' => $creeLe->copy()->addDays(3),
                        'updated_at' => $creeLe->copy()->addDays(3),
                    ],
                );
            }
        }
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function seedConversation(
        ServiceRequest $demande,
        User $client,
        User $provider,
        array $definition,
        $creeLe,
    ): void {
        $conversation = Conversation::updateOrCreate(
            ['request_id' => $demande->id],
            [
                'client_id' => $client->id,
                'provider_id' => $provider->id,
                'last_message_at' => $creeLe,
                'created_at' => $creeLe,
            ],
        );

        $echanges = [[$client->id, $definition['message'], $creeLe]];

        if (isset($definition['reply'])) {
            $echanges[] = [$provider->id, $definition['reply'], $creeLe->copy()->addHours(4)];
        }

        foreach ($echanges as [$expediteur, $corps, $envoyeLe]) {
            Message::updateOrCreate(
                ['conversation_id' => $conversation->id, 'sender_id' => $expediteur, 'body' => $corps],
                ['created_at' => $envoyeLe, 'updated_at' => $envoyeLe],
            );
        }

        $conversation->forceFill(['last_message_at' => end($echanges)[2]])->save();
    }

    /**
     * La moyenne et le nombre d'avis sont stockés sur la fiche du prestataire,
     * pas recalculés à l'affichage : c'est `ReviewController` qui les met à
     * jour à chaque avis déposé. Les insérer sans refaire ce calcul laisserait
     * toutes les fiches à zéro étoile malgré les avis visibles.
     *
     * @param  array<string, User>  $prestataires
     */
    private function recomputeRatings(array $prestataires): void
    {
        foreach ($prestataires as $provider) {
            $stats = $provider->reviewsReceived()->visible();

            $provider->providerProfile?->forceFill([
                'rating_avg' => round($stats->avg('rating') ?? 0, 2),
                'rating_count' => $stats->count(),
            ])->save();
        }
    }

    /**
     * @param  array<string, User>  $prestataires
     * @param  array<string, User>  $clients
     */
    private function report(array $prestataires, array $clients, string $motDePasse, bool $nouveaux): void
    {
        $this->command?->info(sprintf(
            '%d prestataires, %d clients, %d services, %d demandes, %d avis.',
            count($prestataires),
            count($clients),
            Service::query()->whereIn('provider_id', array_map(fn (User $u) => $u->id, $prestataires))->count(),
            ServiceRequest::query()->whereIn('provider_id', array_map(fn (User $u) => $u->id, $prestataires))->count(),
            Review::query()->whereIn('provider_id', array_map(fn (User $u) => $u->id, $prestataires))->count(),
        ));

        $this->command?->newLine();
        $this->command?->line('Comptes de démonstration (tous en '.self::DOMAIN.') :');
        $this->command?->line('  prestataire  serge.ndongo'.self::DOMAIN);
        $this->command?->line('  client       aissatou.bello'.self::DOMAIN);
        $this->command?->line('  mot de passe '.($nouveaux ? $motDePasse : '(inchangé — poser DEMO_PASSWORD pour le remplacer)'));
        $this->command?->newLine();
        $this->command?->comment('Aucun compte administrateur n\'a été créé.');
        $this->command?->comment('Pour tout retirer : php artisan demo:clear');
    }
}
