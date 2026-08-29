<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Services\DeploymentCheckService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Le contrôle d'après-déploiement n'a d'intérêt que s'il voit les pannes
 * silencieuses. Chaque test ci-dessous reproduit une configuration qui laisse
 * l'application démarrer normalement tout en cassant une de ses fonctions.
 */
class DeploymentCheckTest extends TestCase
{
    use RefreshDatabase;

    private string $defaultConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultConnection = (string) config('database.default');

        // Une base sans formule est un vrai point bloquant — c'est justement
        // ce que le contrôle signale. Ces tests portent sur autre chose : on
        // part donc d'une installation complète.
        Plan::factory()->create();

        // La suite tourne avec MAIL_MAILER=array, qui est précisément une des
        // configurations que le contrôle refuse. Les tests qui ne portent pas
        // sur l'e-mail partent donc d'un expéditeur plausible.
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => 'smtp.hebergeur.invalid',
            'mail.from.address' => 'contact@smartlink.cm',
        ]);
    }

    private function statusOf(string $name): ?string
    {
        foreach (app(DeploymentCheckService::class)->run() as $check) {
            if ($check['name'] === $name) {
                return $check['status'];
            }
        }

        return null;
    }

    private function pretendServerless(): void
    {
        putenv('VERCEL=1');
    }

    /**
     * Les contrôles de supervision ne portent que sur la production : en local,
     * des journaux dans un fichier et aucune alerte sont le bon réglage.
     */
    private function identiteLegaleComplete(): void
    {
        $cles = array_keys((array) config('legal.editeur'));

        config(['legal.editeur' => array_combine($cles, array_map(fn (string $c) => 'valeur '.$c, $cles))]);
    }

    private function pretendProduction(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
    }

    protected function tearDown(): void
    {
        putenv('VERCEL');

        // RefreshDatabase ouvre sa transaction sur la connexion par défaut au
        // démarrage et la referme au démontage. Un test qui bascule la
        // connexion par défaut la laisserait ouverte sur l'ancienne, et le
        // test suivant échouerait sur « cannot start a transaction within a
        // transaction » — une panne sans rapport avec ce qu'il vérifie.
        config(['database.default' => $this->defaultConnection]);

        parent::tearDown();
    }

    public function test_an_empty_plans_table_is_an_error(): void
    {
        Plan::query()->forceDelete();

        $this->assertSame(DeploymentCheckService::ERROR, $this->statusOf('Formules'));
    }

    public function test_a_zero_day_cycle_is_an_error(): void
    {
        // Ce qu'on obtient quand la variable existe sur l'hébergeur mais vide :
        // (int) '' vaut zéro, et l'abonnement payé ne couvre rien.
        config(['subscription.cycle_days' => 0]);

        $this->assertSame(DeploymentCheckService::ERROR, $this->statusOf('SUBSCRIPTION_CYCLE_DAYS'));
    }

    public function test_a_relative_payment_url_is_an_error(): void
    {
        config([
            'payment.driver' => 'hrskills',
            'payment.hrskills.webhook_secret' => 'un-secret',
            'payment.hrskills.base_url' => '',
        ]);

        $this->assertSame(DeploymentCheckService::ERROR, $this->statusOf('Paiement'));
    }

    public function test_an_absolute_payment_url_passes(): void
    {
        config([
            'payment.driver' => 'hrskills',
            'payment.hrskills.webhook_secret' => 'un-secret',
            'payment.hrskills.base_url' => 'https://api.hrskills-pay.com',
        ]);

        $this->assertSame(DeploymentCheckService::OK, $this->statusOf('Paiement'));
    }

    public function test_a_migration_pushed_but_never_applied_is_an_error(): void
    {
        // On efface la trace de la dernière migration : elle redevient « en
        // attente » sans que le schéma bouge, exactement comme après un
        // déploiement où personne n'a lancé migrate.
        $derniere = DB::table('migrations')->orderByDesc('id')->first();
        DB::table('migrations')->where('id', $derniere->id)->delete();

        $this->assertSame(DeploymentCheckService::ERROR, $this->statusOf('Migrations en attente'));
    }

    public function test_an_up_to_date_schema_passes(): void
    {
        $this->assertSame(DeploymentCheckService::OK, $this->statusOf('Migrations en attente'));
    }

    public function test_an_unencrypted_postgres_connection_is_an_error(): void
    {
        config([
            'database.default' => 'pgsql',
            'database.connections.pgsql.host' => 'inexistant.invalid',
            'database.connections.pgsql.sslmode' => 'prefer',
        ]);

        $this->assertSame(DeploymentCheckService::ERROR, $this->statusOf('DB_SSLMODE'));
    }

    public function test_a_required_ssl_mode_passes(): void
    {
        config([
            'database.default' => 'pgsql',
            'database.connections.pgsql.host' => 'inexistant.invalid',
            'database.connections.pgsql.sslmode' => 'require',
        ]);

        $this->assertSame(DeploymentCheckService::OK, $this->statusOf('DB_SSLMODE'));
    }

    public function test_a_direct_postgres_host_is_flagged_on_a_serverless_host(): void
    {
        $this->pretendServerless();
        config([
            'database.default' => 'pgsql',
            'database.connections.pgsql.sslmode' => 'require',
            'database.connections.pgsql.host' => 'db.abcdef.supabase.invalid',
        ]);

        $this->assertSame(DeploymentCheckService::WARNING, $this->statusOf('Mutualisation'));
    }

    public function test_a_pooled_postgres_host_is_not_flagged(): void
    {
        $this->pretendServerless();
        config([
            'database.default' => 'pgsql',
            'database.connections.pgsql.sslmode' => 'require',
            'database.connections.pgsql.host' => 'aws-0-eu-west-3.pooler.supabase.invalid',
        ]);

        $this->assertNull($this->statusOf('Mutualisation'));
    }

    public function test_the_transport_checks_stay_silent_on_other_engines(): void
    {
        config(['database.default' => 'sqlite']);

        $this->assertNull($this->statusOf('DB_SSLMODE'));
        $this->assertNull($this->statusOf('Mutualisation'));
    }

    public function test_a_local_media_disk_is_an_error_on_a_serverless_host(): void
    {
        $this->pretendServerless();
        config(['filesystems.media' => 'public']);

        $this->assertSame(DeploymentCheckService::ERROR, $this->statusOf('MEDIA_DISK'));
    }

    public function test_a_local_media_disk_is_fine_on_a_classic_host(): void
    {
        config(['filesystems.media' => 'public']);

        $this->assertSame(DeploymentCheckService::OK, $this->statusOf('MEDIA_DISK'));
    }

    public function test_a_queued_connection_is_an_error_without_a_worker(): void
    {
        $this->pretendServerless();
        config(['queue.default' => 'database']);

        $this->assertSame(DeploymentCheckService::ERROR, $this->statusOf('QUEUE_CONNECTION'));
    }

    public function test_a_sync_queue_passes_on_a_serverless_host(): void
    {
        $this->pretendServerless();
        config(['queue.default' => 'sync']);

        $this->assertSame(DeploymentCheckService::OK, $this->statusOf('QUEUE_CONNECTION'));
    }

    public function test_a_missing_cron_secret_is_an_error_on_a_serverless_host(): void
    {
        $this->pretendServerless();
        config(['cron.secret' => null]);

        $this->assertSame(DeploymentCheckService::ERROR, $this->statusOf('CRON_SECRET'));
    }

    public function test_an_s3_disk_without_a_public_url_is_flagged(): void
    {
        $this->pretendServerless();
        config([
            'filesystems.media' => 's3',
            'filesystems.disks.s3.url' => null,
        ]);

        $this->assertSame(DeploymentCheckService::WARNING, $this->statusOf('MEDIA_DISK'));
    }

    public function test_a_fully_configured_serverless_host_passes(): void
    {
        $this->pretendServerless();
        config([
            'filesystems.media' => 's3',
            'filesystems.disks.s3.url' => 'https://cdn.example.com',
            // Les pièces d'identité ont leur propre seau, fermé : un disque
            // local les perdrait au déploiement suivant, et la vérification
            // des prestataires s'arrêterait sans erreur visible.
            'filesystems.id_documents' => 's3_id_documents',
            'queue.default' => 'sync',
            'cron.secret' => 'un-secret',
            'logging.default' => 'stderr',
            // Sans effet quand la suite tourne sur SQLite ; indispensable
            // quand elle tourne sur PostgreSQL, où le transport est contrôlé.
            'database.connections.pgsql.sslmode' => 'require',
        ]);

        $checks = app(DeploymentCheckService::class)->run();

        $this->assertFalse(app(DeploymentCheckService::class)->hasErrors($checks));
    }

    /**
     * Le disque des pièces d'identité ne doit jamais être celui des images :
     * ce dernier est public, donc servi sans passer par Laravel.
     */
    public function test_id_documents_sharing_the_public_media_disk_is_an_error(): void
    {
        config([
            'filesystems.media' => 'public',
            'filesystems.id_documents' => 'public',
        ]);

        $this->assertSame(DeploymentCheckService::ERROR, $this->statusOf('ID_DOCUMENTS_DISK'));
    }

    public function test_a_local_id_documents_disk_is_an_error_on_a_serverless_host(): void
    {
        $this->pretendServerless();
        config(['filesystems.id_documents' => 'id_documents']);

        $this->assertSame(DeploymentCheckService::ERROR, $this->statusOf('ID_DOCUMENTS_DISK'));
    }

    public function test_the_health_route_refuses_a_call_without_the_secret(): void
    {
        config(['cron.secret' => 'un-secret']);

        $this->getJson(route('cron.health'))->assertForbidden();
    }

    public function test_the_health_route_reports_the_checks_with_the_secret(): void
    {
        config([
            'cron.secret' => 'un-secret',
            'database.connections.pgsql.sslmode' => 'require',
        ]);

        $response = $this->getJson(route('cron.health'), [
            'Authorization' => 'Bearer un-secret',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'serverless',
            'status',
            'checks' => [['name', 'status', 'message']],
        ]);
    }

    public function test_the_health_route_answers_500_when_something_blocks(): void
    {
        $this->pretendServerless();
        config([
            'cron.secret' => 'un-secret',
            'filesystems.media' => 'public',
        ]);

        $this->getJson(route('cron.health'), ['Authorization' => 'Bearer un-secret'])
            ->assertStatus(500)
            ->assertJson(['status' => 'failed', 'serverless' => true]);
    }

    /**
     * Le défaut de Laravel écrit les messages dans un fichier au lieu de les
     * envoyer. Rien ne casse, le formulaire affiche « lien envoyé », et
     * l'utilisateur qui a perdu son mot de passe ne reçoit jamais rien : c'est
     * la seule voie de récupération d'un compte.
     */
    public function test_a_mailer_that_sends_nothing_is_an_error_in_production(): void
    {
        $this->pretendProduction();

        foreach (['log', 'array', 'null'] as $pilote) {
            config(['mail.default' => $pilote]);

            $this->assertSame(
                DeploymentCheckService::ERROR,
                $this->statusOf('MAIL_MAILER'),
                "Le pilote « {$pilote} » n'envoie rien et doit bloquer en production.",
            );
        }
    }

    /**
     * Hors production, écrire les messages dans un fichier est le bon réglage.
     * Le signaler comme bloquant ferait sortir `deploy:check` en échec sur
     * toutes les machines de développement — et on apprendrait vite à ne plus
     * lire sa sortie.
     */
    public function test_the_same_mailer_is_only_a_warning_outside_production(): void
    {
        config(['mail.default' => 'log']);

        $this->assertSame(DeploymentCheckService::WARNING, $this->statusOf('MAIL_MAILER'));
    }

    public function test_an_smtp_mailer_without_a_host_is_an_error(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => '',
        ]);

        $this->assertSame(DeploymentCheckService::ERROR, $this->statusOf('MAIL_MAILER'));
    }

    /**
     * L'adresse d'exemple livrée dans .env.example : les messages partent, mais
     * d'un domaine qui n'appartient à personne — ils finissent en indésirables.
     */
    public function test_the_example_sender_address_is_flagged(): void
    {
        config(['mail.from.address' => 'hello@example.com']);

        $this->assertSame(DeploymentCheckService::WARNING, $this->statusOf('MAIL_FROM_ADDRESS'));
    }

    public function test_a_real_smtp_configuration_passes(): void
    {
        $this->assertSame(DeploymentCheckService::OK, $this->statusOf('MAIL_MAILER'));
    }

    public function test_no_alert_destination_is_flagged_in_production(): void
    {
        $this->pretendProduction();
        config(['logging.default' => 'stderr', 'logging.channels.slack.url' => null]);

        $this->assertSame(DeploymentCheckService::WARNING, $this->statusOf('Alertes'));
    }

    public function test_an_alert_destination_passes(): void
    {
        $this->pretendProduction();
        config([
            'logging.default' => 'stderr',
            'logging.channels.slack.url' => 'https://hooks.slack.invalid/services/…',
        ]);

        $this->assertSame(DeploymentCheckService::OK, $this->statusOf('Alertes'));
    }

    /**
     * Sur Vercel, un journal écrit dans l'arborescence disparaît au
     * déploiement suivant — et il n'y a alors plus aucune trace des pannes.
     */
    public function test_a_file_log_channel_is_flagged_on_a_serverless_host(): void
    {
        $this->pretendProduction();
        $this->pretendServerless();
        config(['logging.default' => 'daily']);

        $this->assertSame(DeploymentCheckService::WARNING, $this->statusOf('Journaux'));
    }

    public function test_the_observability_checks_stay_silent_outside_production(): void
    {
        config(['logging.default' => 'single', 'logging.channels.slack.url' => null]);

        $this->assertNull($this->statusOf('Alertes'));
        $this->assertNull($this->statusOf('Journaux'));
    }

    /**
     * Les mentions légales figurent sur les statuts de la société : personne
     * ne peut les deviner, et l'application démarre parfaitement sans. Sans ce
     * contrôle, leur absence ne se remarque que le jour où quelqu'un les
     * cherche.
     */
    public function test_missing_legal_details_are_flagged_in_production(): void
    {
        $this->pretendProduction();
        config(['legal.editeur.rccm' => '', 'legal.editeur.siege' => null]);

        $this->assertSame(DeploymentCheckService::WARNING, $this->statusOf('Mentions légales'));
    }

    /**
     * Le piège que ce contrôle double : le bandeau de la page ne s'affichait
     * que si *toutes* les mentions manquaient. Ici, une seule manque.
     */
    public function test_a_single_missing_detail_is_enough_to_flag(): void
    {
        $this->pretendProduction();
        $this->identiteLegaleComplete();
        config(['legal.editeur.niu' => '']);

        $this->assertSame(DeploymentCheckService::WARNING, $this->statusOf('Mentions légales'));
    }

    public function test_complete_and_reviewed_legal_details_pass(): void
    {
        $this->pretendProduction();
        $this->identiteLegaleComplete();
        config(['legal.valide_juridiquement' => true]);

        $this->assertSame(DeploymentCheckService::OK, $this->statusOf('Mentions légales'));
    }

    /**
     * Complètes mais non relues : les pages portent une mention visible du
     * public, qui doit se voir aussi dans le contrôle.
     */
    public function test_unreviewed_documents_are_still_flagged(): void
    {
        $this->pretendProduction();
        $this->identiteLegaleComplete();
        config(['legal.valide_juridiquement' => false]);

        $this->assertSame(DeploymentCheckService::WARNING, $this->statusOf('Mentions légales'));
    }
}
