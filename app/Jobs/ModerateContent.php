<?php

namespace App\Jobs;

use App\Models\Review;
use App\Models\Service;
use App\Services\Ai\ContentModerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;

/**
 * La modération passe par la file d'attente : elle ne doit jamais retarder
 * la publication d'un service ni le dépôt d'un avis.
 */
class ModerateContent implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(private readonly Model $content) {}

    public function handle(ContentModerator $moderator): void
    {
        $text = $this->extractText();

        if ($text === null) {
            return;
        }

        $moderator->review($this->content, $text);
    }

    private function extractText(): ?string
    {
        return match (true) {
            $this->content instanceof Service => trim($this->content->title."\n\n".$this->content->description),
            $this->content instanceof Review => $this->content->comment,
            default => null,
        };
    }
}
