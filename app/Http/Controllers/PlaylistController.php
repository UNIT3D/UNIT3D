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
 * @author     HDVinnie
 */

namespace App\Http\Controllers;

use App\Models\Playlist;
use App\Models\PlaylistTorrent;
use App\Models\Torrent;
use App\Repositories\ChatRepository;
use App\Services\MovieScrapper;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Image;

final class PlaylistController extends Controller
{
    /**
     * @var ChatRepository
     */
    private ChatRepository $chat;
    /**
     * @var \Illuminate\Contracts\View\Factory
     */
    private Factory $viewFactory;
    /**
     * @var \Illuminate\Contracts\Auth\Guard
     */
    private Guard $guard;
    /**
     * @var \Illuminate\Routing\Redirector
     */
    private Redirector $redirector;
    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    private Repository $configRepository;

    /**
     * PlaylistController Constructor.
     *
     * @param  ChatRepository  $chat
     * @param  \Illuminate\Contracts\View\Factory  $viewFactory
     * @param  \Illuminate\Contracts\Auth\Guard  $guard
     * @param  \Illuminate\Routing\Redirector  $redirector
     * @param  \Illuminate\Contracts\Config\Repository  $configRepository
     */
    public function __construct(ChatRepository $chat, Factory $viewFactory, Guard $guard, Redirector $redirector, Repository $configRepository)
    {
        $this->chat = $chat;
        $this->viewFactory = $viewFactory;
        $this->guard = $guard;
        $this->redirector = $redirector;
        $this->configRepository = $configRepository;
    }

    /**
     * Display All Playlists.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(): Factory
    {
        $playlists = Playlist::with('user')->withCount('torrents')->where('is_private', '=', 0)->orderBy('name', 'ASC')->paginate(24);

        return $this->viewFactory->make('playlist.index', ['playlists' => $playlists]);
    }

    /**
     * Show Playlist Create Form.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create(): Factory
    {
        return $this->viewFactory->make('playlist.create');
    }

    /**
     * Store A New Playlist.
     *
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|mixed
     */
    public function store(Request $request)
    {
        $user = $this->guard->user();

        $playlist = new Playlist();
        $playlist->user_id = $user->id;
        $playlist->name = $request->input('name');
        $playlist->description = $request->input('description');
        $playlist->cover_image = null;

        if ($request->hasFile('cover_image') && $request->file('cover_image')->getError() === 0) {
            $image = $request->file('cover_image');
            $filename = 'playlist-cover_'.uniqid().'.'.$image->getClientOriginalExtension();
            $path = public_path('/files/img/'.$filename);
            Image::make($image->getRealPath())->fit(400, 225)->encode('png', 100)->save($path);
            $playlist->cover_image = $filename;
        }

        $playlist->position = $request->input('position');
        $playlist->is_private = $request->input('is_private');

        $v = validator($playlist->toArray(), [
            'user_id'     => 'required',
            'name'        => 'required',
            'description' => 'required',
            'is_private'  => 'required',
        ]);

        if ($v->fails()) {
            return $this->redirector->route('playlists.create')
                ->withInput()
                ->withErrors($v->errors());
        } else {
            $playlist->save();

            // Announce To Shoutbox
            $appurl = $this->configRepository->get('app.url');
            if ($playlist->is_private != 1) {
                $this->chat->systemMessage(
                    sprintf('User [url=%s/', $appurl).$user->username.'.'.$user->id.']'.$user->username.sprintf('[/url] has created a new playlist [url=%s/playlists/', $appurl).$playlist->id.']'.$playlist->name.'[/url] check it out now! :slight_smile:'
                );
            }

            return $this->redirector->route('playlists.show', ['id' => $playlist->id])
                ->withSuccess('Your Playlist Was Created Successfully!');
        }
    }

    /**
     * Show A Playlist.
     *
     * @param  \App\Playlist  $id
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     *
     * @throws \ErrorException
     * @throws \HttpInvalidParamException
     */
    public function show($id): Factory
    {
        $playlist = Playlist::findOrFail($id);
        $meta = null;

        $random = PlaylistTorrent::where('playlist_id', '=', $playlist->id)->inRandomOrder()->first();
        if (isset($random)) {
            $torrent = Torrent::where('id', '=', $random->torrent_id)->firstOrFail();
        }
        if (isset($random) && isset($torrent)) {
            $client = new MovieScrapper($this->configRepository->get('api-keys.tmdb'), $this->configRepository->get('api-keys.tvdb'), $this->configRepository->get('api-keys.omdb'));
            if ($torrent->category_id == 2) {
                if ($torrent->tmdb || $torrent->tmdb != 0) {
                    $meta = $client->scrape('tv', null, $torrent->tmdb);
                } else {
                    $meta = $client->scrape('tv', 'tt'.$torrent->imdb);
                }
            } elseif ($torrent->tmdb || $torrent->tmdb != 0) {
                $meta = $client->scrape('movie', null, $torrent->tmdb);
            } else {
                $meta = $client->scrape('movie', 'tt'.$torrent->imdb);
            }
        }

        $torrents = PlaylistTorrent::with(['torrent'])->where('playlist_id', '=', $playlist->id)->get()->sortBy('name');

        return $this->viewFactory->make('playlist.show', ['playlist' => $playlist, 'meta' => $meta, 'torrents' => $torrents]);
    }

    /**
     * Show Playlist Update Form.
     *
     * @param  \App\Playlist  $id
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit($id): Factory
    {
        $user = $this->guard->user();
        $playlist = Playlist::findOrFail($id);

        abort_unless($user->id == $playlist->user_id || $user->group->is_modo, 403);

        return $this->viewFactory->make('playlist.edit', ['playlist' => $playlist]);
    }

    /**
     * Update A Playlist.
     *
     * @param \Illuminate\Http\Request  $request
     * @param \App\Playlist  $id
     * @return \Illuminate\Http\RedirectResponse|mixed
     */
    public function update(Request $request, $id)
    {
        $user = $this->guard->user();
        $playlist = Playlist::findOrFail($id);

        abort_unless($user->id == $playlist->user_id || $user->group->is_modo, 403);

        $playlist->name = $request->input('name');
        $playlist->description = $request->input('description');
        $playlist->cover_image = null;

        if ($request->hasFile('cover_image') && $request->file('cover_image')->getError() === 0) {
            $image = $request->file('cover_image');
            $filename = 'playlist-cover_'.uniqid().'.'.$image->getClientOriginalExtension();
            $path = public_path('/files/img/'.$filename);
            Image::make($image->getRealPath())->fit(400, 225)->encode('png', 100)->save($path);
            $playlist->cover_image = $filename;
        }

        $playlist->position = $request->input('position');
        $playlist->is_private = $request->input('is_private');

        $v = validator($playlist->toArray(), [
            'name'        => 'required',
            'description' => 'required',
            'is_private'  => 'required',
        ]);

        if ($v->fails()) {
            return $this->redirector->route('playlists.edit', ['id' => $playlist->id])
                ->withInput()
                ->withErrors($v->errors());
        } else {
            $playlist->save();

            return $this->redirector->route('playlists.show', ['id' => $playlist->id])
                ->withSuccess('Your Playlist Has Successfully Been Updated!');
        }
    }

    /**
     * Delete A Playlist.
     *
     * @param  \App\Playlist  $id
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id): RedirectResponse
    {
        $user = $this->guard->user();
        $playlist = Playlist::findOrFail($id);

        abort_unless($user->id == $playlist->user_id || $user->group->is_modo, 403);

        $playlist->delete();

        return $this->redirector->route('playlists.index')
            ->withSuccess('Playlist Deleted!');
    }
}
