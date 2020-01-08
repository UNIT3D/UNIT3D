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
 * @author     Mr.G
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kyslik\ColumnSortable\Sortable;

/**
 * App\Models\Peer.
 *
 * @property int $id
 * @property string|null $peer_id
 * @property string|null $md5_peer_id
 * @property string|null $info_hash
 * @property string|null $ip
 * @property int|null $port
 * @property string|null $agent
 * @property int|null $uploaded
 * @property int|null $downloaded
 * @property int|null $left
 * @property int|null $seeder
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $torrent_id
 * @property int|null $user_id
 * @property-read \App\Models\Torrent $seed
 * @property-read \App\Models\Torrent|null $torrent
 * @property-read \App\Models\User|null $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Peer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Peer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Peer query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Peer sortable($defaultParameters = null)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Peer whereAgent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Peer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Peer whereDownloaded($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Peer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Peer whereInfoHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Peer whereIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Peer whereLeft($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Peer whereMd5PeerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Peer wherePeerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Peer wherePort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Peer whereSeeder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Peer whereTorrentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Peer whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Peer whereUploaded($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Peer whereUserId($value)
 * @mixin \Eloquent
 */
final class Peer extends Model
{
    use Sortable;

    /**
     * The Columns That Are Sortable.
     */
    public array $sortable = [
        'id',
        'agent',
        'uploaded',
        'downloaded',
        'left',
        'seeder',
        'created_at',
    ];

    /**
     * Belongs To A User.
     *
     * @return BelongsTo
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault([
            'username' => 'System',
            'id'       => '1',
        ]);
    }

    /**
     * Belongs To A Torrent.
     *
     * @return BelongsTo
     */
    public function torrent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Torrent::class);
    }

    /**
     * Belongs To A Seed.
     *
     * @return BelongsTo
     */
    public function seed(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Torrent::class, 'torrents.id', 'torrent_id');
    }
}
