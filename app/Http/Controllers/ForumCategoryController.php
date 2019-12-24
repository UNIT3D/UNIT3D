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

use App\Models\Forum;
use App\Models\Post;
use App\Models\Topic;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;

final class ForumCategoryController extends Controller
{
    /**
     * @var \Illuminate\Routing\Redirector
     */
    private Redirector $redirector;
    /**
     * @var \Illuminate\Contracts\View\Factory
     */
    private Factory $viewFactory;

    public function __construct(Redirector $redirector, Factory $viewFactory)
    {
        $this->redirector = $redirector;
        $this->viewFactory = $viewFactory;
    }

    /**
     * Show The Forum Category.
     *
     * @param $id
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function show($id): RedirectResponse
    {
        // Find the topic
        $forum = Forum::findOrFail($id);

        // Total Forums Count
        $num_forums = Forum::count();
        // Total Posts Count
        $num_posts = Post::count();
        // Total Topics Count
        $num_topics = Topic::count();

        // Check if this is a category or forum
        if ($forum->parent_id != 0) {
            return $this->redirector->route('forums.show', ['id' => $forum->id]);
        }

        // Check if the user has permission to view the forum
        $category = Forum::findOrFail($forum->id);
        if ($category->getPermission()->show_forum != true) {
            return $this->redirector->route('forums.index')
                ->withErrors('You Do Not Have Access To This Category!');
        }

        // Fetch topics->posts in descending order
        $topics = $forum->sub_topics()->latest('pinned')->latest('last_reply_at')->latest()->paginate(25);

        return $this->viewFactory->make('forum.category', [
            'forum'    => $forum,
            'topics'   => $topics,
            'category' => $category,
            'num_posts'  => $num_posts,
            'num_forums' => $num_forums,
            'num_topics' => $num_topics,
        ]);
    }
}
