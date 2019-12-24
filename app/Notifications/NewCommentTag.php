<?php
/**
 * NOTICE OF LICENSE.
 *
 * UNIT3D is open-sourced software licensed under the GNU Affero General Public License v3.0
 * The details is bundled with this project in the file LICENSE.txt.
 *
 * @project    UNIT3D
 *
 * @license    https://www.gnu.org/licenses/agpl-3.0.en.html/ GNU Affero General Public License v3.0
 * @author     HDVinnie, singularity43
 */

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class NewCommentTag extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @var string
     */
    public string $type;

    /**
     * @var string
     */
    public string $tagger;

    /**
     * @var \App\Models\Comment
     */
    public Comment $comment;
    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    private Repository $configRepository;

    /**
     * Create a new notification instance.
     *
     * @param  string  $type
     * @param  string  $tagger
     * @param  Comment  $comment
     * @param  \Illuminate\Contracts\Config\Repository  $configRepository
     */
    public function __construct(string $type, string $tagger, Comment $comment, Repository $configRepository)
    {
        $this->type = $type;
        $this->comment = $comment;
        $this->tagger = $tagger;
        $this->configRepository = $configRepository;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return string[]
     */
    public function via($notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return string[]
     */
    public function toArray($notifiable): array
    {
        $appurl = $this->configRepository->get('app.url');

        if ($this->type == 'torrent') {
            return [
                'title' => $this->tagger.' Has Tagged You In A Torrent Comment',
                'body' => $this->tagger.' has tagged you in a Comment for Torrent '.$this->comment->torrent->name,
                'url' => sprintf('/torrents/%s', $this->comment->torrent->id),
            ];
        } elseif ($this->type == 'request') {
            return [
                'title' => $this->tagger.' Has Tagged You In A Request Comment',
                'body' => $this->tagger.' has tagged you in a Comment for Request '.$this->comment->request->name,
                'url' => sprintf('/requests/%s', $this->comment->request->id),
            ];
        }

        return [
            'title' => $this->tagger.' Has Tagged You In An Article Comment',
            'body' => $this->tagger.' has tagged you in a Comment for Article '.$this->comment->article->title,
            'url' => sprintf('/articles/%s', $this->comment->article->id),
        ];
    }
}
