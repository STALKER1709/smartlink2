<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Les fichiers déposés doivent pouvoir changer de disque sans toucher aux
 * vues : c'est ce qui rend l'hébergement serverless possible, où le disque
 * local ne survit pas d'une requête à l'autre.
 */
class MediaHelperTest extends TestCase
{
    public function test_it_falls_back_to_the_public_disk(): void
    {
        config(['filesystems.media' => null]);

        $this->assertSame('public', media_disk());
    }

    public function test_it_follows_the_configured_disk(): void
    {
        config(['filesystems.media' => 's3']);

        $this->assertSame('s3', media_disk());
    }

    public function test_it_builds_the_url_on_the_configured_disk(): void
    {
        Storage::fake('media-de-test');
        config(['filesystems.media' => 'media-de-test']);

        $url = media_url('logos/exemple.png');

        $this->assertNotNull($url);
        $this->assertStringContainsString('logos/exemple.png', $url);
    }

    public function test_it_returns_null_for_an_empty_path(): void
    {
        $this->assertNull(media_url(null));
        $this->assertNull(media_url(''));
    }

    /**
     * Un disque S3 sans seau fait lever le SDK — « The GetObject operation
     * requires non-empty parameter: Bucket » — au moment où une vue demande
     * l'URL d'une image. L'exception part donc au milieu d'un rendu : la page
     * entière devient une erreur 500 pour une vignette. C'est arrivé en
     * production, et l'accueil, le catalogue et l'annuaire sont tombés
     * ensemble pendant que la connexion répondait encore.
     *
     * Le réglage reste fautif — `deploy:check` le refuse — mais son coût est
     * borné à ce qu'il est vraiment : une image qui manque.
     */
    public function test_a_misconfigured_disk_costs_an_image_and_not_the_page(): void
    {
        config([
            'filesystems.media' => 's3',
            'filesystems.disks.s3' => [
                'driver' => 's3',
                'key' => '',
                'secret' => '',
                'region' => '',
                'bucket' => '',
            ],
        ]);

        $this->assertNull(media_url('logos/exemple.png'));
    }
}
